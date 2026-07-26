/*
 * dbotsbridge - Exposes to authorized IRCops the handful of UnrealIRCd
 * commands that dBOTS (github.com/juanjiyo/dBOTS) needs but that stock
 * UnrealIRCd 6 only accepts from a linked SERVER (CMD_SERVER), not from
 * a regular client -- even an opered one.
 *
 * BACKGROUND: dBOTS was written for UnrealIRCd 3.2.8 + UDB, and it
 * connects to the ircd as a fake linked SERVER (raw PASS/PROTOCTL/SERVER
 * handshake in sistema/sockets.mrc, no SID). UnrealIRCd 6.2.6 requires
 * SID-based linking and will reject that handshake outright -- dBOTS
 * cannot link to a modern Unreal at all, full stop, regardless of any
 * other compatibility work. See ../README.md for the audit.
 *
 * The fix used here: dBOTS' service personas (NiCK, CHaN, etc.) connect
 * as ordinary opered IRC clients instead (NICK/USER/OPER, like any other
 * bot), and every server-only command they used to send over the S2S
 * link is replaced with either:
 *   a) the existing oper CLIENT command Unreal 6 already ships
 *      (SAJOIN/SAPART/SAMODE/SVSJOIN/SVSPART/SVSKILL/CHGHOST/CHGIDENT/
 *      CHGNAME/SETHOST/SETIDENT/UMODE2/GLINE/ZLINE/SHUN/SQUIT are all
 *      CMD_USER already -- no bridge needed, see ../README.md), or
 *   b) this module's DBOTSSVS command, for the handful that genuinely
 *      have no CMD_USER form: SVSNICK, SVSSILENCE, SVSNOLAG, SWHOIS, and
 *      SVS2MODE/SVSMODE (forcing arbitrary USER modes, e.g. +B/+k/+r, on
 *      someone else -- SAMODE only covers CHANNEL modes, and even then
 *      not this kind: see below).
 *
 * SVS2MODE/SVSMODE also accepts a CHANNEL as the target (parv[2]
 * starting with '#'), to force paramless channel modes -- concretely,
 * +r ("registered channel"), which CReG needs to set after accepting a
 * channel registration and which NickServ's own SVS2MODE call sets on
 * the user side after a successful IDENTIFY. This is NOT the same as
 * SAMODE: SAMODE goes through the normal do_mode()/is_ok() machinery,
 * and +r's own is_ok() handler (chanmodes/isregistered.c) returns
 * EX_ALWAYS_DENY for anyone that isn't IsServer()/IsULine() -- a check
 * do_mode() honors unconditionally, SAMODE included. This module's
 * channel path flips the mode bit directly (same trick dbots_svs2mode()
 * already uses for user modes) and broadcasts it itself, bypassing that
 * check the same deliberate way, behind the same "dbots:svs" permission.
 *
 * DBOTSSVS is intentionally its own single command (not a re-opening of
 * SVSNICK/SVSSILENCE/etc. to CMD_USER) so it can be gated behind one
 * dedicated operclass permission ("dbots:svs") instead of loosening
 * security-sensitive commands network-wide.
 *
 * SVSNOOP (the "strip every IRCop's privileges network-wide" panic
 * switch) is deliberately NOT bridged. Automating a command that can
 * de-op every oper on the network from a bot is not something this
 * module will do; if your dBOTS config path ever calls it, remove that
 * call, it has no supported replacement here.
 *
 * Multi-server networks: if the target user is not connected to the same
 * server dBOTS is connected to, DBOTSSVS relays itself once to the
 * target's server (same pattern core commands like SVSSILENCE use) --
 * so the module must be loaded on every server, not just the one dBOTS
 * connects to.
 */
#include "unrealircd.h"

#define MSG_DBOTSSVS "DBOTSSVS"
#define DBOTS_PERM   "dbots:svs"

CMD_FUNC(cmd_dbotssvs);
static void dbots_svsnick(Client *client, Client *target, const char *newnick);
static void dbots_svssilence(Client *client, Client *target, const char *list);
static void dbots_svsnolag(Client *client, Client *target, const char *plusminus);
static void dbots_swhois(Client *client, Client *target, int parc, const char *parv[]);
static void dbots_svs2mode(Client *client, Client *target, const char *modestr);
static void dbots_svs2mode_channel(Client *client, Channel *channel, const char *modestr);

ModuleHeader MOD_HEADER = {
	"third/dbotsbridge",
	"1.0.0",
	"Oper-gated bridge for the few SVS* commands dBOTS needs that have no CMD_USER form",
	"Zona MIRC Registros",
	"unrealircd-6",
};

MOD_TEST()
{
	return MOD_SUCCESS;
}

MOD_INIT()
{
	CommandAdd(modinfo->handle, MSG_DBOTSSVS, cmd_dbotssvs, MAXPARA, CMD_USER | CMD_SERVER);
	return MOD_SUCCESS;
}

MOD_LOAD()
{
	return MOD_SUCCESS;
}

MOD_UNLOAD()
{
	return MOD_SUCCESS;
}

/*
** DBOTSSVS <SVSNICK|SVSSILENCE|SVSNOLAG|SWHOIS> <target nick> <args...>
** Mirrors the parv layout of the real SVS* commands it replaces so the
** mIRC side only has to swap the command name, not rebuild the line.
*/
CMD_FUNC(cmd_dbotssvs)
{
	Client *target;
	const char *subcmd;

	if (parc < 3)
		return;

	subcmd = parv[1];

	/* client is always resolved to the true origin (the oper), even if
	 * this hop is a server link relaying it -- so the permission check
	 * is identical either way. */
	if (!IsOper(client) || !ValidatePermissionsForPath(DBOTS_PERM, client, NULL, NULL, NULL))
	{
		if (MyUser(client))
			sendnumeric(client, ERR_NOPRIVILEGES);
		return;
	}

	/* Channel target: only SVS2MODE/SVSMODE make sense here (e.g. +r,
	 * "registered channel" -- see dbots_svs2mode_channel()). Every other
	 * subcommand (SVSNICK, SVSSILENCE, ...) is inherently user-only, so
	 * a channel-shaped parv[2] for those is simply not a valid call. */
	if (parv[2][0] == '#')
	{
		if ((!strcasecmp(subcmd, "SVS2MODE") || !strcasecmp(subcmd, "SVSMODE")) && parc >= 4)
		{
			Channel *channel = find_channel(parv[2]);
			if (channel)
				dbots_svs2mode_channel(client, channel, parv[3]);
		}
		return;
	}

	target = find_user(parv[2], NULL);
	if (!target)
		return;

	if (!MyUser(target))
	{
		/* Not ours to handle: forward the exact same command, unmodified,
		 * once to whichever server owns the target -- same pattern core
		 * SVSSILENCE/SVSNOLAG use for their own single relay hop. */
		char buf[BUFSIZE];
		char *p = buf;
		int left = sizeof(buf);
		int n, i;

		n = snprintf(p, left, "%s", subcmd);
		p += n; left -= n;
		for (i = 2; i < parc && left > 1; i++)
		{
			int last = (i == parc - 1);
			n = snprintf(p, left, last ? " :%s" : " %s", parv[i]);
			p += n; left -= n;
		}
		sendto_one(target, NULL, ":%s %s %s", client->name, MSG_DBOTSSVS, buf);
		return;
	}

	if (!strcasecmp(subcmd, "SVSNICK") && parc >= 4)
		dbots_svsnick(client, target, parv[3]);
	else if (!strcasecmp(subcmd, "SVSSILENCE") && parc >= 4)
		dbots_svssilence(client, target, parv[3]);
	else if (!strcasecmp(subcmd, "SVSNOLAG") && parc >= 4)
		dbots_svsnolag(client, target, parv[3]);
	else if (!strcasecmp(subcmd, "SWHOIS"))
		dbots_swhois(client, target, parc, parv);
	else if ((!strcasecmp(subcmd, "SVS2MODE") || !strcasecmp(subcmd, "SVSMODE")) && parc >= 4)
		dbots_svs2mode(client, target, parv[3]);
}

/* ---- SVSNICK: force a nick change -----------------------------------
 * Same core logic as src/modules/svsnick.c's cmd_svsnick(), just reached
 * via an oper-gated client command instead of CMD_SERVER. */
static void dbots_svsnick(Client *client, Client *target, const char *newnick_in)
{
	char nickname[NICKLEN + 1];
	char oldnickname[NICKLEN + 1];
	Client *collision;
	MessageTag *mtags = NULL;

	strlcpy(nickname, newnick_in, sizeof(nickname));
	if (!do_nick_name(nickname))
		return;

	if ((collision = find_client(nickname, NULL)) && collision != target)
	{
		exit_client(target, NULL,
		            "Nickname collision due to forced nickname change, your nick was overruled");
		return;
	}
	if (!strcmp(target->name, nickname))
		return;

	strlcpy(oldnickname, target->name, sizeof(oldnickname));
	target->umodes &= ~UMODE_REGNICK;

	new_message(target, NULL, &mtags);
	RunHook(HOOKTYPE_LOCAL_NICKCHANGE, target, mtags, nickname);
	sendto_local_common_channels(target, target, 0, mtags, ":%s NICK :%s", target->name, nickname);
	sendto_one(target, mtags, ":%s NICK :%s", target->name, nickname);
	sendto_server(NULL, 0, 0, mtags, ":%s NICK %s :%lld", target->id, nickname, (long long)TStime());

	add_history(target, 1, WHOWAS_EVENT_NICK_CHANGE);
	target->lastnick = TStime();
	del_from_client_hash_table(target->name, target);
	strlcpy(target->name, nickname, sizeof(target->name));
	add_to_client_hash_table(nickname, target);
	RunHook(HOOKTYPE_POST_LOCAL_NICKCHANGE, target, mtags, oldnickname);
	free_message_tags(mtags);

	unreal_log(ULOG_INFO, "dbotsbridge", "DBOTS_SVSNICK", client,
	           "$client.details forced $target.details to change nick to $new_nick (via dBOTS bridge)",
	           log_data_client("target", target),
	           log_data_string("new_nick", nickname));
}

/* ---- SVSSILENCE: push +mask/-mask entries onto target's own SILENCE list ---- */
static void dbots_svssilence(Client *client, Client *target, const char *list)
{
	char request[BUFSIZE];
	char *p, *cp, c;

	strlcpy(request, list, sizeof(request));
	for (p = strtok(request, " "); p; p = strtok(NULL, " "))
	{
		c = *p;
		if ((c == '-') || (c == '+'))
			p++;
		else if (!(strchr(p, '@') || strchr(p, '.') || strchr(p, '!') || strchr(p, '*')))
			continue;
		else
			c = '+';
		cp = pretty_mask(p);
		if ((c == '-' && !del_silence(target, cp)) ||
		    (c != '-' && !add_silence(target, cp, 0)))
		{
			sendto_prefix_one(target, target, NULL, ":%s SILENCE %c%s", client->name, c, cp);
		}
	}
	unreal_log(ULOG_INFO, "dbotsbridge", "DBOTS_SVSSILENCE", client,
	           "$client.details updated $target.details's silence list (via dBOTS bridge)",
	           log_data_client("target", target));
}

/* ---- SVSNOLAG: exempt/un-exempt target from fake lag ----------------------- */
static void dbots_svsnolag(Client *client, Client *target, const char *plusminus)
{
	(void)client;
	if (*plusminus == '+')
		SetNoFakeLag(target);
	else if (*plusminus == '-')
		ClearNoFakeLag(target);
}

/* ---- SWHOIS: add/remove an extra WHOIS line on target ---------------------
** parv[3] = + or -
** parv[4] = tag (who/what added it, e.g. "dBOTS")
** parv[5] = priority (integer, higher = shown first)
** parv[6] = text (only for +) */
static void dbots_swhois(Client *client, Client *target, int parc, const char *parv[])
{
	int add;
	int priority = 0;

	if (parc < 6)
		return;
	add = (*parv[3] == '+') ? 1 : 0;
	priority = atoi(parv[5]);

	if (add)
	{
		if (parc < 7)
			return;
		swhois_add(target, parv[4], priority, parv[6], client, client);
	}
	else
	{
		/* Delete anything previously added under this tag, regardless
		 * of its text, mirroring swhois.c's own "old syntax" delete. */
		swhois_delete(target, parv[4], "*", client, client);
	}
}

/* ---- SVS2MODE / SVSMODE: force arbitrary USER modes on target -------------
 * set_usermode() only ever ADDs bits (starting from a fresh 0), it has no
 * "apply relative to what's already set" mode. So to support "+kB-i" style
 * strings we call it twice: once as given (yields the ADD bits, '-' parts
 * are no-ops against 0) and once with every '+'/'-' sign flipped (yields
 * the DEL bits, by the same no-op logic in reverse). Both calls reuse the
 * exact same parser core UnrealIRCd itself uses, nothing hand-rolled. */
static long dbots_modebits(const char *modestr, int deletions)
{
	char buf[64];
	char *d = buf;
	const char *s = modestr;

	while (*s && (size_t)(d - buf) < sizeof(buf) - 1)
	{
		if (deletions)
		{
			if (*s == '+') *d++ = '-';
			else if (*s == '-') *d++ = '+';
			else *d++ = *s;
		}
		else
		{
			*d++ = *s;
		}
		s++;
	}
	*d = '\0';
	return set_usermode(buf);
}

static void dbots_svs2mode(Client *client, Client *target, const char *modestr)
{
	long old = target->umodes;
	long addbits = dbots_modebits(modestr, 0);
	long delbits = dbots_modebits(modestr, 1);

	/* Never let this path grant IRCOp status -- SVSO (already CMD_USER
	 * in stock Unreal) and /OPER exist for that and go through proper
	 * oper-count bookkeeping and hooks that a raw bit flip here would
	 * skip. */
	addbits &= ~UMODE_OPER;

	target->umodes |= addbits;
	target->umodes &= ~delbits;

	if (target->umodes == old)
		return;

	send_umode_out(target, 1, old);
	unreal_log(ULOG_INFO, "dbotsbridge", "DBOTS_SVS2MODE", client,
	           "$client.details forced usermodes $modestr on $target.details (via dBOTS bridge)",
	           log_data_string("modestr", modestr),
	           log_data_client("target", target));
}

/* ---- SVS2MODE / SVSMODE on a CHANNEL target: force paramless channel
 * modes, e.g. +r ("registered channel") -- needed because dBOTS' own
 * CReG persona has no other way to set it. UnrealIRCd 6 gates +r (like
 * +z and a few others) behind IsServer()/IsULine() in its own is_ok()
 * handler (chanmodes/isregistered.c) -- the exact same restriction
 * dbots_svs2mode() above works around for USER modes. There is no
 * equivalent CMD_USER path for channel modes: stock SVSMODE/SVS2MODE's
 * own channel_svsmode() (src/modules/svsmode.c) only ever touches
 * ban-type (b/e/I) and MEMBER-type (o/v/...) modes -- paramless
 * CMODE_NORMAL modes like +r are simply not something it handles at
 * all, for channels or otherwise.
 *
 * Deliberately restricted to CMODE_NORMAL, paramless modes only (found
 * via the same find_channel_mode_handler() stock svsmode.c itself uses
 * for member-mode lookups) -- member modes (+o/+v) and parameter modes
 * (+l/+k/...) need very different handling (a target user, or a
 * parameter) that this bridge has no reason to grow, since dBOTS never
 * needs more than +r/-r through this specific path. Anything else in
 * modestr is silently skipped rather than partially applied. */
static void dbots_svs2mode_channel(Client *client, Channel *channel, const char *modestr)
{
	const char *m;
	int what = MODE_ADD;
	Cmode_t addbits = 0, delbits = 0;
	Cmode_t old = channel->mode.mode;
	char modebuf[64];
	char *mb = modebuf;
	MessageTag *mtags = NULL;

	for (m = modestr; *m && (size_t)(mb - modebuf) < sizeof(modebuf) - 2; m++)
	{
		Cmode *cm;

		if (*m == '+')
		{
			what = MODE_ADD;
			continue;
		}
		if (*m == '-')
		{
			what = MODE_DEL;
			continue;
		}

		cm = find_channel_mode_handler(*m);
		if (!cm || (cm->type != CMODE_NORMAL))
			continue;

		if (what == MODE_ADD)
			addbits |= cm->mode;
		else
			delbits |= cm->mode;

		*mb++ = (what == MODE_ADD) ? '+' : '-';
		*mb++ = *m;
	}
	*mb = '\0';

	if (!*modebuf)
		return;

	channel->mode.mode |= addbits;
	channel->mode.mode &= ~delbits;

	if (channel->mode.mode == old)
		return;

	new_message(client, NULL, &mtags);
	sendto_channel(channel, client, client, 0, 0, SEND_LOCAL, mtags,
	               ":%s MODE %s %s", client->name, channel->name, modebuf);
	sendto_server(NULL, 0, 0, mtags, ":%s MODE %s %s%s", client->id, channel->name, modebuf,
	              IsServer(client) ? " 0" : "");
	free_message_tags(mtags);

	unreal_log(ULOG_INFO, "dbotsbridge", "DBOTS_SVS2MODE_CHANNEL", client,
	           "$client.details forced channel modes $modestr on $channel.name (via dBOTS bridge)",
	           log_data_string("modestr", modebuf),
	           log_data_string("channel", channel->name));
}
