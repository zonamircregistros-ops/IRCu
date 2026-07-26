/*
 * udbnick - "/NICK usuario:contrasena" shim for UnrealIRCd 6.x, backed by
 * dBOTS' OWN real NickServ -- no separate account database of its own.
 *
 * v1 of this module (see git history) kept its own account database.
 * That was wrong for a real dBOTS deployment: the real accounts (70k+
 * users, ~20 years of history) live ONLY in dBOTS' own mIRC .db files.
 * A second, independent, empty database on the ircd side would have
 * orphaned every existing registration. Rewritten as a pure syntax
 * shim instead: dBOTS' NickServ (see ../DBOTS-MIGRATION.md for how it
 * gets adapted to connect as a regular oper client on Unreal 6) remains
 * the single source of truth for accounts. This module does not know,
 * and does not need to know, who is registered.
 *
 * What it does:
 *   /NICK usuario:contrasena
 *   /NICK usuario!contrasena
 * splits off the password, lets the (bare) nick change proceed exactly
 * like a normal NICK, and once the client is fully connected/renamed,
 * silently sends the equivalent of what the user would have typed by
 * hand:
 *   PRIVMSG <NickServ nick>@<this server> :IDENTIFY <contrasena>
 * dBOTS' existing nickserv.identify code (ni.mrc) does the actual
 * password check against its real database and replies with its own
 * usual NOTICE -- this module never sees whether it succeeded.
 *
 * Trade-off, stated plainly: the old local-database version could
 * outright REJECT a NICK to a registered nick with no/wrong password
 * (matching original UDB's strict "NICK message ignored" behaviour).
 * This shim cannot do that anymore -- it has no way to know locally
 * whether a nick is registered, so a bare NICK grab is never blocked
 * here. Any nick-squatting protection now has to come from dBOTS
 * itself (e.g. forced ghost/kill of unauthenticated holders of a
 * registered nick), the same as it did before UDB existed.
 */
#include "unrealircd.h"

ModuleHeader MOD_HEADER = {
	"third/udbnick",
	"2.0.0",
	"NICK nick:pass shim -> PRIVMSG NickServ IDENTIFY (dBOTS stays authoritative)",
	"Zona MIRC Registros",
	"unrealircd-6",
};

/* ---- Config --------------------------------------------------------------
 * No config block parser yet (see README "Limitaciones conocidas"): the
 * NickServ nick is a compile-time constant. Change it to match whatever
 * `nick=` your dbots.conf [nickserv] section actually uses.
 */
#define UDBNICK_NICKSERV_NICK "NiCK"

static ModDataInfo *udbnick_md = NULL; /* pending-identify stash, see below */

/* Forward declarations */
CMD_OVERRIDE_FUNC(udbnick_override_nick);
int udbnick_hook_local_connect(Client *client);
int udbnick_hook_post_nickchange(Client *client, MessageTag *mtags, const char *oldnick);
void udbnick_moddata_free(ModData *m);
static void udbnick_send_pending_identify(Client *client);

MOD_TEST()
{
	return MOD_SUCCESS;
}

MOD_INIT()
{
	ModDataInfo mreq;

	memset(&mreq, 0, sizeof(mreq));
	mreq.name = "udbnick_pendingpass";
	mreq.free = udbnick_moddata_free;
	mreq.type = MODDATATYPE_LOCAL_CLIENT; /* local-only: never leaves this server */
	udbnick_md = ModDataAdd(modinfo->handle, mreq);
	if (!udbnick_md)
		return MOD_FAILED;

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
	return MOD_SUCCESS;
}

MOD_UNLOAD()
{
	return MOD_SUCCESS;
}

/* client->user does not exist yet for a brand new, still-unregistered
 * connection at the point the NICK override runs, so the password is
 * stashed in moddata and the IDENTIFY is sent here once client->user is
 * guaranteed to exist (LOCAL_CONNECT for new users, POST_LOCAL_NICKCHANGE
 * for existing ones switching nick). */
static void udbnick_send_pending_identify(Client *client)
{
	ModData *m = &moddata_local_client(client, udbnick_md);
	char target[NICKLEN + HOSTLEN + 2];
	const char *idparv[3];

	if (!m->str)
		return;
	if (client->user)
	{
		snprintf(target, sizeof(target), "%s@%s", UDBNICK_NICKSERV_NICK, me.name);
		/* Deliver exactly as if the client had typed it by hand.
		 * parv[0] is the sender's own name, per convention (see e.g.
		 * svsjoin.c's do_cmd() call), not NULL. */
		idparv[0] = client->name;
		idparv[1] = target;
		idparv[2] = m->str;
		do_cmd(client, NULL, "PRIVMSG", 3, idparv);
		unreal_log(ULOG_INFO, "udbnick", "UDBNICK_AUTOIDENTIFY", client,
		           "$client.details auto-IDENTIFY sent to NickServ via NICK nick:pass shim");
	}
	safe_free(m->str);
}

int udbnick_hook_local_connect(Client *client)
{
	udbnick_send_pending_identify(client);
	return 0;
}

int udbnick_hook_post_nickchange(Client *client, MessageTag *mtags, const char *oldnick)
{
	udbnick_send_pending_identify(client);
	return 0;
}

void udbnick_moddata_free(ModData *m)
{
	safe_free(m->str);
}

/* ---- NICK override ---------------------------------------------------
 * Splits "nick:pass" / "nick!pass" into the real nick and the password,
 * lets the (bare) nick change proceed as normal, and stashes the
 * password to be delivered as an IDENTIFY once the client exists.
 * Unregistered/unrecognized nicks are harmless here: dBOTS' own
 * nickserv.identify will just reply "nick not registered" or "wrong
 * password" the same as if the user had typed /msg NickServ IDENTIFY
 * by hand -- this module has no opinion on that outcome.
 */
CMD_OVERRIDE_FUNC(udbnick_override_nick)
{
	char nickbuf[NICKLEN + 2];
	char *sep;
	const char *pass = NULL;
	const char *myparv[MAXPARA + 1];
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
	if (!sep)
	{
		CALL_NEXT_COMMAND_OVERRIDE();
		return;
	}

	*sep = '\0';
	pass = sep + 1;
	if (!BadPtr(pass))
		safe_strdup(moddata_local_client(client, udbnick_md).str, pass);

	/* Rewrite parv[1] to the bare nick before handing off to the real
	 * NICK handler (and anything else chained after us). */
	for (i = 0; i < parc && i <= MAXPARA; i++)
		myparv[i] = parv[i];
	myparv[1] = nickbuf;
	parv = myparv;

	CALL_NEXT_COMMAND_OVERRIDE();
}
