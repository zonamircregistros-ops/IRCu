/*
 * udbnick - Native ircd-side nick account database for UnrealIRCd 6.x
 *
 * Reimplements, on top of the modern UnrealIRCd 6 module API, the specific
 * feature of trocotronic/udb (a UnrealIRCd 3.2.8 fork used by IRC-Hispano
 * style networks) that dBOTS (github.com/juanjiyo/dBOTS) users rely on:
 *
 *     /NICK usuario:contrasena
 *     /NICK usuario!contrasena
 *
 * A registered nick can only be taken by whoever supplies the correct
 * password inline in the NICK command. On success the client is marked
 * as logged into the services account "usuario" (client->user->account),
 * exactly like SASL would, so cloaking/WHOIS/extbans/etc. behave the same
 * way they do for a SASL login.
 *
 * This module owns its own account database (register/identify/setpass/drop
 * commands below) instead of talking to NickServ. It does NOT port UDB's
 * channel/IP-restriction/link/X-line database blocks -- Unreal 6 already
 * has native, better-maintained equivalents for those (chanserv-style
 * services, permchannels, TKL, links.conf). See ../README.md for the full
 * scope discussion and the alternative "thin shim" design that instead
 * defers password checks to an existing NickServ (e.g. dBOTS itself).
 *
 * Passwords are hashed with Argon2 (Auth_Hash/Auth_Check, the same API
 * UnrealIRCd uses for oper blocks) -- never the MD5/SHA1/RIPEMD160 that the
 * original 2010-era UDB used.
 */
#include "unrealircd.h"

ModuleHeader MOD_HEADER = {
	"third/udbnick",
	"1.0.0",
	"UDB-style /NICK nick:pass account database",
	"Zona MIRC Registros",
	"unrealircd-6",
};

/* ---- Config ------------------------------------------------------------
 * v1 has no config block parser yet: the database path and hash type are
 * compile-time constants. See README.md "Limitaciones conocidas".
 */
#define UDBNICK_DBFILE   "data/udbnick.db"
#define UDBNICK_MINPASS  6
#define UDBNICK_HASHTYPE AUTHTYPE_ARGON2

typedef struct UdbNickAccount UdbNickAccount;
struct UdbNickAccount {
	UdbNickAccount *next;
	char nick[NICKLEN + 1];   /* canonical (as registered) spelling */
	char *hash;                /* Auth_Hash() output, heap-owned */
	char *email;                /* optional, heap-owned, may be NULL */
	time_t registered;
};

static UdbNickAccount *udbnick_accounts = NULL;
static ModDataInfo *udbnick_md = NULL; /* pending-login stash, see below */

/* Forward declarations */
CMD_OVERRIDE_FUNC(udbnick_override_nick);
CMD_FUNC(udbnick_cmd_register);
CMD_FUNC(udbnick_cmd_identify);
CMD_FUNC(udbnick_cmd_setpass);
CMD_FUNC(udbnick_cmd_drop);
int udbnick_hook_local_connect(Client *client);
int udbnick_hook_post_nickchange(Client *client, MessageTag *mtags, const char *oldnick);
void udbnick_moddata_free(ModData *m);
static void udbnick_load(void);
static void udbnick_save(void);
static UdbNickAccount *udbnick_find(const char *nick);
static void udbnick_apply_pending_login(Client *client);
static int udbnick_check_password(Client *client, UdbNickAccount *acct, const char *pass);
static void udbnick_login(Client *client, const char *accountnick);

MOD_TEST()
{
	return MOD_SUCCESS;
}

MOD_INIT()
{
	ModDataInfo mreq;

	memset(&mreq, 0, sizeof(mreq));
	mreq.name = "udbnick_pendinglogin";
	mreq.free = udbnick_moddata_free;
	mreq.type = MODDATATYPE_LOCAL_CLIENT; /* local-only: never leaves this server */
	udbnick_md = ModDataAdd(modinfo->handle, mreq);
	if (!udbnick_md)
		return MOD_FAILED;

	CommandAdd(modinfo->handle, "REGISTER", udbnick_cmd_register, MAXPARA, CMD_USER);
	CommandAdd(modinfo->handle, "IDENTIFY", udbnick_cmd_identify, MAXPARA, CMD_USER);
	CommandAdd(modinfo->handle, "SETPASS", udbnick_cmd_setpass, MAXPARA, CMD_USER);
	CommandAdd(modinfo->handle, "DROP", udbnick_cmd_drop, MAXPARA, CMD_USER);

	HookAdd(modinfo->handle, HOOKTYPE_LOCAL_CONNECT, 0, udbnick_hook_local_connect);
	HookAdd(modinfo->handle, HOOKTYPE_POST_LOCAL_NICKCHANGE, 0, udbnick_hook_post_nickchange);

	return MOD_SUCCESS;
}

MOD_LOAD()
{
	/* CommandOverrideAdd() must happen in MOD_LOAD(), not MOD_INIT():
	 * at MOD_INIT() time the core "NICK" command may not be registered
	 * yet depending on module load order, and UnrealIRCd BUGs out if you
	 * try to override a command that doesn't exist yet. Caught by
	 * ./unrealircd configtest against a real 6.2.7-git build -- see
	 * ../DBOTS-MIGRATION.md "Pruebas reales" for the full test log. */
	CommandOverrideAdd(modinfo->handle, "NICK", 0, udbnick_override_nick);
	udbnick_load();
	return MOD_SUCCESS;
}

MOD_UNLOAD()
{
	UdbNickAccount *a, *next;

	for (a = udbnick_accounts; a; a = next)
	{
		next = a->next;
		safe_free(a->hash);
		safe_free(a->email);
		safe_free(a);
	}
	udbnick_accounts = NULL;
	return MOD_SUCCESS;
}

/* ---- Persistence --------------------------------------------------------
 * One line per account: nick:argon2hash:registered_ts:email
 * (Argon2's encoded form uses '$' internally, never ':', so this is safe.)
 */
static void udbnick_load(void)
{
	FILE *fp;
	char line[1024];

	fp = fopen(UDBNICK_DBFILE, "r");
	if (!fp)
		return; /* no database yet, that's fine on first run */

	while (fgets(line, sizeof(line), fp))
	{
		char *nick, *hash, *ts, *email;
		UdbNickAccount *a;

		line[strcspn(line, "\r\n")] = '\0';
		if (line[0] == '\0')
			continue;

		nick = line;
		hash = strchr(nick, ':');
		if (!hash)
			continue;
		*hash++ = '\0';
		ts = strchr(hash, ':');
		if (!ts)
			continue;
		*ts++ = '\0';
		email = strchr(ts, ':');
		if (email)
			*email++ = '\0';

		a = safe_alloc(sizeof(UdbNickAccount));
		strlcpy(a->nick, nick, sizeof(a->nick));
		safe_strdup(a->hash, hash);
		a->registered = atol(ts);
		if (email && *email)
			safe_strdup(a->email, email);
		a->next = udbnick_accounts;
		udbnick_accounts = a;
	}
	fclose(fp);
}

static void udbnick_save(void)
{
	FILE *fp;
	UdbNickAccount *a;

	fp = fopen(UDBNICK_DBFILE ".tmp", "w");
	if (!fp)
	{
		unreal_log(ULOG_ERROR, "udbnick", "UDBNICK_SAVE_FAILED", NULL,
		           "udbnick: could not open $filename for writing: $error",
		           log_data_string("filename", UDBNICK_DBFILE ".tmp"),
		           log_data_string("error", strerror(errno)));
		return;
	}
	for (a = udbnick_accounts; a; a = a->next)
	{
		fprintf(fp, "%s:%s:%ld:%s\n", a->nick, a->hash,
		        (long)a->registered, a->email ? a->email : "");
	}
	fclose(fp);
	rename(UDBNICK_DBFILE ".tmp", UDBNICK_DBFILE);
}

static UdbNickAccount *udbnick_find(const char *nick)
{
	UdbNickAccount *a;

	for (a = udbnick_accounts; a; a = a->next)
		if (!strcasecmp(a->nick, nick))
			return a;
	return NULL;
}

/* ---- Password handling --------------------------------------------------- */

static int udbnick_check_password(Client *client, UdbNickAccount *acct, const char *pass)
{
	AuthConfig authc;

	if (BadPtr(pass))
		return 0;
	memset(&authc, 0, sizeof(authc));
	authc.type = UDBNICK_HASHTYPE;
	authc.data = acct->hash;
	return Auth_Check(client, &authc, pass);
}

/* Mark 'client' as logged into services account 'accountnick', the same
 * way sasl.c does it, so cloaking/WHOIS/extbans react as if SASL had
 * authenticated them. */
static void udbnick_login(Client *client, const char *accountnick)
{
	strlcpy(client->user->account, accountnick, sizeof(client->user->account));
	RunHook(HOOKTYPE_ACCOUNT_LOGIN, client, NULL);
	sendnotice(client, "*** Identificado como \002%s\002.", accountnick);
	unreal_log(ULOG_INFO, "udbnick", "UDBNICK_LOGIN", client,
	           "$client.details identified as account $account via NICK nick:pass",
	           log_data_string("account", accountnick));
}

/* client->user does not exist yet for a brand new, still-unregistered
 * connection at the point the NICK override runs, so a verified login is
 * stashed in moddata and applied here once client->user is guaranteed to
 * exist (LOCAL_CONNECT for new users, POST_LOCAL_NICKCHANGE for existing
 * ones switching nick). */
static void udbnick_apply_pending_login(Client *client)
{
	ModData *m = &moddata_local_client(client, udbnick_md);

	if (!m->str)
		return;
	if (client->user && !strcasecmp(client->name, m->str))
		udbnick_login(client, m->str);
	safe_free(m->str);
}

int udbnick_hook_local_connect(Client *client)
{
	udbnick_apply_pending_login(client);
	return 0;
}

int udbnick_hook_post_nickchange(Client *client, MessageTag *mtags, const char *oldnick)
{
	udbnick_apply_pending_login(client);
	return 0;
}

void udbnick_moddata_free(ModData *m)
{
	safe_free(m->str);
}

/* ---- NICK override -------------------------------------------------------
 * Splits "nick:pass" / "nick!pass" into the real nick and the password.
 * If the resulting nick is registered:
 *   - no password given  -> reject the NICK change (mirrors UDB's
 *     "/NICK nick:clave para identificarte" behaviour)
 *   - wrong password     -> reject the NICK change
 *   - correct password   -> let the (stripped) nick change proceed, and
 *     stash the account so it gets applied once client->user exists.
 * Unregistered nicks always pass through untouched (password, if any, is
 * just discarded -- exactly like plain UnrealIRCd's "NICK nick pass"
 * second-parameter form already silently ignores a password for unknown
 * connect-block auth).
 */
CMD_OVERRIDE_FUNC(udbnick_override_nick)
{
	char nickbuf[NICKLEN + 2];
	char *sep;
	const char *pass = NULL;
	const char *myparv[MAXPARA + 1];
	UdbNickAccount *acct;
	int i;

	if (!MyConnect(client) || IsServer(client) || parc < 2 || BadPtr(parv[1]))
	{
		CALL_NEXT_COMMAND_OVERRIDE();
		return;
	}

	strlcpy(nickbuf, parv[1], sizeof(nickbuf));
	sep = strchr(nickbuf, ':');
	if (!sep)
		sep = strchr(nickbuf, '!');
	if (sep)
	{
		*sep = '\0';
		pass = sep + 1;
		if (BadPtr(pass))
			pass = NULL;
	}

	acct = udbnick_find(nickbuf);
	if (acct)
	{
		if (!pass)
		{
			sendnotice(client, "*** El nick \002%s\002 esta registrado. "
			           "Usa /NICK %s:tu_contrasena para identificarte.",
			           nickbuf, nickbuf);
			return; /* NICK message ignored, exactly like stock UDB */
		}
		if (!udbnick_check_password(client, acct, pass))
		{
			sendnotice(client, "*** Contrasena incorrecta para \002%s\002.", nickbuf);
			unreal_log(ULOG_INFO, "udbnick", "UDBNICK_BADPASS", client,
			           "Failed NICK nick:pass login attempt for account $account",
			           log_data_string("account", nickbuf));
			return; /* NICK message ignored */
		}
		/* Password correct: stash it, apply once client->user exists */
		safe_strdup(moddata_local_client(client, udbnick_md).str, nickbuf);
	}

	if (!sep)
	{
		/* No delimiter at all, nothing to rewrite */
		CALL_NEXT_COMMAND_OVERRIDE();
		return;
	}

	/* Rewrite parv[1] to the bare nick before handing off to the real
	 * NICK handler (and anything else chained after us). */
	for (i = 0; i < parc && i <= MAXPARA; i++)
		myparv[i] = parv[i];
	myparv[1] = nickbuf;
	parv = myparv;

	CALL_NEXT_COMMAND_OVERRIDE();
}

/* ---- /REGISTER <password> [email] --------------------------------------- */
CMD_FUNC(udbnick_cmd_register)
{
	UdbNickAccount *a;
	const char *hash;

	if (!client->user)
		return;
	if (parc < 2 || BadPtr(parv[1]))
	{
		sendnotice(client, "*** Sintaxis: /REGISTER <contrasena> [email]");
		return;
	}
	if (strlen(parv[1]) < UDBNICK_MINPASS)
	{
		sendnotice(client, "*** La contrasena debe tener al menos %d caracteres.", UDBNICK_MINPASS);
		return;
	}
	if (udbnick_find(client->name))
	{
		sendnotice(client, "*** El nick \002%s\002 ya esta registrado.", client->name);
		return;
	}

	hash = Auth_Hash(UDBNICK_HASHTYPE, parv[1]);
	if (!hash)
	{
		sendnotice(client, "*** Error interno generando la contrasena, intentalo de nuevo.");
		return;
	}

	a = safe_alloc(sizeof(UdbNickAccount));
	strlcpy(a->nick, client->name, sizeof(a->nick));
	safe_strdup(a->hash, hash);
	a->registered = TStime();
	if (parc > 2 && !BadPtr(parv[2]) && !strchr(parv[2], ':'))
		safe_strdup(a->email, parv[2]);
	a->next = udbnick_accounts;
	udbnick_accounts = a;
	udbnick_save();

	udbnick_login(client, client->name);
	sendnotice(client, "*** Nick \002%s\002 registrado. A partir de ahora usa "
	           "/NICK %s:tu_contrasena para identificarte al conectar.",
	           client->name, client->name);
	unreal_log(ULOG_INFO, "udbnick", "UDBNICK_REGISTER", client,
	           "$client.details registered account $account",
	           log_data_string("account", client->name));
}

/* ---- /IDENTIFY <password> ------------------------------------------------ */
CMD_FUNC(udbnick_cmd_identify)
{
	UdbNickAccount *a;

	if (!client->user)
		return;
	if (parc < 2 || BadPtr(parv[1]))
	{
		sendnotice(client, "*** Sintaxis: /IDENTIFY <contrasena>");
		return;
	}
	a = udbnick_find(client->name);
	if (!a)
	{
		sendnotice(client, "*** El nick \002%s\002 no esta registrado.", client->name);
		return;
	}
	if (IsLoggedIn(client))
	{
		sendnotice(client, "*** Ya estas identificado.");
		return;
	}
	if (!udbnick_check_password(client, a, parv[1]))
	{
		sendnotice(client, "*** Contrasena incorrecta.");
		unreal_log(ULOG_INFO, "udbnick", "UDBNICK_BADPASS", client,
		           "Failed IDENTIFY attempt for account $account",
		           log_data_string("account", client->name));
		return;
	}
	udbnick_login(client, client->name);
}

/* ---- /SETPASS <oldpassword> <newpassword> ------------------------------- */
CMD_FUNC(udbnick_cmd_setpass)
{
	UdbNickAccount *a;
	const char *hash;

	if (!client->user)
		return;
	if (parc < 3 || BadPtr(parv[1]) || BadPtr(parv[2]))
	{
		sendnotice(client, "*** Sintaxis: /SETPASS <contrasena_actual> <contrasena_nueva>");
		return;
	}
	a = udbnick_find(client->name);
	if (!a)
	{
		sendnotice(client, "*** El nick \002%s\002 no esta registrado.", client->name);
		return;
	}
	if (!udbnick_check_password(client, a, parv[1]))
	{
		sendnotice(client, "*** Contrasena actual incorrecta.");
		return;
	}
	if (strlen(parv[2]) < UDBNICK_MINPASS)
	{
		sendnotice(client, "*** La contrasena nueva debe tener al menos %d caracteres.", UDBNICK_MINPASS);
		return;
	}
	hash = Auth_Hash(UDBNICK_HASHTYPE, parv[2]);
	if (!hash)
	{
		sendnotice(client, "*** Error interno generando la contrasena, intentalo de nuevo.");
		return;
	}
	safe_strdup(a->hash, hash);
	udbnick_save();
	sendnotice(client, "*** Contrasena actualizada.");
}

/* ---- /DROP [nick] [password] --------------------------------------------
 * With no arguments: drops the account of the nick you're currently
 * identified as. With arguments: drops <nick> given its <password>
 * (works even if you're not currently using that nick). Opers may always
 * drop any nick without a password. */
CMD_FUNC(udbnick_cmd_drop)
{
	const char *targetnick;
	UdbNickAccount *a, *prev = NULL;

	if (!client->user)
		return;

	if (parc >= 3 && !BadPtr(parv[1]) && !BadPtr(parv[2]))
	{
		targetnick = parv[1];
		a = udbnick_find(targetnick);
		if (!a)
		{
			sendnotice(client, "*** El nick \002%s\002 no esta registrado.", targetnick);
			return;
		}
		if (!IsOper(client) && !udbnick_check_password(client, a, parv[2]))
		{
			sendnotice(client, "*** Contrasena incorrecta.");
			return;
		}
	}
	else if (parc >= 2 && !BadPtr(parv[1]) && IsOper(client))
	{
		targetnick = parv[1];
		a = udbnick_find(targetnick);
		if (!a)
		{
			sendnotice(client, "*** El nick \002%s\002 no esta registrado.", targetnick);
			return;
		}
	}
	else
	{
		if (!IsLoggedIn(client))
		{
			sendnotice(client, "*** Sintaxis: /DROP <nick> <contrasena> (o identificate primero para /DROP tu propio nick)");
			return;
		}
		targetnick = client->user->account;
		a = udbnick_find(targetnick);
		if (!a)
			return; /* shouldn't happen: logged in but no account row? */
	}

	{
		UdbNickAccount *cur = udbnick_accounts;
		prev = NULL;
		while (cur)
		{
			if (cur == a)
			{
				if (prev)
					prev->next = cur->next;
				else
					udbnick_accounts = cur->next;
				break;
			}
			prev = cur;
			cur = cur->next;
		}
	}

	sendnotice(client, "*** Nick \002%s\002 eliminado del registro.", targetnick);
	unreal_log(ULOG_INFO, "udbnick", "UDBNICK_DROP", client,
	           "$client.details dropped account $account",
	           log_data_string("account", targetnick));

	safe_free(a->hash);
	safe_free(a->email);
	safe_free(a);
	udbnick_save();
}
