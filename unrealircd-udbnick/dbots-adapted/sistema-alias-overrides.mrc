; ==========================================================================
; UnrealIRCd 6 adaptation -- two alias replacements in sistema.mrc.
; Verified against real dBOTS source + real mIRC 6.2 + a compiled
; UnrealIRCd 6.2.7-git in this session. See ../DBOTS-MIGRATION.md
; "Pruebas reales (parte 2)" for the full, real test transcript.
;
; HOW TO APPLY: in sistema.mrc, find the two aliases named below
; (`alias s { ... }` and `alias p.m { ... }`) and replace their bodies
; with the versions here. Nothing else in sistema.mrc needs to change.
; ==========================================================================

; ---- Replaces: alias s { echo -s < $1- | sockwrite -nt dbots $1- | ... }
;
; Originally: sockwrite -nt dbots $1- -- a single shared server-link
; socket, with any persona's identity forged via the leading ":sender "
; prefix (dBOTS could do this because it WAS the server, introducing
; every persona itself).
;
; Now: each persona is its own real client connection with its own
; socket (dbots_nickserv, dbots_chanserv, ...; see sockets-bootstrap.mrc
; in this same directory). A real client can't forge its own sender
; prefix -- the ircd stamps it automatically based on who's actually
; connected on that socket. So this parses the ":sender " prefix dBOTS'
; own code already puts on every outgoing line, strips it (a real client
; never sends its own prefix), and routes the write to THAT persona's
; own socket instead of the one shared "dbots" socket.
alias s {
  var %line = $1-
  var %target = dbots_nickserv
  if ($left(%line,1) == :) {
    var %sender = $right($gettok(%line,1,32),-1)
    %line = $gettok(%line,2-,32)
    %target = $dbots6.socketfor(%sender)
  }
  echo -s < %line
  if ($sock(%target)) { sockwrite -nt %target %line }
  .signal modulos $iif(:* iswm $1-,$right($1-,-1),$1-)
  if ($2 != ping) && ($1 != ping) && ($2 != PONG) && ($2 != INFO) {
    echo @debug ( $+ $date - $time $+ ) => $1-
  }
}

; New alias, add it right after the one above. Maps a persona's current
; nick to its dedicated UnrealIRCd 6 client socket. Extend this one line
; per persona as you wire up the rest (op.mrc, ce.mrc, gl.mrc, etc.),
; following the exact same on:sockopen/on:sockread pattern that
; sockets-bootstrap.mrc uses for nickserv/chanserv.
alias dbots6.socketfor {
  if ($1 == $nickserv) { return dbots_nickserv }
  if ($1 == $chanserv) { return dbots_chanserv }
  return dbots_nickserv
}

; ---- Replaces: alias p.m { %tmp.m.bot = $1 | %tmp.m.origen = $o }
;
; $o (= $d(1)) is the FULL "nick!user@host" sender prefix off the raw
; PRIVMSG line, not just the nick -- confirmed by reading sistema.mrc's
; own `alias o { return $d(1) }`. The original UnrealIRCd 3.2.8 that
; dBOTS targets is lenient and accepts a PRIVMSG target of the form
; "nick!user@host" (routing on the nick part). UnrealIRCd 6 is strict
; and rejects it outright with ERR_NOSUCHNICK (401) -- confirmed live:
; before this fix, every NickServ/ChanServ reply died with
;   :irc.example.org 401 NiCK Prueba1!Mew@Clk-E7BB8D1A :No such nick/channel
; instead of reaching the user. Stripping to the bare nick (splitting on
; '!', ASCII 33) fixes it, and is a no-op improvement even against real
; UnrealIRCd 3.2.8 since it was always sending the bare nick that ircd
; happened to tolerate the noise around.
alias p.m { %tmp.m.bot = $1 | %tmp.m.origen = $gettok($o,1,33) }
