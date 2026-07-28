; ==========================================================================
; UnrealIRCd 6 adaptation -- makes dBOTS' OPeR BLOCK, GLINE and
; KILLCLONES commands actually create real, enforced bans again. Under
; the original UDB architecture dBOTS was a linked SERVER and could
; issue a raw client "TKL + G ..." line and have it work. UnrealIRCd 6
; silently drops it: cmd_tkl_add() (src/modules/tkl.c) hard-requires
; IsServer(client) || IsMe(client), with no operclass permission able
; to satisfy it, and it isn't even logged (the check runs before any
; unreal_log() call). Confirmed live: BLOCK/GLINE/KILLCLONES all
; reported their own "success" message (dBOTS prints that
; unconditionally) while UnrealIRCd's own gline.db and ircd.log showed
; nothing had happened at all.
;
; Fix, same pattern as the +r fix in plus-r-modes.mrc: src/dbotsbridge.c
; (this repo, already updated) gained a `DBOTSSVS GLINE ADD/DEL`
; subcommand that calls the exact same internal TKL-layer functions
; cmd_tkl_add()/cmd_tkl_del() call (tkl_add_serverban(), tkl_added(),
; etc.), all of which ARE exported for cross-module use (extern MODVAR
; in include/h.h). See the comments in dbotsbridge.c for the full
; rationale and the one honestly-stated gap (GLINE DEL doesn't
; broadcast the removal to other linked servers, only ADD does -- see
; that file).
;
; LIVE-TESTED: BLOCK/GLINE ADD now shows up in UnrealIRCd's own
; gline.db AND ircd.log AND triggers the real oper snotice ("G-Line
; added: ... [by: NiCK] [duration: 5m]"), broadcast to every opered
; persona exactly like a real /GLINE would. GLINE DEL likewise shows
; "G-Line removed: ...". Enforcement (actually killing a banned user)
; was confirmed to correctly respect UnrealIRCd's own hardcoded
; *@127.0.0.1 / *@::1 "localhost is always exempt" exception
; (src/modules/tkl.c's add_default_exempts()) -- this could only be
; verified structurally in this single-machine test environment (every
; test connection IS localhost), not by watching a real non-exempt user
; actually get killed, but the same code path a real /GLINE goes
; through is what's being exercised.
;
; IMPORTANT PREREQUISITE: this only works at all once dBOTS' own
; usuarios.db has the target's REAL host, not their cloak. See
; sockets-bootstrap.mrc's dbots6.onread (WHOIS-based real-host
; learning) and DBOTS-MIGRATION.md "parte 5" for why that's a separate,
; necessary fix -- BLOCK/GLINE/KILLCLONES all read the ban host from
; usuarios.db.
; ==========================================================================

; ---- sistema.mrc, alias g (shared by BLOCK/GLINE/KILLCLONES in
; op.mrc, and CeNTeR's own KLINE-like command in ce.mrc) --
; Before:
;   alias g {
;     %tmp.glinet = $iif($5 >= 1,$calc($ctime + $5),0)
;     if ( $1 == 1 ) { s : $+ %conf.servidor TKL + G $2 $3 $4 %tmp.glinet $ctime : $+ $6- }
;     g.db gline.db gline $2 $+ $chr(64) $+ $3 $r.c($gettok($4,1,33)) %tmp.glinet $ctime $q.c($6-)
;   }
; After (only the raw TKL line changes -- $5 here is already the
; duration in seconds, which is what DBOTSSVS GLINE ADD expects; the
; rest of the alias, including dBOTS' own gline.db bookkeeping, is
; untouched):
;   alias g {
;     %tmp.glinet = $iif($5 >= 1,$calc($ctime + $5),0)
;     if ( $1 == 1 ) { s : $+ %conf.servidor DBOTSSVS GLINE ADD $2 $3 $5 : $+ $6- }
;     g.db gline.db gline $2 $+ $chr(64) $+ $3 $r.c($gettok($4,1,33)) %tmp.glinet $ctime $q.c($6-)
;   }

; ---- op.mrc, alias operserv.gline, the DEL branch --
; Before:
;     s : $+ %conf.servidor TKL - G %tmp.ident %tmp.host $o
; After:
;     s : $+ %conf.servidor DBOTSSVS GLINE DEL %tmp.ident %tmp.host

; ---- sistema.mrc, alias gline.c.p (checks whether a host belongs to a
; protected representative before allowing BLOCK/GLINE/KILLCLONES to
; proceed) -- unrelated to the TKL/DBOTSSVS fix above, but discovered
; while testing it: this alias reads $mircdirdatabase\clones.db via
; $initopic()/loadbuf, and dBOTS' own r.dbs (called by conectar.u6 on
; every reconnect) deliberately wipes that file down to nothing on
; every connect (`.remove clones.db` then recreate-and-immediately-
; strip, by design -- clone tracking is meant to be rebuilt fresh each
; session). On a freshly-wiped/nonexistent clones.db, $initopic()
; doesn't reliably return 0 the way the alias assumes, so `loadbuf`
; gets called on a file that doesn't exist and throws
; "* /loadbuf: unable to open ... clones.db". Confirmed live: this
; error, left unhandled, appeared to abort the rest of the calling
; alias (BLOCK never even reached the point of sending the ban).
;
; Before:
;   alias gline.c.p {
;     flushini $mircdirdatabase\clones.db
;     %tmp.ruta = $mircdirdatabase\clones.db
;     if ( $initopic( %tmp.ruta , $1 ) == 0 ) { goto f.g.c.p }
;     ...
; After (one line added):
;   alias gline.c.p {
;     flushini $mircdirdatabase\clones.db
;     %tmp.ruta = $mircdirdatabase\clones.db
;     if ( $exists(%tmp.ruta) == $false ) { return no }
;     if ( $initopic( %tmp.ruta , $1 ) == 0 ) { goto f.g.c.p }
;     ...
