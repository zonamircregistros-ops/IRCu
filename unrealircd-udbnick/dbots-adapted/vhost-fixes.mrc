; ==========================================================================
; UnrealIRCd 6 adaptation -- makes NickServ's VHOST ("ip virtual") both
; the admin command (VHOST <nick> <host>) and the self-service one
; (SET VHOST <host>, via nicksetvhost) actually change what other users
; see, not just dBOTS' own database.
;
; WHY THIS WAS NEEDED: exactly the same root cause as +r
; (plus-r-modes.mrc): under the original UDB architecture, the ircd
; itself applied the vhost when it saw dBOTS' `a.t N::nick::V host`
; database-sync line (the same DB-sync mechanism documented as now a
; harmless no-op in DBOTS-MIGRATION.md "parte 3", since UnrealIRCd 6
; doesn't understand the raw `DB` command it's built on). dBOTS itself
; never called a live host-changing command -- confirmed by reading
; both `nickserv.vhost` and `nicksetvhost` in the real, unmodified
; ni.mrc: neither one ever touches the live connection.
;
; Unlike +r and GLINE, this one needed NO bridge and NO permission
; workaround: CHGHOST is already CMD_USER in stock UnrealIRCd 6 (see
; src/modules/chghost.c), gated only by the `client:set:host`
; operclass permission, which the `netadmin` parent operclass (that
; `dbots-service` inherits from) already grants. It only needed to
; actually be called.
;
; LIVE-TESTED: after these three additions, `/msg NiCK VHOST <nick>
; <host>` (admin) and `/msg NiCK SET VHOST <host>` (self-service) both
; now show up immediately in `/whois <nick>` for every other user on
; the network, not just in dBOTS' own database.
; ==========================================================================

; ---- ni.mrc, alias nicksetvhost (self-service SET VHOST -- $1 here is
; $o, the caller's full "nick!user@host" mask, passed in by whichever
; alias dispatches NickServ's SET command) -- one line added right
; after the existing database-sync line, using the bare nick
; ($gettok($1,1,33)) since CHGHOST resolves its target the same way any
; IRC command does (by nick, not by mask):
;
; Before:
;     a.t N:: $+ $1 $+ ::V $2
;     g.db nickserv\ $+ $r.c($1) configuracion vhostsf $2
; After:
;     a.t N:: $+ $1 $+ ::V $2
;     s : $+ $nickserv CHGHOST $gettok($1,1,33) $2
;     g.db nickserv\ $+ $r.c($1) configuracion vhostsf $2

; ---- ni.mrc, alias nickserv.vhost (admin VHOST <nick> <host>/DEL
; command -- $d(5) here is already the bare target nick, no mask to
; strip) -- one line added in EACH branch:
;
; SET branch, before:
;   a.t N:: $+ $d(5) $+ ::V $d(6)
;   g.db nickserv\ $+ $r.c($d(5)) configuracion vhostf $d(6)
; SET branch, after (only apply live if the target is actually online --
; VHOST can be set on an offline registered nick too, for when they next
; connect, dBOTS' own database-side behavior for that is unchanged):
;   a.t N:: $+ $d(5) $+ ::V $d(6)
;   g.db nickserv\ $+ $r.c($d(5)) configuracion vhostf $d(6)
;   if ( $n.c($d(5)) == si ) { s : $+ $nickserv CHGHOST $d(5) $d(6) }
;
; DEL branch, before:
;   a.t N:: $+ $d(5) $+ ::V
;   b.db nickserv\ $+ $r.c($d(5)) configuracion vhostf
; DEL branch, after (reverts the live display to the target's own real
; host/IP, read from dBOTS' usuarios.db -- see sockets-bootstrap.mrc for
; why that now holds the real host, not the cloak):
;   a.t N:: $+ $d(5) $+ ::V
;   if ( $n.c($d(5)) == si ) { s : $+ $nickserv CHGHOST $d(5) $gettok($l.db(usuarios.db,usuarios,$r.c($d(5))),2,32) }
;   b.db nickserv\ $+ $r.c($d(5)) configuracion vhostf
