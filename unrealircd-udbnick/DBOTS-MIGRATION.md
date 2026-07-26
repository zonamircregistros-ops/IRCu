# Adaptar dBOTS a UnrealIRCd 6.2.6 — auditoria completa y guia de migracion

Este documento es el "de arriba a abajo" real: descargue y lei los 15
ficheros de [dBOTS](https://github.com/juanjiyo/dBOTS/tree/main/dbots)
(`dbots.conf`, `remote.ini`, `sistema/*.mrc`, `firstrun.mrc`) y el codigo
fuente actual de UnrealIRCd 6 (`unrealircd/unrealircd`, rama
`unreal60_dev`) para saber exactamente que hace falta.

## El hallazgo critico (esto cambia todo el planteamiento)

**dBOTS no es un bot oper normal. Es un paquete de servicios completo
(NickServ/ChanServ/OperServ/etc.) escrito en mIRC que se conecta al ircd
como un SERVIDOR ENLAZADO**, no como un cliente. La prueba esta en
`sistema/sockets.mrc` linea 1-11:

```
on 1:sockopen:dbots: {
  ...
  s PASS %conf.clink
  s PROTOCTL $+(UDB,$l.conf(otras,udb),=,%conf.servidor) TKLEXT
  s SERVER %conf.servidor 1 : $+ %conf.desc
  s : $+ %conf.servidor SERVER $l.conf(modulosserv,sname) 2 :Servidor de modulos
}
```

Eso es el handshake de enlace servidor-servidor **de antes de que
existieran los SID** (`PASS`/`PROTOCTL`/`SERVER` sin ningun `SID`).
`dbots.conf` lo confirma sin ambiguedad:

```ini
[servidor]
ip=127.0.0.1
puerto=4400
servidor=deep.space
clink=openaccess
...
[otras]
unreal=Unreal3.2.8+UDB-3.6.1es
udb=3.6.1
```

Y define **11 pseudo-clientes de servicio** (`CeNTeR`, `OPeR`, `GLoBaL`,
`PRoXy`, `NoTiCiaS`, `HeLP`, `NiCK`, `MeMO`, `CHaN`, `CReG`, `SHaDoW`) que
el propio dBOTS introduce en la red como si fuera un servidor real
presentando sus usuarios en el burst inicial.

**UnrealIRCd exige SID para enlazar servidores desde hace muchas versiones
(4.x en adelante). Un handshake sin SID como el de arriba sera rechazado
por un 6.2.6 antes de llegar a nada -- dBOTS, tal cual esta escrito en
GitHub hoy, no puede enlazar a un Unreal moderno. Punto.** Esto es
independiente de cualquier otra cosa (sintaxis de `/NICK`, numericos,
etc.) -- es un bloqueo de protocolo en el primer paquete que se envia.

## La decision que tome (con el poder que me diste)

Reescribir en mIRC el protocolo S2S moderno de Unreal (SID, `UID` para
presentar usuarios, negociacion `PROTOCTL` actual, fin de sincronizacion,
etc.) es tecnicamente posible pero es re-implementar, en un lenguaje de
scripting no pensado para ello, una porcion no trivial del protocolo
interno de un ircd -- alto riesgo (un fallo ahi puede desincronizar o
tirar el servidor) para un beneficio que se consigue mas barato de otra
forma.

**En vez de eso: dBOTS deja de enlazar como servidor y sus 11 personas de
servicio pasan a conectarse cada una como un cliente IRC normal
(`NICK`/`USER`), que despues hace `/OPER` para ganar privilegios.** mIRC
ya sabe hacer conexiones de cliente normales -- lo demuestra el propio
`sockets.mrc` en el socket `ayuda.online` (linea 640: `NICK`, `USER`,
`JOIN` estandar). Es el mismo patron, aplicado a los 11 servicios.

Con esto, **el 95% de la logica de negocio de dBOTS no cambia en
absoluto** (`ni.mrc`, `ch.mrc`, las bases de datos `.db`, los alias de
comandos, todo eso sigue igual) -- lo unico que cambia es la capa mas
baja: como se abre la conexion y como se piden los "poderes" que antes
venian gratis por ser un servidor enlazado.

## Tabla de comandos: que necesita cada uno

Verifique esto leyendo el codigo fuente real de cada modulo de Unreal 6
(no por analogia con la version 3.2.8), en concreto si su `CommandAdd()`
incluye `CMD_USER` (utilizable por un oper conectado como cliente normal)
ademas de `CMD_SERVER`:

| Comando dBOTS | En Unreal 6.2.6 | Que hacer |
|---|---|---|
| `SAJOIN`, `SAPART`, `SAMODE` | `CMD_USER` ya incluido | Usar tal cual, como oper |
| `SVSJOIN`, `SVSPART`, `SVSKILL` | `CMD_USER` ya incluido | Usar tal cual, como oper |
| `CHGHOST`, `CHGIDENT`, `CHGNAME`/`SVSNAME` | `CMD_USER` ya incluido | Usar tal cual, como oper |
| `SETHOST`, `SETIDENT`, `UMODE2` | `CMD_USER` (solo sobre uno mismo) | Usar tal cual |
| `SVSO` (dar oper via cuenta de servicios) | `CMD_USER` ya incluido | Usar tal cual, como oper |
| `SQUIT` | `CMD_USER` ya incluido | Usar tal cual, como oper (netadmin) |
| `GLINE`/`GZLINE`/`ZLINE`/`SHUN`/`TKL` | Comandos de oper estandar | Usar tal cual, como oper |
| `SVSNICK` | **Solo `CMD_SERVER`** | **`DBOTSSVS SVSNICK <nick> <nuevonick>`** (modulo puente) |
| `SVSSILENCE` | **Solo `CMD_SERVER`** | **`DBOTSSVS SVSSILENCE <nick> <+m/-m ...>`** |
| `SVSNOLAG`/`SVS2NOLAG` | **Solo `CMD_SERVER`** | **`DBOTSSVS SVSNOLAG <nick> <+/->`** |
| `SWHOIS` | **Solo `CMD_SERVER`** | **`DBOTSSVS SWHOIS <nick> <+/-> <tag> <prioridad> [texto]`** |
| `SVSMODE`/`SVS2MODE` (modos de **usuario** sobre otro nick, ej. `+kB`) | **Ya no existe como comando** (ni CMD_SERVER); `SAMODE` es solo de **canal** | **`DBOTSSVS SVS2MODE <nick> <+modos-usuario>`** |
| `PASS`/`PROTOCTL`/`SERVER` (bootstrap de enlace) | No aplica -- no hay enlace | Sustituido por `NICK`/`USER`/`OPER`, ver mas abajo |
| `EOS` (fin de sincronizacion tras el burst) | No aplica -- no hay burst que sincronizar | Eliminar ese manejo, no hace falta |
| `NICK` usado para *presentar* las 11 personas via burst de servidor | No aplica | Cada persona hace su propio `NICK`/`USER` de cliente al conectar su socket |
| `SVSTIME` (sincronizar hora entre servidores) | No aplica a un cliente | Eliminar esa llamada (`op.mrc` linea 325), no tiene sentido fuera de un enlace real |
| `SVSNOOP` (quitar oper a TODO el mundo en la red, panico) | Existe pero **deliberadamente no lo puenteo** | Ver aviso de seguridad abajo |
| `SVSFLINE`, `SVSCMDS` | No existen en Unreal 6 (extensiones propias de UDB, nunca se subieron a Unreal oficial) | Los usos que vi son solo listas de "que soporta este server" (`de.mrc` linea 161-173), no llamadas reales -- no hace falta nada |

**Aviso de seguridad sobre `SVSNOOP`**: es el interruptor de panico que
quita privilegios de IRCop a *todo el mundo en la red* de golpe. El
modulo puente (`src/dbotsbridge.c`) NO lo expone a proposito. Si algun
`.mrc` de dBOTS lo llega a invocar automaticamente en algun flujo,
elimina esa llamada -- no tiene sustituto aqui ni deberia automatizarse.

## Que construi -- y ya COMPILE Y PROBE de verdad, no solo revise

1. **`src/udbnick.c`**: el truco `/NICK nick:clave` funciona igual sin
   importar si dBOTS esta enlazado como servidor o conectado como
   cliente, porque es una funcion propia del ircd.
2. **`src/dbotsbridge.c`**: comando `DBOTSSVS` con subcomandos
   `SVSNICK`, `SVSSILENCE`, `SVSNOLAG`, `SWHOIS`, `SVS2MODE`/`SVSMODE`,
   protegido tras el permiso de operclass `dbots:svs`. Reutiliza,
   literalmente, la misma logica interna que los modulos oficiales
   `svsnick.c`/`svssilence.c`/`svsnolag.c`/`swhois.c` de Unreal 6.
   Reenvia una vez al servidor correcto si el objetivo esta en otro nodo
   de una red hub+leaf (logica presente y basada en el mismo patron del
   codigo oficial, pero sin poder probarla en vivo con dos servidores
   reales en este entorno).

**Ambos, compilados de verdad** contra UnrealIRCd 6.2.7-git (rama
`unreal60_dev`) y **probados en vivo con 18 casos de prueba
automatizados** contra un ircd real corriendo -- 18/18 pasan, incluyendo
los 5 subcomandos de `DBOTSSVS` ejecutados desde una sesion realmente
opereada. Ver "Pruebas reales" mas abajo para el detalle completo y como
reproducirlo. En el camino encontre y corregi un bug real (orden de
`CommandOverrideAdd()` entre `MOD_INIT`/`MOD_LOAD`) que solo aparecio al
compilar contra las cabeceras reales -- exactamente el tipo de cosa que
no se detecta solo leyendo codigo.

## Configuracion necesaria en UnrealIRCd

```
loadmodule "third/udbnick";
loadmodule "third/dbotsbridge";

operclass dbots-service {
	permissions {
		dbots { svs; }
		sacmd { sajoin; sapart; samode; }
		/* añade aquí sajoin/sapart/samode/etc. si tu operclass base
		   no las trae ya heredadas */
	};
};

oper dbots_nickserv {
	class clients; /* o la que uses para bots locales */
	operclass dbots-service;
	password "cambia-esto";
	mask *@127.0.0.1; /* o la IP real donde corre mIRC/dBOTS */
};
/* repite un bloque oper{} por cada una de las 11 personas, o comparte
   uno solo si todas conectan desde el mismo host y no te importa que
   compartan el mismo nombre de cuenta de oper */
```

## Plantilla de bootstrap de conexion (sustituye el handshake de servidor)

Esto sustituye las lineas 1-11 de `sistema/sockets.mrc`. Ejemplo completo
para UNA persona (NickServ / `NiCK`); las otras 10 siguen el mismo patron
cambiando nick/usuario/config:

```
on 1:sockopen:dbots_nickserv: {
  if ($sockerr) { echo -a No se pudo conectar NickServ: $sock($sockname).wsmsg | return }
  sockwrite -tn dbots_nickserv NICK $l.conf(nickserv,nick)
  sockwrite -tn dbots_nickserv USER $l.conf(nickserv,ident) 0 * : $+ $l.conf(nickserv,realname)
}

on 1:sockread:dbots_nickserv: {
  var %datos
  sockread %datos
  if ($sockerr) { return }
  ; PING del servidor -- hay que responder o te desconecta
  if ($gettok(%datos,1,32) == PING) { sockwrite -tn dbots_nickserv PONG $gettok(%datos,2,32) | return }
  ; Numerico 001 = bienvenida, ya estamos "dentro": ahora pedimos OPER
  if ($gettok(%datos,2,32) == 001) {
    sockwrite -tn dbots_nickserv OPER dbots_nickserv %conf.operpass
  }
  ; Confirmacion de OPER (numerico 381) -- a partir de aqui SAMODE,
  ; SVSJOIN, DBOTSSVS, etc. ya funcionan
  if ($gettok(%datos,2,32) == 381) {
    sockwrite -tn dbots_nickserv MODE $l.conf(nickserv,nick) +iBdH
  }
  ; --- a partir de aqui, el resto de ni.mrc (nickserv.procesa, etc.)
  ; se engancha exactamente igual que ya lo hace, solo que ahora %datos
  ; llega de una conexion de cliente normal en vez de un enlace ---
}
```

Puntos a tener en cuenta al replicar esto para las otras 10 personas:

- Cada persona es **un socket mIRC independiente** (no se puede tener 11
  nicks simultaneos desde una sola conexion de cliente IRC). El fichero
  `dbots.conf` ya tiene una seccion `[nombreserv]` por persona con
  `nick`/`ident`/`vhost`/`realname` -- son los valores a usar en el
  `NICK`/`USER` de cada bootstrap.
- El campo `modos=+iorKkBdH` de cada seccion en `dbots.conf` trae una
  `o` (oper) que ya no se puede pedir por `MODE`, sale de haber hecho
  `/OPER` correctamente -- quita la `o` de esa cadena antes de mandarla
  por `MODE`.
- `vhost=-` en varias secciones: si quieres el vhost cosmetico de cada
  servicio, hazlo con `/CHGHOST` (ya `CMD_USER`) justo despues del OPER,
  no hace falta nada especial.

## Pruebas reales (actualizacion: ya no es solo teoria)

Compile UnrealIRCd 6.2.7-git de verdad (rama `unreal60_dev` de
`unrealircd/unrealircd`, la continuacion de la 6.2.6) con `udbnick.c` y
`dbotsbridge.c` puestos en `src/modules/third/`, lo arranque en una red
de pruebas local, y ejecute 18 casos de prueba automatizados de extremo
a extremo con un cliente IRC en Python (raw sockets, sin dependencias)
contra el ircd real -- incluyendo los 5 subcomandos de `DBOTSSVS`
(`SWHOIS`, `SVS2MODE`, `SVSNICK`, `SVSSILENCE`, `SVSNOLAG`) ejecutados
desde una sesion realmente opereada. **18/18 pasan.** Detalles, como
reproducirlo, y un bug real que encontre y corregi
(`CommandOverrideAdd()` llamado en el hook de modulo equivocado) estan en
el README de `udbnick` bajo "Pruebas reales realizadas".

Lo que ESO prueba: el lado ircd (los dos modulos C) funciona de verdad,
no solo "debería compilar". Lo que NO prueba: no hay forma de correr
mIRC (software Windows propietario) en este entorno Linux, asi que el
lado mIRC de la conversion (lo que sigue en esta seccion) no lo pude
ejecutar boton a boton contra dBOTS real.

## Intente convertir ni.mrc de verdad -- y encontre que el hueco es mas
## profundo de lo que la tabla de arriba sugiere

Antes de tocar nada me lei `sistema/ni.mrc` linea a linea (el handler
`on 1:sockread:dbots:` completo, 1588 lineas) para hacer la conversion
de verdad, no solo aplicar la tabla mecanicamente. Encontre DOS
problemas estructurales adicionales que la tabla de comandos NO cubre,
y que cualquier conversion tiene que resolver antes de que valga la
pena tocar una sola linea de los 15 ficheros:

**1. El envio "en nombre de otro" ya no existe.** Casi cada respuesta de
NickServ en el codigo original tiene esta forma:

```
s : $+ $nickserv %conf.metodo $1 :mensaje
```

Eso es "manda, con el prefijo `:NiCK!-@- PRIVMSG destino :mensaje`
forjado a mano" -- posible unicamente porque dBOTS habla como un
SERVIDOR enlazado, que puede introducir el prefijo que quiera para
cualquiera de sus pseudo-usuarios. **Un cliente normal no puede forjar su
propio prefijo** -- el ircd lo pone el solo segun quien esta conectado de
verdad en ese socket. Bajo el modelo cliente+oper la misma linea pasa a
ser simplemente `sockwrite -tn dbots_nickserv PRIVMSG $1 :mensaje`, sin
prefijo manual. Esto no es un problema en si (de hecho es una
simplificacion), **pero aparece en cientos de sitios a lo largo de los
15 ficheros**, no solo en los 5 comandos "puente" de la tabla -- es el
patron de comunicacion mas usado en todo dBOTS, y cada aparicion hay que
tocarla.

**2. El cacheo pasivo de la red entera no tiene equivalente para un
cliente normal.** Ademas de los mensajes dirigidos a NickServ, el mismo
handler tambien procesa, para CUALQUIER usuario de la red (no solo el
que le escribe a NickServ):

- `NICK`/`QUIT`/`KILL` globales, para mantener una cache interna de
  usuarios (linea 12-14: `c.n`/`s.u`).
- `SETIDENT`/`SETNAME` globales, para reflejar cambios de ident/nombre
  en la base de datos propia (linea 33-42).
- Respuestas al protocolo `DBQ`/`DB` propio de UDB (numerico 339 y
  comando `DB`, linea 43-63) -- consultas/sincronizacion directa contra
  la base de datos `nicks.udb` del ircd.

Todo eso funciona en el original **solo porque, al ser un servidor
enlazado, dBOTS ve TODO el trafico de la red** (cada NICK, QUIT, KILL,
cambio de modo de cualquiera, en cualquier canal). Un cliente normal --
por diseño, y por privacidad/escala -- NO recibe ese trafico salvo que
comparta canal con cada usuario. No hay forma de "traducir" estas
lineas a un comando equivalente porque el problema no es de sintaxis,
es que **la fuente de datos entera deja de existir** bajo el modelo
cliente+oper.

El punto 3 (DBQ/DB) ya no aplica de todas formas con esta arquitectura:
`udbnick.c` es su propia base de datos autosuficiente, no sincroniza con
UDB porque no hay UDB -- esas ~20 lineas son codigo muerto a eliminar,
no a traducir. Los puntos 1 y 2 si son trabajo real:

- El punto 1 (des-prefijar los envios) es mecanico pero extenso --
  cientos de sitios, cambio de patron simple, riesgo bajo por repeticion
  pero alto en volumen.
- El punto 2 (cache global) necesita **rediseño real, no traduccion**:
  la alternativa razonable en Unreal 6 es IRCv3 `MONITOR` (consultar
  el estado online/offline de una lista concreta de nicks, sin snoopear
  toda la red) para lo que hoy hace el cacheo de NICK/QUIT, y aceptar
  que SETIDENT/SETNAME globales sencillamente no se pueden replicar sin
  que NickServ este en todos los canales -- o sin resignarse a que ese
  dato se quede desactualizado hasta el proximo `/WHOIS`.

## Por que no genero un `ni.mrc` "convertido"

Dado lo anterior, no voy a fabricar un fichero que diga "ni.mrc
adaptado" sin poder ejecutarlo contra un mIRC real -- encontre demasiada
incertidumbre estructural (el propio mecanismo de nombrado de sockets de
dBOTS, via `aliases.sys`, no lo pude terminar de trazar con confianza)
como para presentar miles de lineas reescritas a ciegas como si
estuvieran verificadas. Eso seria writing code that looks done but isn't
-- exactamente lo que quiero evitar.

**Lo que si esta terminado, compilado y probado de verdad de extremo a
extremo es el lado ircd** (`udbnick.c` + `dbotsbridge.c`, ver "Pruebas
reales" arriba). Es la mitad dificil, la que puede desincronizar o
tirar el servidor si esta mal, y la que no se puede improvisar en
produccion. El lado mIRC, con los tres hallazgos de este documento
(protocolo de enlace obsoleto, comandos SVS* sin CMD_USER, y ahora estos
dos problemas estructurales de comunicacion+cacheo), tiene ya un mapa
mucho mas preciso de por donde hay que cortar -- pero la conversion
mecanica de los ~9000 lineas restantes es trabajo que pide iterar contra
un mIRC real, cosa que este entorno no tiene.
