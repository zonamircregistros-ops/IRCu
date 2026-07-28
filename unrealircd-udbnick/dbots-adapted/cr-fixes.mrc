; ==========================================================================
; UnrealIRCd 6 adaptation -- one real bug found in cr.mrc (CReG, channel
; registration) by actually running REGISTRA -> (WHO check) -> ACEPTA
; against real dBOTS + real mIRC 6.2 + a compiled UnrealIRCd 6.2.7-git.
; See ../DBOTS-MIGRATION.md "Pruebas reales (parte 3)" for the
; transcript. Same class of bug as ni-fixes.mrc: $o (full
; "nick!user@host") used where the bare nick was needed.
;
; HOW TO APPLY: one line inside alias cregserv.registra.
; ==========================================================================

; cregserv.registra stashes the founder's identity, then sends a WHO on
; the target channel to confirm the founder is actually opped there
; (dbots.mrc's on:sockread:dbots_cregserv: handler matches WHO's numeric
; 352 reply's nick field, $d(8), against this stashed value, and checks
; for '@' in the flags field, $d(9), before allowing registration to
; proceed to ENREGISTRO/PENDIENTE).
;
; Before (real, unmodified dBOTS):
;   set %creg.registra.i- [ $+ [ $d(5) ] ] $o $d(6) $d(7-)
; $o is the full "nick!user@host" mask. WHO's reply nick field ($d(8))
; is always the bare nick. The comparison
;   $d(8) == $gettok(%creg.registra.i-[chan],1,32)
; can therefore never match -- confirmed live: every REGISTRA attempt
; fell through to the 315 (end-of-WHO) handler's "op still not
; confirmed" branch and replied
;   ERROR: No tienes @ en el canal #testchan
; even though the founder was genuinely opped (WHO's own flags field
; showed "H@") and had just created the channel.
;
; After (only $o changes to $gettok($o,1,33), the bare nick -- $d(6) and
; $d(7-), the password and description, are untouched):
;   set %creg.registra.i- [ $+ [ $d(5) ] ] $gettok($o,1,33) $d(6) $d(7-)
