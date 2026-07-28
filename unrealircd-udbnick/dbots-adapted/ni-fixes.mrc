; ==========================================================================
; UnrealIRCd 6 adaptation -- three real bugs found in ni.mrc (NickServ)
; by actually running the REGISTER -> VALIDAR -> login roundtrip against
; real dBOTS + real mIRC 6.2 + a compiled UnrealIRCd 6.2.7-git. Not
; theoretical -- each one blocked a real login attempt until fixed. See
; ../DBOTS-MIGRATION.md "Pruebas reales (parte 3)" for the transcripts.
;
; HOW TO APPLY: three small, surgical edits inside ni.mrc. Nothing else
; needs to change. All three are instances of the SAME underlying
; mistake: using $o (= $d(1), the FULL "nick!user@host" sender prefix)
; in a spot that actually needs the bare nick. This is the same class of
; bug already documented for p.m in sistema-alias-overrides.mrc -- dBOTS'
; own code conflates "$o as a database storage key" (correct: the full
; mask IS the per-nick storage key throughout, e.g.
; nickserv\ $+ $r.c($o)) with "$o as a value to compare/print" (wrong:
; those need $gettok($o,1,33), the bare nick before the first '!').
; ==========================================================================

; ---- Fix 1: nickserv.registra2's login instructions embedded the FULL
; hostmask instead of the bare nick, so the exact command dBOTS itself
; tells a freshly-registered user to type would never work.
;
; Before (real, unmodified dBOTS):
;   else { m Tu contraseña temporal es12 %tmp.password $+ . Utilice12 /nick $o $+ ! $+ %tmp.password para identificarse. }
; e.g. for nick "TestLogin" this printed:
;   Utilice /nick TestLogin!Mew@Clk-E7BB8D1A!T6O2qdE2711j para identificarse.
; A user (or udbnick.c's NICK override, which splits on the FIRST '!' or
; ':') copy-pasting that literally sends password
; "Mew@Clk-E7BB8D1A!T6O2qdE2711j" instead of "T6O2qdE2711j" -- login
; fails. Confirmed live: exact symptom reproduced with a real registered
; test nick before this fix.
;
; After (inside alias nickserv.registra2 -- only "$o $+ !" changes to
; "$gettok($o,1,33) $+ !", nothing else on the line):
;   else { m Tu contraseña temporal es12 %tmp.password $+ . Utilice12 /nick $gettok($o,1,33) $+ ! $+ %tmp.password para identificarse. }

; ---- Fix 2: nickserv.identify's target-qualification check only
; accepted the fully-qualified "$nickserv@$conf.servidor" form, never
; the bare nick. Every OTHER command dispatcher in this exact codebase
; (ni.mrc's own top-of-file PRIVMSG match, plus ce.mrc/ch.mrc/cr.mrc/
; gl.mrc/he.mrc/me.mrc/no.mrc/op.mrc/pr.mrc -- ALL eleven personas)
; already accepts both forms:
;   (( $d(3) == $nickserv ) || ( $d(3) == $nickserv $+ @ $+ %conf.servidor ))
; nickserv.identify's dedicated re-check at line ~417 was the one place
; in the whole suite that didn't. Under UnrealIRCd 6, PRIVMSG delivered
; to "nick@servername" arrives at the recipient with $d(3) as the bare
; nick (the ircd resolves and normalizes the target before delivery) --
; confirmed live by manually sending
;   /msg NiCK@irc.example.org IDENTIFY <realpassword>
; from mIRC itself (not just via udbnick.c) and getting rejected with
; dBOTS' own "El sistema de identificación ha cambiado" message every
; time, using the correct password. This is not specific to the udbnick
; shim -- a real human typing dBOTS' own suggested syntax hits the same
; wall.
;
; Before:
;   if ( $d(3) != $nickserv $+ @ $+ %conf.servidor ) { m El sistema de identificación ha cambiado. Identifícate con 12/msg $nickserv $+ @ $+ %conf.servidor IDENTIFY <tu_contraseña> | l.v }
; After (accept both forms, matching every other dispatcher in the suite):
;   if ( ( $d(3) != $nickserv ) && ( $d(3) != $nickserv $+ @ $+ %conf.servidor ) ) { m El sistema de identificación ha cambiado. Identifícate con 12/msg $nickserv $+ @ $+ %conf.servidor IDENTIFY <tu_contraseña> | l.v }

; ---- Fix 3: root-nick auto-promotion never fired, because i.n (called
; as "i.n $o" from nickserv.identify on successful login, and also from
; ni.mrc's own on-connect nick-restore path) passes the FULL hostmask,
; but the two comparisons against the configured root nick
; (dbots.conf [otras] root=, a bare nick -- confirmed by reading
; dialog.mrc's config-panel code, which writes it from a plain text
; field) compared that bare config value directly against
; $r.c($1)+$r.c(fullmask), which can never match. Confirmed live: after
; registering+identifying a nick equal to dbots.conf's configured root=
; value, status.db kept recording status 3 (plain identified user)
; instead of 8/9 (root admin) -- and CReG's ACEPTA (oper approval of a
; channel registration) correctly refused it with "Permiso denegado"
; until this was fixed.
;
; Both occurrences are inside alias nickserv.c.r:
; Before:
;   if ($r.c($1) == $l.conf(otras,root)) { g.db status.db status $r.c($1) 9 }
;   ...
;   if ( $r.c($1) == %conf.root ) { g.db status.db status $r.c($1) 8 | g.db representantes.db conectados $r.c($1) Administrador }
; After (extract the bare nick from $1 before r.c-encoding it for the
; comparison; the g.db calls right after keep using $r.c($1), the full
; mask, since that's the correct storage-key convention everywhere else):
;   if ($r.c($gettok($1,1,33)) == $l.conf(otras,root)) { g.db status.db status $r.c($1) 9 }
;   ...
;   if ( $r.c($gettok($1,1,33)) == %conf.root ) { g.db status.db status $r.c($1) 8 | g.db representantes.db conectados $r.c($1) Administrador }
