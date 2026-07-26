; ==========================================================================
; UnrealIRCd 6 adaptation -- makes dBOTS actually set +r (registered
; nick / registered channel) again, which it stopped doing once dBOTS
; no longer runs as a linked SERVER.
;
; WHY THIS IS NEEDED: under the original UDB architecture, dBOTS never
; had to set +r itself -- UDB (the ircd fork dBOTS was built for) kept
; its own account database integrated into the ircd core, and applied
; +r automatically as part of processing a successful IDENTIFY/REGISTER
; at the server level. Confirmed by reading the real, unmodified
; ni.mrc/cr.mrc/ch.mrc: none of them contain a single MODE, SVSMODE or
; SVS2MODE call for +r anywhere -- the only "+r" text in the whole
; suite is inside configurable welcome-message strings that just
; MENTION it. Now that dBOTS' personas are ordinary oper clients (see
; sockets-bootstrap.mrc) instead of the ircd itself, nobody sets +r
; unless dBOTS is told to ask for it explicitly.
;
; It's not simply a matter of dBOTS sending "MODE nick +r" itself,
; either: UnrealIRCd 6 defines both the user mode +r
; (src/api-usermode.c: `UmodeAdd(NULL, 'r', UMODE_GLOBAL, 0,
; umode_allow_none, &UMODE_REGNICK)`) and the channel mode +r
; (src/modules/chanmodes/isregistered.c's is_ok handler) as
; server-or-ULine-only -- confirmed by reading both handlers, and by
; testing live that even SAMODE (a stock oper command) cannot set
; channel +r, since do_mode() honors EX_ALWAYS_DENY unconditionally,
; SAMODE included.
;
; THE FIX has two parts:
;
; 1. src/dbotsbridge.c (this repo, already updated) extends the
;    existing DBOTSSVS SVS2MODE/SVSMODE command (which already forced
;    arbitrary USER modes for dBOTS, bypassing the same server-only
;    restriction) to also accept a CHANNEL as the target, for paramless
;    channel modes like +r. Same "dbots:svs" operclass permission
;    gate as everything else DBOTSSVS does. See the comments in that
;    file for exactly how (it directly flips the Cmode_t bit found via
;    find_channel_mode_handler() and broadcasts the MODE line itself,
;    the same trick already used for user modes).
;
; 2. Two one-line additions to dBOTS' own mIRC scripts, at the exact
;    two points that already know identify/registration genuinely
;    succeeded (nowhere else has that knowledge -- this is why
;    udbnick.c itself can never set +r safely: per its own README, the
;    shim has no visibility into whether the IDENTIFY it forwarded
;    actually succeeded).
;
; LIVE-TESTED: registering+identifying a nick now shows, via /WHOIS,
; "<nick> is identified for this nick" (UnrealIRCd's own native WHOIS
; line for +r) and mIRC's own "* <nick> sets mode: +r". Registering and
; accepting a channel via CReG now shows the channel's mode line
; including 'r' (e.g. "#channel [+nrt]") and "* CReG sets mode: +r".
; ==========================================================================

; ---- ni.mrc, alias i.n (called on EVERY successful IDENTIFY -- both
; from nickserv.identify's own success path and from the on-connect
; nick-restore path) -- add one line right after the line that records
; status 3 (identified). $1 here is the FULL "nick!user@host" mask (see
; ni-fixes.mrc for why), so the bare nick has to be extracted with
; $gettok($1,1,33) before handing it to DBOTSSVS, which resolves its
; target the same way any IRC command does (by nick, not by mask).
;
; Before:
;   alias i.n {
;     g.db status.db status $r.c($1) 3
;     .timer $+ $r.c($1) $+ -kill off
;     ...
; After:
;   alias i.n {
;     g.db status.db status $r.c($1) 3
;     s : $+ $nickserv DBOTSSVS SVS2MODE $gettok($1,1,33) +r
;     .timer $+ $r.c($1) $+ -kill off
;     ...

; ---- cr.mrc, alias cregserv.acepta -- add one line in EACH of the two
; branches that mark a channel ACEPTADO (the "ACEPTA <canal> FORCE"
; branch, and the normal PENDIENTE -> ACEPTADO branch a few lines
; below it). $d(5) is already the bare channel name here (channel
; names have no mask to strip).
;
; FORCE branch, before:
;     g.db cregserv\canales\ $+ $r.c($d(5)) datos ultcf $ctime
;     b.db nickserv\ $+ $r.c(%tmp.fundador) configuracion enregistro
;     memoserv.envia %tmp.fundador $cregserv Tu canal12 ...
; FORCE branch, after (one new line inserted):
;     g.db cregserv\canales\ $+ $r.c($d(5)) datos ultcf $ctime
;     b.db nickserv\ $+ $r.c(%tmp.fundador) configuracion enregistro
;     s : $+ $cregserv DBOTSSVS SVS2MODE $d(5) +r
;     memoserv.envia %tmp.fundador $cregserv Tu canal12 ...
;
; Normal branch, before:
;   g.db cregserv\canales\ $+ $r.c($d(5)) datos ultcf $ctime
;   memoserv.envia %tmp.fundador $cregserv Tu canal12 ...
; Normal branch, after (one new line inserted):
;   g.db cregserv\canales\ $+ $r.c($d(5)) datos ultcf $ctime
;   s : $+ $cregserv DBOTSSVS SVS2MODE $d(5) +r
;   memoserv.envia %tmp.fundador $cregserv Tu canal12 ...

; ---- Scope, honestly stated ----
; NOT covered by this patch (not what was asked, and each is a
; separate, smaller design decision of its own):
;   - Clearing channel +r if a channel is later dropped/unregistered
;     (chanserv.drop / cregserv equivalents don't call SVS2MODE -r).
;     Low risk in practice: a dropped channel's +r becomes stale
;     metadata, not a security issue, since nothing else in dBOTS
;     trusts channel +r for access control.
;   - Clearing user +r on nick DROP while the same nick is still
;     online under that name. UnrealIRCd's own core (src/modules/
;     nick.c) already auto-clears user +r on any NICK change, which
;     covers the overwhelmingly common case (someone's registration
;     lapses, they eventually change nick or reconnect). The narrow
;     edge case (dropped while still connected under that exact nick,
;     never changing nick again) is the same kind of stale-metadata
;     situation as the channel case above.
