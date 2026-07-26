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

## Que construi y esta listo para compilar/revisar

1. **`src/udbnick.c`** (de la entrega anterior): sigue siendo valido e
   independiente de todo esto -- el truco `/NICK nick:clave` funciona
   igual sin importar si dBOTS esta enlazado como servidor o conectado
   como cliente, porque es una funcion propia del ircd.
2. **`src/dbotsbridge.c`** (nuevo): comando `DBOTSSVS` con subcomandos
   `SVSNICK`, `SVSSILENCE`, `SVSNOLAG`, `SWHOIS`, `SVS2MODE`/`SVSMODE`,
   protegido tras el permiso de operclass `dbots:svs`. Reutiliza,
   literalmente, la misma logica interna que los modulos oficiales
   `svsnick.c`/`svssilence.c`/`svsnolag.c`/`swhois.c` de Unreal 6 -- no
   me lo he inventado, es la misma implementacion con la puerta de
   entrada cambiada de "solo servidores" a "opers con este permiso".
   Reenvia una vez al servidor correcto si el objetivo esta en otro nodo
   de una red hub+leaf.

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

## Lo que falta por hacer (y por que no lo teleport a "hecho")

No he reescrito los ~200 puntos donde los 15 ficheros `.mrc` originales
mandan `SVSMODE`/`SVSNICK`/`SVSSILENCE`/etc. directamente por el socket
de enlace (`ce.mrc`, `ch.mrc`, `de.mrc`, `op.mrc`, `gl.mrc`, etc.).
Con la tabla de arriba, cada punto se traduce mecanicamente (cambia el
nombre del comando, en los 5 casos "puente" antepon `DBOTSSVS `), pero
son cientos de sitios en miles de lineas de mIRC que no puedo verificar
sin una red de pruebas real con UnrealIRCd 6.2.6 + dBOTS corriendo de
verdad -- y un error de sintaxis silencioso en mIRC no siempre se nota
hasta que el caso concreto se dispara en produccion.

Lo que SI esta completo y lo mas verificado que he podido dejarlo sin
compilarlo (misma salvedad que en el README principal: no tengo aqui
toolchain de UnrealIRCd para compilar de verdad):

- El hallazgo de que el protocolo actual de dBOTS no enlaza, punto.
- La estrategia de conversion (cliente+oper en vez de servidor enlazado).
- El modulo `dbotsbridge.c`, que cierra command-by-command cada hueco
  real que encontre (no generico, cada subcomando esta verificado contra
  el modulo oficial equivalente de Unreal 6).
- La plantilla de bootstrap y la tabla de traduccion completa para que
  el resto sea trabajo mecanico guiado, no adivinado.

Si quieres que seleccione un fichero `.mrc` en concreto (por ejemplo
`ni.mrc`, que es el que mas nos importa por ser NickServ) y lo reescriba
entero linea a linea aplicando esta tabla, dimelo y lo hago a
continuacion -- prefiero hacerlo fichero a fichero con tu visto bueno en
el primero, en vez de convertir los 15 a ciegas y arriesgarme a propagar
el mismo error de traduccion 200 veces.
