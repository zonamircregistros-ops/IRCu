; ==========================================================================
; UnrealIRCd 6 adaptation. Replaces the OLD PASS/PROTOCTL/SERVER
; server-link bootstrap in sockets.mrc (the "on 1:sockopen:dbots:"
; through "on 1:sockclose:dbots:" block). See ../DBOTS-MIGRATION.md for
; why the original handshake cannot link to a modern UnrealIRCd (no SID
; support) and the full design writeup.
;
; LIVE-TESTED, not just proposed: this exact code (all 11 personas, not
; just nickserv+chanserv) was run against a real mIRC 6.2 (the actual
; binary dBOTS ships) and a compiled UnrealIRCd 6.2.7-git across several
; rounds in this session. Confirmed end to end, with real dBOTS replies
; (not mocked): nick REGISTER -> VALIDAR -> temp password -> /NICK
; nick:pass login via udbnick.c's shim; CReG channel REGISTRA -> oper
; ACEPTA; OPeR STATS/IRCOPS; CeNTeR CLONES; GLoBaL GLOBAL; PRoXy IGNORA;
; NoTiCiaS ALTA; HeLP UMODES; MeMO SEND. Full transcripts in
; ../DBOTS-MIGRATION.md "Pruebas reales (parte 2)" and "(parte 3)".
;
; Each dBOTS persona now connects as an ordinary IRC client
; (NICK/USER), then /OPERs to gain the privileges it needs. All
; personas run inside this SAME mIRC process, so they share every
; alias/global exactly like before -- only the transport changed.
;
; Config expected in dbots.conf, new [unreal6] section:
;   [unreal6]
;   ircserver=127.0.0.1
;   ircport=6667
;   operuser=bobsmith
;   operpass=testpass123
;
; IMPORTANT -- dbots.conf [otras] servidor= must equal the ircd's real
; server name (unrealircd.conf's set::name, e.g. irc.example.org), NOT
; the old UDB-era placeholder some stock dbots.conf ship with (e.g.
; "deep.space"). dBOTS' own nickserv.identify checks the PRIVMSG target
; against "$nickserv@$conf.servidor" -- see DBOTS-MIGRATION.md for the
; related ni.mrc patch this interacts with.
;
; All 11 personas from %conf.tbots. shadowserv has no PRIVMSG dispatcher
; of its own in the original code (channel-mode enforcement only), so it
; connects but nothing sends it commands directly -- connect+OPER
; success is the extent of what can be tested for it.
;
; conectar.u6 also seeds bots.db with all 11 persona nicks before
; opening any socket. Under the original server-link architecture this
; bookkeeping happened as a side effect of the "c.b" pseudo-client
; introduction routine (SQLINE + legacy NICK-burst, done once when
; dBOTS' fake SERVER first introduced each persona). This adaptation
; never calls c.b -- personas are real NICK/USER clients from the
; start -- so without this seeding, functions that gate on "is this nick
; already a known bot" (e.g. globalserv.e.g, used by GLOBAL) wrongly
; conclude the persona was never introduced and try to run the legacy
; c.b path against it. That path does "s SQLINE <ownnick> :..." followed
; by a fake server-style NICK burst -- SQLINE-ing the bot's OWN
; currently-connected nick, which UnrealIRCd then kills. Confirmed live:
; without this seeding, sending GLOBAL to GLoBaL made it SQLINE and
; disconnect itself; with the seeding, GLOBAL works and GLoBaL stays
; connected. See DBOTS-MIGRATION.md "parte 3" for the transcript.
; ==========================================================================

alias dbots6.oper { sockwrite -tn $1 OPER $l.conf(unreal6,operuser) $l.conf(unreal6,operpass) }

alias dbots6.onread {
  sockread %datos
  if ($sockerr) { return }
  if ($d(1) == PING) { sockwrite -tn $sockname PONG $d(2) | return }
  if ($gettok(%datos,2,32) == 001) { dbots6.oper $sockname | return }
  if ($gettok(%datos,2,32) == 381) { sockwrite -tn $sockname MODE $me +iBdH | return }
  echo @debug ( $+ $date - $time $+ ) [ $+ $sockname $+ ] => %datos
  .signal modulos $iif(:* iswm %datos,$right(%datos,-1),%datos)
}

on 1:sockopen:dbots_nickserv: {
  if ($sockerr > 0) { v.bots 4Error NiCK: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $nickserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(nickserv,realname)
}
on 1:sockread:dbots_nickserv: { dbots6.onread }
on 1:sockclose:dbots_nickserv: { .timer* off }

on 1:sockopen:dbots_chanserv: {
  if ($sockerr > 0) { v.bots 4Error CHaN: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $chanserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(chanserv,realname)
}
on 1:sockread:dbots_chanserv: { dbots6.onread }
on 1:sockclose:dbots_chanserv: { .timer* off }

on 1:sockopen:dbots_cregserv: {
  if ($sockerr > 0) { v.bots 4Error CReG: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $cregserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(cregserv,realname)
}
on 1:sockread:dbots_cregserv: { dbots6.onread }
on 1:sockclose:dbots_cregserv: { .timer* off }

on 1:sockopen:dbots_operserv: {
  if ($sockerr > 0) { v.bots 4Error OPeR: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $operserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(operserv,realname)
}
on 1:sockread:dbots_operserv: { dbots6.onread }
on 1:sockclose:dbots_operserv: { .timer* off }

on 1:sockopen:dbots_centerserv: {
  if ($sockerr > 0) { v.bots 4Error CeNTeR: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $centerserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(centerserv,realname)
}
on 1:sockread:dbots_centerserv: { dbots6.onread }
on 1:sockclose:dbots_centerserv: { .timer* off }

on 1:sockopen:dbots_globalserv: {
  if ($sockerr > 0) { v.bots 4Error GLoBaL: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $globalserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(globalserv,realname)
}
on 1:sockread:dbots_globalserv: { dbots6.onread }
on 1:sockclose:dbots_globalserv: { .timer* off }

on 1:sockopen:dbots_proxyserv: {
  if ($sockerr > 0) { v.bots 4Error PRoXy: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $proxyserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(proxyserv,realname)
}
on 1:sockread:dbots_proxyserv: { dbots6.onread }
on 1:sockclose:dbots_proxyserv: { .timer* off }

on 1:sockopen:dbots_noticiasserv: {
  if ($sockerr > 0) { v.bots 4Error NoTiCiaS: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $noticiasserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(noticiasserv,realname)
}
on 1:sockread:dbots_noticiasserv: { dbots6.onread }
on 1:sockclose:dbots_noticiasserv: { .timer* off }

on 1:sockopen:dbots_helpserv: {
  if ($sockerr > 0) { v.bots 4Error HeLP: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $helpserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(helpserv,realname)
}
on 1:sockread:dbots_helpserv: { dbots6.onread }
on 1:sockclose:dbots_helpserv: { .timer* off }

on 1:sockopen:dbots_memoserv: {
  if ($sockerr > 0) { v.bots 4Error MeMO: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $memoserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(memoserv,realname)
}
on 1:sockread:dbots_memoserv: { dbots6.onread }
on 1:sockclose:dbots_memoserv: { .timer* off }

on 1:sockopen:dbots_shadowserv: {
  if ($sockerr > 0) { v.bots 4Error SHaDoW: $sock($sockname).wsmsg | return }
  sockwrite -tn $sockname NICK $shadowserv
  sockwrite -tn $sockname USER dbots 0 * : $+ $l.conf(shadowserv,realname)
}
on 1:sockread:dbots_shadowserv: { dbots6.onread }
on 1:sockclose:dbots_shadowserv: { .timer* off }

alias conectar.u6 {
  r.conf
  r.dbs
  g.db bots.db bots $r.c($nickserv) Bot
  g.db bots.db bots $r.c($chanserv) Bot
  g.db bots.db bots $r.c($cregserv) Bot
  g.db bots.db bots $r.c($operserv) Bot
  g.db bots.db bots $r.c($centerserv) Bot
  g.db bots.db bots $r.c($globalserv) Bot
  g.db bots.db bots $r.c($proxyserv) Bot
  g.db bots.db bots $r.c($noticiasserv) Bot
  g.db bots.db bots $r.c($helpserv) Bot
  g.db bots.db bots $r.c($memoserv) Bot
  g.db bots.db bots $r.c($shadowserv) Bot
  sockopen dbots_nickserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_chanserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_cregserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_operserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_centerserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_globalserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_proxyserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_noticiasserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_helpserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_memoserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_shadowserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
}
