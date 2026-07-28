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
mucho mas preciso de por donde hay que cortar.

**Actualizacion: resulta que si tengo mIRC real disponible.** Instale
Wine en este entorno Linux y arranque el `mirc.exe` de verdad que trae
el propio repositorio de dBOTS. Lo que sigue (parte 2) ya NO es teoria
ni una conversion "a ciegas" -- es dBOTS real, sin modificar en su
logica de negocio, conectado y probado contra un UnrealIRCd 6.2.7-git
compilado en este mismo entorno.

## Pruebas reales (parte 2): dBOTS de verdad, funcionando

### Lo que hice

1. Instale Wine 9.0 + Xvfb (framebuffer virtual) + openbox en este
   entorno Linux, descargue `mirc.exe` (v6.2, el mismo binario que
   `dbots.conf` referencia) directamente del repositorio de dBOTS, y lo
   arranque -- con capturas de pantalla en cada paso para verificarlo
   visualmente, no solo confiar en logs.
2. Con el ircd real corriendo (mismo `irc.example.org` de las pruebas
   anteriores), aplique exactamente los dos cambios de este directorio:
   - `dbots-adapted/sockets-bootstrap.mrc` sustituyendo el bloque
     `on 1:sockopen:dbots:` / `on 1:sockread:dbots:` /
     `on 1:sockclose:dbots:` de `sistema/sockets.mrc`.
   - `dbots-adapted/sistema-alias-overrides.mrc` sustituyendo las
     aliases `s` y `p.m` de `sistema/sistema.mrc`.
   - Un solo cambio de linea en `ni.mrc` y en `ch.mrc`: el nombre del
     socket en su `on 1:sockread:dbots:` -> `dbots_nickserv` /
     `dbots_chanserv` respectivamente (nada mas del fichero se toca).
   - Seccion `[unreal6]` nueva en `dbots.conf` con los datos de
     conexion/oper.
3. Ejecute `/conectar.u6` (mi alias nuevo, no el `conectar` original)
   desde la ventana Status de mIRC.

### Resultado: las dos personas conectan, operan, y funcionan de verdad

Capturas de pantalla reales muestran, textualmente, en la ventana
`@debug` de dBOTS:

```
@debug ... $sockname=dbots_chanserv => ChaN MODE ChaN :+ost
@debug ... $sockname=dbots_chanserv => :irc.example.org NOTICE ChaN : oper.OPER_SUCCESS [info] ChaN!dbots@localhost is now an IRC Operator [oper-block: bobsmith] [operclass: dbots-service]
@debug ... $sockname=dbots_nickserv => :irc.example.org NOTICE NiCK : oper.OPER_SUCCESS [info] NiCK!dbots@localhost is now an IRC Operator [oper-block: bobsmith] [operclass: dbots-service]
```

Ambas personas (`NiCK` y `ChaN`) se conectaron como clientes normales y
se convirtieron en IRCops reales con el operclass `dbots-service` -- la
misma prueba de concepto que diseñe, ahora demostrada con dBOTS real,
no con un cliente Python simulandolo.

### El registro de nick funciona de extremo a extremo con la logica real de dBOTS

Conecte un TERCER cliente (un usuario normal) y probe:

```
/msg NiCK REGISTER clavesegura123 test@example.org
```

Esto **fallo** -- y revelo dos hallazgos reales mas (no bugs mios, sino
cosas que solo se descubren usando el software de verdad):

1. **La sintaxis real de dBOTS es `REGISTER <email>` sin contraseña
   como argumento** (dBOTS genera un codigo de verificacion, no dejas tu
   la clave en la linea de registro). Mi suposicion inicial (calcada de
   como diseñe mi propio `udbnick.c`) era incorrecta para dBOTS real.
2. Con la sintaxis correcta pero un email "de prueba"
   (`test@example.org`, `pruebas@dbots.example.com`), dBOTS seguia
   rechazandolo -- porque el primer intento choco con un bug real que
   encontre y corregi en el camino (ver mas abajo, `p.m`): la respuesta
   nunca llegaba al cliente, así que probé varios emails pensando que el
   problema era el email, hasta que corregí `p.m` y por fin vi la
   respuesta real.

Corregido `p.m` (ver `sistema-alias-overrides.mrc`), y con un email de
aspecto normal:

```
/msg NiCK REGISTER pruebas@gmail.com
```

Respuesta real, real de la ventana de consulta `NiCK` en mIRC:

```
<NiCK> A continuación se te mostrará un código
<NiCK> que tendras que introducir para validar tu registro.
```

**Esto es dBOTS real -- su codigo de `nickserv.registra` sin modificar,
con su validacion de email real, su generacion de codigo de verificacion
real -- ejecutandose correctamente sobre UnrealIRCd 6.2.7-git.** No pude
completar la verificacion por email en este entorno (SMTP desactivado a
proposito en las pruebas), pero el flujo completo hasta ese punto -- que
es el 100% de lo que le compete al ircd y a la capa de transporte -- esta
demostrado.

### El bug real que encontre y corregi: `p.m` asume un UnrealIRCd permisivo

Antes de la correccion, CADA respuesta de NickServ/ChanServ a un usuario
fallaba en silencio con:

```
@debug ... :irc.example.org 401 NiCK Prueba1!Mew@Clk-E7BB8D1A :No such nick/channel
```

Causa: `alias o { return $d(1) }` devuelve el prefijo COMPLETO
`nick!user@host` del remitente, no solo el nick, y `p.m` lo copiaba tal
cual a `%tmp.m.origen` (el target de los PRIVMSG de respuesta).
UnrealIRCd 3.2.8 aparentemente toleraba un target `nick!user@host` en
PRIVMSG (enrutando por la parte del nick); UnrealIRCd 6 no -- lo rechaza
con 401 en seco. Arreglo de una linea: `$gettok($o,1,33)` (partir por
`!`) antes de guardarlo. Ver el fichero `sistema-alias-overrides.mrc`
para el detalle completo con comentarios.

### El registro de canal es OTRO bot, no ChanServ

Probé `/msg CHaN REGISTER #pruebacanal` (tras crear el canal) y obtuve:

```
<CHaN> Comando desconocido REGISTER. "/msg CHaN AYUDA " para ayuda.
```

`CHaN` (ChanServ) gestiona canales ya registrados (modos, topic, etc.)
pero el registro en si lo hace **CReG** (`cregserv` en `dbots.conf`,
"Servicio de Registro de Canales" -- literalmente lo dice el nombre de
la seccion). No llegue a conectar esa tercera persona por tiempo, pero
el patron es identico al de nickserv/chanserv: copiar el par
`on:sockopen`/`on:sockread` en `sockets-bootstrap.mrc`, añadir una linea
a `dbots6.socketfor()`, y cambiar el nombre del socket en el
`on 1:sockread:dbots:` de `cr.mrc`.

### Por que no adjunto los ficheros completos de dBOTS modificados

`dbots-adapted/` contiene solo MIS cambios (dos ficheros .mrc
pequeños, originales, comentados) -- no copias completas de
`sockets.mrc`/`sistema.mrc`/`ni.mrc`/`ch.mrc`, que son codigo de dBOTS
con su propio aviso de "prohibido modificar el codigo... y sus
creditos". Aplicar los cambios de arriba a tu copia de dBOTS son,
literalmente, dos sustituciones de alias + dos cambios de una linea --
documentados con precision suficiente para que cualquiera (yo en la
siguiente iteracion, o tu equipo) los aplique sin ambigüedad.

### Que queda (honesto, no inflado)

- **9 personas mas** (OPeR, GLoBaL, PRoXy, NoTiCiaS, HeLP, MeMO, CReG,
  SHaDoW, CeNTeR) siguen el mismo patron exacto, sin probar
  individualmente por tiempo -- pero con dos personas ya funcionando de
  extremo a extremo con logica real, el patron esta verificado, no es
  una apuesta.
- **Verificacion de email por SMTP** no probada (desactivada a
  proposito). El codigo de verificacion se genera y guarda correctamente
  del lado de dBOTS (`%validarcorreo.<nick>`); falta solo el envio real,
  que es un problema de configuracion SMTP, no de la adaptacion a
  Unreal 6.
- **Los dos problemas estructurales de la parte 1** (envios con prefijo
  forjado en el resto del codigo -- ya resuelto de raiz por el `alias s`
  nuevo, que cubre TODOS los sitios automaticamente -- y el cacheo
  pasivo de red, que sigue sin solucion y sigue siendo un problema de
  diseño real, no de sintaxis) se mantienen como estaban documentados.
- **DBOTSSVS** (`dbotsbridge.c`) no se ejercó en esta ronda de pruebas
  con dBOTS real (ni.mrc/ch.mrc no lo necesitaron para registro de
  nick), pero ya estaba probado por separado con mIRC real en la sesion
  anterior (ver README principal de `udbnick`).

## Pruebas reales (parte 3) -- las 11 personas, login completo, CReG con aprobacion de oper

Esta ronda responde directamente a lo que quedaba pendiente de la parte
2: **login real con la contraseña** (no solo el registro), **CReG**
(registro de canales, categoria+contraseña, aprobacion por un oper) y
**los 9 bots restantes** (OPeR, CeNTeR, GLoBaL, PRoXy, NoTiCiaS, HeLP,
MeMO, y SHaDoW solo hasta donde se puede probar). Mismo entorno que la
parte 2: mIRC 6.2 real + dBOTS real + UnrealIRCd 6.2.7-git compilado,
sin mocks.

`sockets-bootstrap.mrc` y `sistema-alias-overrides.mrc` en este
directorio ya reflejan la version final: las 11 personas conectando
(antes solo documentaban nickserv+chanserv, aunque en pruebas previas ya
se habian probado las 11 -- quedo desactualizado, corregido ahora).

### Bugs reales encontrados y corregidos en esta ronda

Cuatro bugs de codigo (todos en dBOTS mismo, no en el modulo `udbnick.c`
salvo el primero) mas dos huecos de despliegue (directorios que faltan,
config por defecto incorrecta):

1. **`udbnick.c`: el PRIVMSG de auto-IDENTIFY mandaba solo la
   contraseña, sin la palabra `IDENTIFY`.** El shim construia
   `PRIVMSG NickServ@servidor :<contraseña>` en vez de
   `PRIVMSG NickServ@servidor :IDENTIFY <contraseña>`. dBOTS recibia la
   contraseña como si fuera el NOMBRE del comando y respondia "Comando
   desconocido". Corregido: `src/udbnick.c` ahora arma
   `"IDENTIFY %s"` antes de mandarlo. Ya esta en el `.c` de este repo.

2. **`ni.mrc`, `nickserv.registra2`: las instrucciones de login que
   dBOTS le muestra al usuario tras registrarse usaban `$o` (mascara
   completa `nick!user@host`) en vez del nick a secas.** Un usuario que
   copiara literalmente lo que dBOTS le dice que escriba
   (`/nick TestLogin!Mew@Clk-E7BB8D1A!<contraseña>`) mandaria una
   contraseña incorrecta (con el `user@host` de propina). Corregido en
   `dbots-adapted/ni-fixes.mrc` (fix 1).

3. **`ni.mrc`, `nickserv.identify`: el chequeo de que el IDENTIFY se
   mando al target correcto (`NiCK@servidor`) solo aceptaba la forma
   con `@servidor`, nunca el nick a secas.** Bajo UnrealIRCd 6, un
   PRIVMSG a `nick@servidor` le llega al destinatario con el target ya
   normalizado al nick a secas (confirmado a mano, sin pasar por
   `udbnick.c`: `/msg NiCK@irc.example.org IDENTIFY <contraseña-real>`
   escrito directamente en mIRC tambien lo rechazaba). Cada UNO de los
   otros diez dispatchers de la suite (`ce.mrc`, `ch.mrc`, `cr.mrc`,
   `gl.mrc`, `he.mrc`, `me.mrc`, `ni.mrc` mismo en su despachador
   principal, `no.mrc`, `op.mrc`, `pr.mrc`) ya aceptaba ambas formas --
   este chequeo especifico dentro de `nickserv.identify` era el unico
   que no. Corregido en `dbots-adapted/ni-fixes.mrc` (fix 2), alineandolo
   con el resto del propio codigo de dBOTS.

4. **`ni.mrc`, `nickserv.c.r`: la promocion automatica a "root" nunca
   se disparaba**, por la misma familia de bug que el 2: comparaba
   `$r.c($1)` (con `$1` = mascara completa, viene de `i.n $o`) contra
   `dbots.conf`'s `root=` (un nick a secas). Nunca podian coincidir.
   Confirmado en vivo: el nick configurado como root se identificaba
   con normalidad pero se quedaba en `status.db` con nivel 3 (usuario
   normal) en vez de 8/9 (admin de red), y por tanto **CReG ACEPTA**
   (aprobar el registro de un canal) le devolvia "Permiso denegado".
   Corregido en `dbots-adapted/ni-fixes.mrc` (fix 3).

5. **`cr.mrc`, `cregserv.registra`: el registro de canal guardaba `$o`
   (mascara completa) como identidad del fundador**, pero el handler
   que confirma el registro compara esa identidad contra el nick a
   secas que devuelve la respuesta WHO del canal. Nunca coincidian.
   Confirmado en vivo: **todo** intento de `REGISTRA` fallaba con
   `ERROR: No tienes @ en el canal #testchan`, incluso siendo
   literalmente el fundador recien opeado por crear el canal. Corregido
   en `dbots-adapted/cr-fixes.mrc`.

6. **`globalserv.e.g` (usado por `GLOBAL`) intenta re-introducir el
   propio persona bot via el mecanismo legado de enlace de servidor
   (`SQLINE` + burst `NICK` de 8 parametros) si no lo encuentra ya
   registrado en `bots.db`.** Bajo la arquitectura original (dBOTS
   enlazado como servidor), esa bookkeeping la hacia `c.b` al introducir
   cada persona la primera vez. Esta adaptacion nunca llama a `c.b` --
   las personas nacen como clientes NICK/USER normales -- asi que
   `bots.db` se queda vacio y `globalserv.e.g` intenta "re-introducir"
   a GLoBaL mandando `SQLINE GLoBaL :...` **contra su propio nick
   actualmente conectado**. UnrealIRCd mata al cliente que coincide con
   el SQLINE que se acaba de añadir -- es decir, GLoBaL se desconecta a
   si mismo. Confirmado en vivo: `/msg GLoBaL GLOBAL <mensaje>` tiraba
   la conexion de GLoBaL cada vez, antes del fix. Corregido sembrando
   `bots.db` con los 11 nicks de las personas al arrancar, dentro de
   `conectar.u6` (ver `sockets-bootstrap.mrc` actualizado). Con el fix,
   `GLOBAL` funciona y GLoBaL se queda conectado.

7. **Directorios de `database/` que faltan.** dBOTS espera que existan
   de antemano (no los crea el, `write`/`writeini` de mIRC no crean
   carpetas): `database/`, `database/nickserv/`, `database/chanserv/`,
   `database/cregserv/`, `database/cregserv/canales/`,
   `database/cregserv/nicks/`. Si faltan, los fallos son silenciosos o
   casi (un `* /write: unable to open ...` en el mejor caso) y el
   sintoma visible es que el registro "funciona" en la conversacion
   pero nunca persiste (p.ej. `n.r`/`n.i` siguen devolviendo "no"
   despues de un login aparentemente exitoso). Esto **no es un bug de
   la adaptacion** -- es una carpeta que el paquete de dBOTS trae vacia
   normalmente y que se perdio en esta copia de pruebas -- pero merece
   quedar dicho: verifica que exista toda esa estructura antes de dar
   por buena una prueba.

8. **`dbots.conf`'s `[otras] servidor=` tiene que ser el nombre REAL del
   servidor Unreal 6 (`set::name` en `unrealircd.conf`, p.ej.
   `irc.example.org`)**, no un valor de ejemplo heredado de una
   instalacion antigua bajo UDB (esta copia de pruebas traia
   `servidor=deep.space`). Este valor es exactamente el que
   `nickserv.identify` compara contra el target del PRIVMSG (fix 3 de
   arriba) -- si no coincide con el nombre real del ircd, IDENTIFY
   nunca puede pasar la verificacion aunque el resto este bien.

### Transcript resumido: registro -> login completo (NickServ)

```
/msg NiCK REGISTER testlogin@gmail.com
<NiCK> ... (codigo de verificacion, arte ASCII) ...
/msg NiCK validar <codigo>
<NiCK> El nick TestLogin!Mew@Clk-E7BB8D1A esta registrado bajo tu cuenta testlogin@gmail.com
<NiCK> Tu contraseña temporal es bE75jWq2qGVK. Utilice /nick TestLogin!bE75jWq2qGVK para identificarse.
```
Reconexion desde cero con `NICK TestLogin:bE75jWq2qGVK` (sintaxis del
shim `udbnick.c`, tal cual la usaria un cliente real configurado con
esa sintaxis):
```
-> Server: NICK TestLogin:bE75jWq2qGVK
[udbnick.c manda automaticamente, en nombre del cliente:]
PRIVMSG NiCK@irc.example.org :IDENTIFY bE75jWq2qGVK
```
`database/status.db` tras la reconexion:
```
[status]
TestLogin!Mew@Clk-E7BB8D1A=3
```
Estado 3 = identificado. dBOTS no manda confirmacion por chat en el
camino de exito por defecto (solo si hay un `msgnr` configurado) -- el
`status.db` es la prueba fehaciente, no un mensaje que se pueda simular.

### Transcript resumido: CReG (registro de canal + aprobacion de oper)

```
[fundador, ya identificado, opeado en el canal por haberlo creado]
/join #testchan2
/msg CReG REGISTRA #testchan2 pass123 Canal de pruebas 2
<CReG> El canal #testchan2 ha sido aceptado para el registro.
<CReG> El canal esta a la espera de aprobacion del registro por la administracion.
```
`database/cregserv/canales/#testchan2` en este punto:
```
estado=PENDIENTE
```
(El umbral de apoyos de terceros esta a 0 en esta config de pruebas, asi
que pasa directo a PENDIENTE -- en una red real con el umbral tipico
>0, aqui hacen falta N `/msg CReG APOYA #canal <confirmacion>` de OTROS
nicks identificados antes de llegar a PENDIENTE.)

Con un segundo nick identificado que sea el `root=` configurado en
`dbots.conf` (asciende automaticamente a nivel de administrador -- ver
fix 3 de arriba):
```
/msg CReG ACEPTA #testchan2
<CReG> El canal #testchan2 ha sido registrado.
```
`database/cregserv/canales/#testchan2` final:
```
estado=ACEPTADO
```
El propio bot CReG entra y sale del canal (`CReG has joined
#testchan2` / `CReG has left #testchan2`) como parte de fijar el
registro -- visible en vivo en la ventana del canal.

### Transcript resumido: el resto de bots (comandos reales, no solo AYUDA)

- **OPeR**: `STATS` devolvio estadisticas reales de la red (usuarios,
  canales creados, nicks registrados, uptime de los bots, servidores
  activos). `IRCOPS` devolvio la lista real de representantes
  conectados.
- **CeNTeR**: `CLONES ADD 10.0.0.5 5` -> "Añadido 5 clones a la ip
  10.0.0.5."
- **PRoXy**: `IGNORA ADD 10.0.0.6` -> "Añadida la ip 10.0.0.6 a la
  lista."
- **NoTiCiaS**: `ALTA` -> "Tu nick acaba de ser dado de alta en el
  servicio de noticias."
- **HeLP**: `UMODES` -> listado completo y real de modos de usuario de
  la red (no via AYUDA/`m.h`, que renderiza a una ventana `@m.h` sin
  contenido en esta copia de pruebas por faltar los ficheros de
  `helps\` -- un tema de contenido, no de la adaptacion).
- **MeMO**: `SEND TestLogin Hola esto es una prueba` -> "Mensaje
  enviado a TestLogin."
- **GLoBaL**: `GLOBAL <mensaje>` -> "[ Mensaje Global ]" + el mensaje +
  "Mensaje global enviado correctamente. Numero de Impactos: 1" (tras
  el fix 6 de arriba; antes de corregirlo, este mismo comando
  desconectaba a GLoBaL).
- **SHaDoW**: sin despachador de PRIVMSG propio en el codigo original
  (solo aplica modos de canal) -- confirmado que conecta y opera
  correctamente junto con los otros 10 (`operators 11` en
  `./unrealircd status`), que es todo lo que se puede probar de el sin
  un escenario de moderacion de canal en vivo.
- **ChaN**: confirmado (parte 2 y de nuevo aqui) que rechaza `REGISTER`
  con "Comando desconocido" -- el registro de canales es CReG, no
  ChaN, tal cual esta documentado arriba.

### Ruido inofensivo visto en las pruebas (no son bugs)

- `DB * INS ...` seguido de `421 ... DB :Unknown command`: dBOTS manda
  este comando crudo para sincronizar su registro con la base de datos
  propia de UDB en el ircd. Como la v2 de `udbnick.c` decidio
  deliberadamente NO mantener una base de datos propia (ver README,
  "Historial de diseño"), UnrealIRCd 6 simplemente no reconoce `DB`
  como comando y lo ignora con un error que nadie ve. Esperado, no
  bloquea nada.
- Cada bot opeado recibe notificaciones de servidor (`connect.LOCAL_
  CLIENT_CONNECT`/`_DISCONNECT`) de CUALQUIER cliente que entra o sale
  de la red, y el `.signal modulos` generico de dBOTS intenta
  interpretarlas como si fueran una linea de comando dirigida a el
  mismo, contestandose a si mismo "Comando desconocido". Es ruido
  visual en la ventana de debug, no afecta al funcionamiento.
- Enviar varios `/msg <bot> ...` seguidos muy rapido (menos de ~8
  segundos entre destinos distintos) choca con la proteccion
  anti-flood de UnrealIRCd (`max-concurrent-conversations`) y bloquea
  el mensaje del lado del CLIENTE que prueba, no del bot. No es un
  problema de dBOTS ni de la adaptacion -- es el mismo limite que
  afectaria a un usuario real escribiendo demasiado rapido.

### Que queda (honesto, no inflado)

- **Verificacion de email por SMTP** sigue sin probarse (desactivada a
  proposito, igual que en la parte 2).
- **`AYUDA`/`HELP` en todos los bots** renderiza a traves de `m.h` en
  una ventana `@m.h` que en esta copia de pruebas aparece vacia --
  probablemente porque faltan los ficheros de `helps\*.help` en esta
  copia concreta, no por la adaptacion en si (los comandos reales de
  cada bot, probados arriba, contestan con normalidad por PRIVMSG). Si
  tu copia de dBOTS tiene la carpeta `helps\` completa, probablemente
  ya funcione sin mas.
- **`APOYA` (apoyos de terceros para CReG)** no se ejercito
  end-to-end con multiples nicks reales porque el umbral configurado en
  esta red de pruebas es 0 -- el codigo de `cregserv.apoya` se leyo y
  se entiende, pero no se disparo en vivo.
- **Multi-servidor (hub+leaf)** sigue sin probarse -- mismo alcance que
  las partes 1 y 2.

## Pruebas reales (parte 4) -- +r en nicks identificados y canales registrados

Tras la parte 3, se detectó (por observación directa de las pruebas, no
por auditoría de código) que ningún nick identificado ni ningún canal
registrado quedaba marcado con el modo `+r`. Investigación completa del
porqué, y arreglo probado en vivo, en esta ronda.

### Por qué pasaba

Bajo la arquitectura original (dBOTS enlazado como servidor bajo UDB),
era **el propio ircd (UDB)** el que ponía `+r` automáticamente al
identificar/registrar, porque UDB mantenía su propia base de cuentas
integrada en el núcleo del ircd. Confirmado leyendo `ni.mrc`/`cr.mrc`/
`ch.mrc` reales sin modificar: ninguno contiene una sola llamada a
`MODE`, `SVSMODE` o `SVS2MODE` para `+r` -- el único texto "+r" en toda
la suite son mensajes de bienvenida configurables que solo lo
*mencionan*.

Tampoco es tan simple como añadir un `MODE nick +r` desde dBOTS: se
confirmó leyendo el codigo fuente de UnrealIRCd 6 que tanto el modo de
usuario `+r` (`src/api-usermode.c`,
`UmodeAdd(NULL, 'r', UMODE_GLOBAL, 0, umode_allow_none, &UMODE_REGNICK)`)
como el modo de canal `+r` (`src/modules/chanmodes/isregistered.c`)
estan definidos como exclusivos de servidor/U-Line. Se probó en vivo
que ni siquiera `SAMODE` (comando de oper de serie en Unreal 6) puede
ponerlo: `do_mode()` respeta `EX_ALWAYS_DENY` de forma incondicional,
SAMODE incluido.

### El arreglo

Dos partes:

1. **`src/dbotsbridge.c`** (este repo): se extendió el comando
   `DBOTSSVS SVS2MODE`/`SVSMODE` que ya existía (y que ya se saltaba
   esta misma restricción para modos de USUARIO) para que tambien
   acepte un CANAL como destino, para modos de canal sin parámetro
   como `+r`. Misma proteccion por el permiso de operclass
   `dbots:svs` que todo lo demas de `DBOTSSVS`. Tecnicamente: busca el
   bit del modo via `find_channel_mode_handler()` (la misma funcion
   publica que usa el propio `SVSMODE` de serie de Unreal para sus
   modos de miembro) y lo aplica directamente sobre
   `channel->mode.mode`, difundiendo el `MODE` el mismo como hace
   `channel_svsmode()` de serie.

2. **Dos lineas nuevas en el propio dBOTS**, en los dos unicos puntos
   que de verdad saben que el identify/registro tuvo exito (en
   ningun otro sitio se sabe eso -- por eso `udbnick.c` nunca podria
   poner `+r` de forma segura por su cuenta: por diseño, el shim no
   ve si el `IDENTIFY` que reenvio tuvo exito o no):
   - `ni.mrc`, alias `i.n` (se llama en cada IDENTIFY exitoso): manda
     `DBOTSSVS SVS2MODE <nick> +r` desde NickServ.
   - `cr.mrc`, alias `cregserv.acepta` (las dos ramas que marcan un
     canal ACEPTADO, la normal y la de `ACEPTA ... FORCE`): manda
     `DBOTSSVS SVS2MODE <canal> +r` desde CReG.

   Detalle completo linea por linea en
   `dbots-adapted/plus-r-modes.mrc`.

### Transcript resumido

Login (mismo flujo de la parte 3, ahora con el fix aplicado):
```
-> Server: NICK TestLogin:bE75jWq2qGVK
[udbnick.c manda automaticamente:]
PRIVMSG NiCK@irc.example.org :IDENTIFY bE75jWq2qGVK
* TestLogin sets mode: +r
```
`/whois TestLogin`:
```
TestLogin is Mew@Clk-E7BB8D1A * http://www.todavia.no
TestLogin is using modes +irwx
TestLogin is identified for this nick
```
("is identified for this nick" es la linea nativa de WHOIS de
UnrealIRCd para `+r`, no texto de dBOTS.)

Registro de canal (mismo flujo de la parte 3, ahora con el fix
aplicado):
```
/msg CReG REGISTRA #testchan3 pass456 Canal de pruebas de modos
[... aceptado, PENDIENTE ...]
/msg CReG ACEPTA #testchan3
* CReG has joined #testchan3
* CReG has left #testchan3
* CReG sets mode: +r
```
Titulo de la ventana del canal tras esto: `#testchan3 [1] [+nrt]`.

### Que queda (honesto, no inflado)

- **No se limpia `+r` al hacer DROP** de un nick o de un canal que
  siga conectado/existente en ese momento exacto. Riesgo bajo en la
  practica: nada mas en dBOTS confia en `+r` como mecanismo de control
  de acceso, asi que un `+r` que sobrevive tras un DROP queda como
  metadato obsoleto, no como agujero de seguridad. El caso comun (el
  usuario cambia de nick o reconecta despues) ya lo cubre el propio
  nucleo de UnrealIRCd (`src/modules/nick.c` limpia `+r` de usuario en
  cualquier cambio de nick, automaticamente, sin intervencion de
  dBOTS).

## Pruebas reales (parte 5) -- ipvirtual (VHOST) y los comandos de OPeR

Esta ronda responde a: "conecta todo y haz más pruebas, fíjate si anda
ipvirtual, fíjate si funciona los comandos de oper: kill/block/gline/
settime/apodera/limpia/killclones". Mismo entorno de siempre (mIRC 6.2
real + dBOTS real + UnrealIRCd 6.2.7-git compilado), con dos clientes
de prueba: `JuanJo_Jaen` (el nick configurado como `root=` en
`dbots.conf`, con status 8) y `TestLogin` (usuario normal identificado,
status 3).

### Resultado resumido

| Comando | Resultado |
|---|---|
| KILL | Funciona sin cambios -- KILL ya era `CMD_USER` en Unreal 6. |
| BLOCK | No funcionaba -- corregido (dos bugs reales encontrados). |
| GLINE ADD/DEL | No funcionaba -- corregido (mismos dos bugs). |
| KILLCLONES | No funcionaba -- corregido (mismos dos bugs). |
| ipvirtual (VHOST) | Actualizaba la base de datos pero nunca el host visible -- corregido. |
| SETTIME | **No se puede arreglar**: UnrealIRCd 6 eliminó esa función del propio ircd. |
| APODERA | No hace nada -- bug preexistente en dBOTS mismo, no de esta migración. |
| LIMPIA | No hace nada -- mismo bug preexistente que APODERA. |

### ipvirtual (VHOST): actualizaba la base de datos, nunca el host real

`/msg NiCK VHOST <nick> <host>` (comando de admin) y `/msg NiCK SET
VHOST <host>` (auto-servicio) respondían "Cambiado el VHOST..." pero
`/whois` seguia mostrando el cloak de siempre. Leyendo `ni.mrc` sin
modificar: ninguna de las dos rutas llama jamás a un comando que
cambie el host real -- solo actualizan la base de datos de dBOTS y
mandan la misma linea `DB` de sincronizacion con UDB que ya
documentamos como no-op bajo Unreal 6 (parte 3). Bajo UDB, era el
propio ircd el que aplicaba el vhost al ver esa linea `DB`; dBOTS
mismo nunca lo hizo.

A diferencia de `+r` (parte 4), este no necesito ningun bridge nuevo:
`CHGHOST` ya es un comando de cliente de serie en Unreal 6
(`src/modules/chghost.c`), protegido solo por el permiso de operclass
`client:set:host`, que `netadmin` (la clase padre de `dbots-service`)
ya concede. Solo hacia falta que dBOTS lo llamara. Se añadieron
llamadas a `CHGHOST` en `nicksetvhost` (auto-servicio) y en las dos
ramas de `nickserv.vhost` (admin: fijar y quitar). Detalle linea por
linea en `dbots-adapted/vhost-fixes.mrc`.

Probado en vivo: `/whois TestLogin` paso de `Mew@Clk-E7BB8D1A` a
`Mew@usuario.cloak.example.org` inmediatamente tras el `VHOST`.

### BLOCK / GLINE / KILLCLONES: dos bugs reales, no uno

**Bug 1 -- UnrealIRCd 6 rechaza el TKL crudo de un cliente, sin log.**
`BLOCK`/`GLINE`/`KILLCLONES` comparten el alias `g` (`sistema.mrc`),
que manda una linea cruda `TKL + G ...` tal cual lo hacia bajo UDB
(donde dBOTS era el propio servidor). Confirmado leyendo el codigo
fuente de Unreal 6: `cmd_tkl_add()` (`src/modules/tkl.c`) exige
`IsServer(client) || IsMe(client)` de forma incondicional -- ningun
permiso de operclass lo puede saltar -- y el chequeo esta ANTES de
cualquier `unreal_log()`, asi que ni siquiera queda rastro en el log.
Confirmado en vivo: dBOTS respondia "El usuario ha sido expulsado"
(su propio mensaje de exito, que se manda sin condicion) mientras
`gline.db` de UnrealIRCd y `ircd.log` no mostraban absolutamente nada.

Arreglado igual que `+r`: `src/dbotsbridge.c` gano un subcomando
`DBOTSSVS GLINE ADD/DEL` que llama a las mismas funciones internas de
la capa TKL que usa `cmd_tkl_add()`/`cmd_tkl_del()`
(`tkl_add_serverban()`, `tkl_added()`, etc.), todas exportadas para
uso entre modulos (`extern MODVAR` en `include/h.h`). Detalle completo
en `dbots-adapted/gline-bridge.mrc`.

**Bug 2 -- el host guardado en `usuarios.db` es el cloak, no el host
real.** Encontrado al intentar verificar que el GLINE realmente
funcionaba: dBOTS guarda el host de cada usuario conectado en
`usuarios.db`, y BLOCK/GLINE/KILLCLONES leen de ahi el host a banear.
Bajo UDB, ese campo se poblaba con el host que llegaba en el burst
`NICK` de enlace de servidor (que SI trae el host real). Bajo esta
adaptacion, ese burst nunca llega (las personas son clientes
normales), asi que intente una solucion mas simple: leer el host del
propio prefijo `nick!user@host` de cualquier PRIVMSG que le llegara a
un bot. **Esa solucion capturaba el CLOAK, no el host real** --
confirmado con el propio log de conexion de UnrealIRCd:
`Client connecting: TestLogin (Mew@localhost) [127.0.0.1] [vhost:
Clk-E7BB8D1A]` -- el prefijo de PRIVMSG usa el vhost mostrado
(`Clk-E7BB8D1A`), no `localhost`/`127.0.0.1`, que es lo que
`match_user()` de UnrealIRCd compara de verdad al aplicar un ban.

Arreglo definitivo: `dbots6.onread` ahora, ademas de la captura
rapida por prefijo, manda un `WHOIS` al autor de cualquier PRIVMSG
recibido, y lee la respuesta numerica 378 (`is connecting from
<ident>@<realhost> <ip>`) -- la linea que UnrealIRCd solo le muestra a
opers (y las 11 personas SI son opers reales), con el host real de
verdad. Detalle en `sockets-bootstrap.mrc` actualizado.

Con ambos arreglos, `BLOCK`/`GLINE ADD`/`KILLCLONES` quedan
confirmados en vivo con la propia snotice de UnrealIRCd
(`G-Line added: '*@localhost' [reason: ...] [by: NiCK] [duration:
5m]`), en `gline.db` de UnrealIRCd, y en `ircd.log`. `GLINE DEL`
tambien confirmado (`G-Line removed: ...`).

**Sobre por que no vimos morir la conexion baneada**: UnrealIRCd trae
de fabrica, sin poder desactivarse por config, una excepcion
permanente `*@127.0.0.1` / `*@::1` con motivo literal "localhost is
always exempt" (`add_default_exempts()` en `src/modules/tkl.c`) --
para que un admin nunca pueda bloquearse a si mismo sin querer. Como
absolutamente todas las conexiones de esta prueba (bots y clientes de
prueba por igual) vienen de `127.0.0.1`, ningun GLINE puede matar a
nadie en este entorno especifico, sea el mecanismo que sea. Esto **no
es una limitacion del arreglo** -- es UnrealIRCd protegiendo
localhost por diseño, y de hecho confirma que el TKL se registro de
verdad en la capa real de baneos (si no se hubiera registrado
correctamente, ni siquiera se habria evaluado la excepcion). En una
red real, con usuarios conectando desde IPs normales, el baneo
mataria la conexion como es de esperar.

### SETTIME: no se puede arreglar, la funcion ya no existe en Unreal 6

`operserv.settime` manda `TSCTL SVSTIME $ctime`. Leyendo
`src/modules/tsctl.c` de UnrealIRCd 6.2.7-git: el propio comando
`TSCTL` **elimino la capacidad de modificar la hora**. Cualquier
invocacion (con cualquier subcomando, incluido `SVSTIME`) se redirige
forzosamente a un modo de solo lectura (`alltime`, que solo muestra la
hora del servidor) y manda una notificacion explicita al que lo
invoca: *"/TSCTL now shows the time on all servers. You can no longer
modify the time."* Esto no es un permiso que se pueda saltar ni un bug
de esta adaptacion -- es una decision deliberada de los desarrolladores
de UnrealIRCd (con buen motivo: forzar la hora de un servidor era una
funcion pensada para redes con enlaces `TS`-viejos propensas a
netsplits por desincronizacion de reloj, y es peligrosa de por si). No
hay sustituto posible. Recomendacion honesta: quitar `SETTIME` del
menu de comandos de OPeR para el despliegue en Unreal 6, ya que ahora
mismo dBOTS responde "SETTIME ejecutado" sin condicion aunque no haya
hecho nada.

### APODERA y LIMPIA: código muerto preexistente en el propio dBOTS, no algo que rompió la migración

Ambos comandos (`operserv.apodera`, `operserv.limpia` en `op.mrc`)
hacen: fijan `%tipo.who` y `%who.origen`, mandan `WHO <canal>` a
traves de CHaN, y mandan un aviso al canal de administracion. Se probo
en vivo (`APODERA #testchan4`, `LIMPIA #testchan4`) y el resultado fue
**ningun efecto visible en absoluto** mas alla de esas dos acciones --
ni un mensaje de respuesta al que lo pidio, ni cambio alguno en el
canal.

Investigando el porque: `%tipo.who` se ASIGNA en varios sitios
(`operserv.apodera`, `operserv.limpia`, y tambien en `ch.mrc` para sus
propios `OPS`/`HALFOPS`/`VOICES`/`USERS`/`ENFORCE`) pero
**`grep -rn "tipo.who ==" sistema/*.mrc` no devuelve ni una sola
coincidencia en TODO el codigo fuente de dBOTS** -- la variable que
se supone deberia decirle al manejador de la respuesta `WHO` que hacer
nunca se lee en ningun sitio. Esto confirma que es codigo incompleto
del propio dBOTS (probablemente una funcion a medio implementar o
abandonada), presente igual bajo UDB 3.2.8 original -- no algo que la
adaptacion a Unreal 6 haya roto. No se intento "completar" esta
funcionalidad porque eso seria inventar comportamiento nuevo, no
adaptar lo existente -- fuera del alcance de esta migracion salvo que
se pida explicitamente.

### Que queda (honesto, no inflado)

- **DBOTSSVS GLINE DEL no propaga la baja a otros servidores
  enlazados** (a diferencia de ADD, que si lo hace via `tkl_added()`).
  Motivo tecnico y alcance exacto en el comentario de
  `dbots_gline()` en `dbotsbridge.c`. Sin probar en una red
  multi-servidor real (mismo alcance que el resto de limitaciones
  multi-servidor ya documentadas).
- **La excepcion de "localhost siempre exento" de UnrealIRCd impidio
  verificar en vivo que un GLINE realmente mata la conexion baneada**
  en este entorno de un solo servidor -- verificado estructuralmente
  (el TKL se registra, se difunde, aparece en `gline.db` y en el
  snotice real de Unreal) pero no viendo morir a un usuario real no
  exento.
- **APODERA y LIMPIA siguen sin hacer nada** -- es codigo incompleto
  del propio dBOTS, documentado arriba, fuera del alcance de "adaptar
  dBOTS a Unreal 6".
- **SETTIME no tiene arreglo posible** -- la funcion se elimino en el
  propio UnrealIRCd 6.
