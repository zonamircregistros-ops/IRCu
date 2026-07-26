; ==========================================================================
; UnrealIRCd 6 adaptation. Replaces the OLD PASS/PROTOCTL/SERVER
; server-link bootstrap in sockets.mrc (the "on 1:sockopen:dbots:"
; through "on 1:sockclose:dbots:" block). See ../DBOTS-MIGRATION.md for
; why the original handshake cannot link to a modern UnrealIRCd (no SID
; support) and the full design writeup.
;
; LIVE-TESTED, not just proposed: this exact code, together with
; sistema-alias-overrides.mrc, was run against a real mIRC 6.2 (the
; actual binary dBOTS ships) and a compiled UnrealIRCd 6.2.7-git in this
; session. Both nickserv and chanserv connected, opered, and correctly
; ran dBOTS' own unmodified ni.mrc registration logic end to end
; (/msg NiCK REGISTER <email> triggered the real email-verification
; flow). Full transcript in ../DBOTS-MIGRATION.md "Pruebas reales
; (parte 2)".
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
; Proven scope: nickserv + chanserv (registro de nicks funciona de
; extremo a extremo; el registro de canales resultó ser un bot
; DISTINTO, ver DBOTS-MIGRATION.md). Extend by copying the
; on:sockopen/on:sockread pair per remaining persona (op.mrc, ce.mrc,
; gl.mrc, cregserv, etc.) and adding one line to dbots6.socketfor() in
; sistema-alias-overrides.mrc.
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

alias conectar.u6 {
  r.conf
  r.dbs
  sockopen dbots_nickserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
  sockopen dbots_chanserv $l.conf(unreal6,ircserver) $l.conf(unreal6,ircport)
}
