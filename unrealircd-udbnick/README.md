> **Actualizacion**: ademas del modulo `udbnick` de abajo, este directorio
> ahora incluye `src/dbotsbridge.c` y, sobre todo,
> **[`DBOTS-MIGRATION.md`](DBOTS-MIGRATION.md)** -- la auditoria completa
> de por que dBOTS, tal cual esta en GitHub, NO puede enlazar a
> UnrealIRCd 6.2.6 (usa un handshake de servidor sin SID, obsoleto desde
> hace años), y el plan concreto para adaptarlo (conectarlo como cliente
> oper en vez de como servidor enlazado). Empieza por ahi si tu objetivo
> es dBOTS funcionando, no solo el atajo `/NICK nick:clave`.

# udbnick — `/NICK usuario:contrasena` para UnrealIRCd 6.2.6

Modulo de terceros para UnrealIRCd 6.x que reproduce, sobre la API moderna,
la funcion concreta de [trocotronic/udb](https://github.com/trocotronic/udb)
(fork de UnrealIRCd 3.2.8 usado por redes estilo IRC-Hispano) que necesitan
los bots [dBOTS](https://github.com/juanjiyo/dBOTS/tree/main/dbots): poder
identificarte metiendo la clave en el propio `/NICK`:

```
/NICK Usuario:MiContrasena
/NICK Usuario!MiContrasena
```

## Como llegue a este diseno (contexto real, no lectura superficial)

Lei el codigo real de ambos proyectos antes de escribir nada:

- **UDB** (`src/modules/m_nick.c`, funcion `m_nick`): corta el parametro del
  `NICK` por `:` o `!` y pasa la clave a `TipoDePass()`, que consulta la
  base de datos propia de UDB (`nicks.udb`, arbol en fichero plano,
  `src/udb.c`). Si el nick esta registrado y la clave falta o es incorrecta,
  el `NICK` se ignora entero (mismo comportamiento que replica este modulo).
- **dBOTS** (`sistema/ni.mrc`): es su propio NickServ escrito en mIRC, con
  su propia base de datos de contrasenas (`nickserv.identify`, alias en
  `ni.mrc` linea ~412), que ya procesa un `IDENTIFY` estandar por PRIVMSG.

O sea: en el stack original hay **dos** bases de datos de cuentas (la de
UDB en el ircd, la de dBOTS en el bot), sincronizadas entre si por el
protocolo `m_db` propio de UDB. Portar eso 1:1 significaria reescribir ese
protocolo de sincronizacion server-to-server tambien.

Aqui se eligio deliberadamente la opcion **"UDB completo pero solo su pieza
de cuentas de nick"**: el ircd pasa a tener su propia base de datos de
cuentas (registro/identify/setpass/drop, con Argon2 en vez del
MD5/SHA1/RIPEMD160 de 2010), y **deja de depender de dBOTS para
identificacion**. Es la interpretacion mas fiel a "portar UDB" sin arrastrar
un protocolo de sync ircd-a-ircd que ya no tiene sentido con un bot mIRC
como maestro.

**Alternativa que NO se construyo (pero es mas barata si la prefieres):**
un modulo "shim" que solo separa `nick:clave` y reenvia un
`PRIVMSG NickServ IDENTIFY clave` sintetico, dejando a dBOTS como unica
fuente de verdad (cero cambios en dBOTS). Es la opcion mas simple si no
quieres mantener una segunda base de datos de cuentas. Pedidmelo y la
implemento en vez de esta, o ademas de esta.

## Que SI se porta y que NO (y por que)

| UDB (Unreal 3.2.8)            | Este modulo                                   |
|--------------------------------|------------------------------------------------|
| `NICK nick:clave` / `nick!clave` | Si, identico en sintaxis y en el "ignora el NICK si falla" |
| Base de datos de nicks (`nicks.udb`) | Si, propia (`data/udbnick.db`), formato distinto |
| Hash MD5/SHA1/RIPEMD160        | No -- Argon2 (`Auth_Hash`/`Auth_Check`, la misma API que usa Unreal para los `oper{}`) |
| Cuenta de servicios visible en WHOIS/cloak | Si -- usa `client->user->account` + `HOOKTYPE_ACCOUNT_LOGIN`, igual que SASL |
| Base de datos de canales (`canales.udb`) | No -- usa tu ChanServ (Anope/Atheme) o el propio ChanServ de dBOTS (`ch.mrc`), que ya funciona igual de bien sobre Unreal 6 sin tocar nada |
| Restriccion de nicks por IP (`ips.udb`) | No -- usa TKL/Z-lines nativos de Unreal 6 |
| Opciones de enlace (`links.udb`) | No -- `links.conf` nativo |
| K/G/Z-lines propias (`lines.udb`) | No -- TKL nativo de Unreal 6 (mas maduro y sincronizado de serie) |
| Protocolo `m_db` de sync entre servidores | No -- no aplica: aqui el ircd es la unica fuente de verdad, no hay bot maestro que sincronizar |

## Instalacion

1. UnrealIRCd 6.2.6 compilado con soporte de modulos de terceros
   (viene de serie).
2. Copia `src/udbnick.c` a `src/modules/third/udbnick.c` dentro del arbol
   fuente de UnrealIRCd (crea `third/` si no existe).
3. Compila el modulo:
   ```
   ./unrealircd module install third/udbnick
   ```
   o, si trabajas directamente sobre el arbol fuente sin el gestor de
   modulos: `make` normal, UnrealIRCd detecta y compila todo lo que hay
   bajo `src/modules/`.
4. En `modules.conf` (o el `.conf` que uses para `loadmodule`):
   ```
   loadmodule "third/udbnick";
   ```
5. Asegurate de que el directorio `data/` dentro del directorio de trabajo
   del ircd existe y es escribible por el usuario que corre UnrealIRCd
   (ahi se guarda `udbnick.db`).
6. `/REHASH` o reinicio.

## Uso

- `/REGISTER <contrasena> [email]` -- registra el nick que estas usando
  ahora mismo.
- `/IDENTIFY <contrasena>` -- identificate con el nick actual (dos pasos,
  como NickServ de toda la vida).
- `/NICK nick:contrasena` -- registro E identificacion en un solo paso, el
  que usan los scripts de dBOTS.
- `/SETPASS <actual> <nueva>` -- cambiar la clave.
- `/DROP` -- borra el registro del nick con el que estas identificado.
- `/DROP <nick> <contrasena>` -- borra el registro de cualquier nick dando
  su clave (no hace falta estar conectado con el). Los IRCops pueden
  `/DROP <nick>` sin clave.

## Pruebas reales realizadas (no solo lectura de codigo)

Esto ya NO es solo "deberia compilar": lo compile y lo probe de verdad.

1. Clone `unrealircd/unrealircd` (rama `unreal60_dev`, que en este momento
   se identifica como `6.2.7-git` -- la continuacion directa de la 6.2.6
   que pediste), lo compile completo con `./Config` + `make` en un
   entorno Linux con las dependencias reales (OpenSSL, PCRE2, c-ares,
   argon2, jansson, sqlite3), con `udbnick.c` y `dbotsbridge.c` puestos en
   `src/modules/third/`.
2. **La primera compilacion encontro un bug real**: `[error] [BUG]
   CommandOverrideAdd() called by module 'third/udbnick' before
   MOD_LOAD().` -- lo tenia en `MOD_INIT()`, y Unreal exige que los
   `CommandOverrideAdd()` se hagan en `MOD_LOAD()` porque en `MOD_INIT()`
   el comando `NICK` puede no estar registrado todavia segun el orden de
   carga de modulos. Corregido (ver el comentario en el codigo).
3. Con eso arreglado, compilo limpio (0 errores, 0 warnings propios --
   solo un `-Waddress` trivial que tambien limpie).
4. Arranque una red de pruebas real (`irc.example.org`, puerto 6667 en
   local) con ambos modulos cargados, un operclass propio
   (`dbots-service`, con el permiso `dbots { svs; }` que exige
   `dbotsbridge.c`) y un oper de prueba.
5. Escribi un cliente IRC en Python puro (`tests/test_udbnick.py`, sin
   dependencias) que habla el protocolo crudo por socket y ejecuta 18
   casos de prueba de extremo a extremo contra el ircd real: registro,
   identificacion automatica via `NICK nick:clave` y `NICK nick!clave`,
   rechazo de clave incorrecta/ausente, `SETPASS`, `DROP`, re-registro
   tras el drop, y los cinco subcomandos de `DBOTSSVS`
   (`SWHOIS`/`SVS2MODE`/`SVSNICK`/`SVSSILENCE`/`SVSNOLAG`) usados desde
   una sesion realmente opereada, mas la comprobacion de que un cliente
   NO-oper recibe `Permission Denied`.

**Resultado: 18/18 pruebas pasan.** Para reproducirlo tu mismo, con
UnrealIRCd ya compilado e instalado con ambos modulos:

```
python3 unrealircd-udbnick/tests/test_udbnick.py
```

## Ademas: mIRC REAL, no solo un cliente Python simulado

Instale Wine + Xvfb (framebuffer virtual) + openbox (gestor de ventanas
minimo) en este entorno Linux, descargue el `mirc.exe` real que trae el
propio repositorio de dBOTS (mIRC v6.2, el mismo binario que mencionan
`dbots.conf`), y lo arranque contra mi UnrealIRCd de pruebas -- con
capturas de pantalla para verificarlo visualmente en cada paso. mIRC
autentico, corriendo bajo Wine, hizo con exito:

- `/server 127.0.0.1 6667` -- conexion normal de cliente.
- `/oper bobsmith testpass123` -- gano privilegios de IRCop de verdad
  (`+iwxost`, autounido a `#opers`).
- `/quote DBOTSSVS SWHOIS ...` -- el `/WHOIS` posterior mostro la linea
  puesta por el bridge, confirmado en pantalla.
- `/register clave123 ...` seguido de una SEGUNDA conexion mIRC
  intentando el mismo nick: rechazada con el mensaje exacto
  ("El nick BridgeTester esta registrado. Usa /NICK..."), y
  `/nick BridgeTester:clave123` identificando correctamente.

Esto **encontro dos bugs reales mas** que ni la compilacion ni las
pruebas en Python habian detectado:

- **Bug de persistencia (el importante)**: UnrealIRCd cambia su
  directorio de trabajo a `tmp/` una vez arrancado del todo (convencion
  de daemonizacion), asi que la ruta relativa `"data/udbnick.db"` que
  usaba resolvia a `tmp/data/udbnick.db` (que no existe) y el guardado
  fallaba en silencio en cuanto pasaba tiempo desde el arranque --
  exactamente el tipo de fallo que solo aparece con uso real, no en un
  test que arranca y prueba todo en segundos. Corregido usando
  `PERMDATADIR` (la macro de ruta absoluta que ya expone UnrealIRCd para
  esto exactamente), no una ruta relativa.
- Un mensaje de log que siempre decia "via NICK nick:pass" aunque el
  login viniera de `/REGISTER` o `/IDENTIFY` -- cosmetico, tambien
  corregido.

Ambos arreglos ya estan en el codigo y reverificados: recompile,
reinstale, y las 18 pruebas de Python siguen en verde, mas confirmacion
en el log de que el guardado ya no falla.

**Esto tambien significa que, si recupero conectividad/entorno
suficiente, SI puedo seguir probando la conversion de los ficheros
`.mrc` con mIRC real** en vez de solo teoria -- lo cual cambia la
conversacion sobre cuanto de la conversion completa es razonable
intentar en las siguientes iteraciones.

(Edita `HOST`/`PORT` al principio del script si tu ircd no esta en
`127.0.0.1:6667`, y asegurate de tener un oper `bobsmith`/`testpass123`
con `operclass dbots-service` para que los tests 7-18 tengan permisos --
o cambia esas dos lineas en el script por tu propio oper de pruebas.)

## Limitaciones conocidas (honestas, no las escondo)

- **No probado en una red multi-servidor real (hub+leaf)**: el relay de
  `DBOTSSVS` a un servidor remoto (`sendto_one` cuando `!MyUser(target)`)
  esta implementado y usa el mismo patron que el codigo oficial de
  Unreal, pero solo tengo un servidor en este entorno de pruebas -- no
  pude verificar en vivo que el reenvio entre dos `unrealircd` enlazados
  funciona byte a byte.
- **No probado con mIRC/dBOTS real**: no hay forma de correr mIRC (software
  Windows propietario) en este entorno Linux. Lo que verifique en vivo es
  el lado del ircd (los comandos que dBOTS necesitaria mandar); el lado
  mIRC de la adaptacion (ver `DBOTS-MIGRATION.md`) sigue siendo una
  plantilla+tabla de traduccion, no algo que haya podido ejecutar de
  verdad boton a boton.
- **Sin bloque de configuracion todavia**: la ruta de la base de datos, el
  minimo de longitud de contrasena y el tipo de hash son `#define` en el
  propio `.c` (arriba del fichero), no `set::udbnick { }`. Facil de anadir
  despues si hace falta (`ConfigCheck`/`ConfigRun`), lo deje fuera para no
  meter API que no pude verificar con la misma confianza que el resto.
- **Sin migracion automatica** desde `nicks.udb` (UDB) ni desde la base de
  datos de dBOTS (`nickserv\*.db`). Habria que escribir un script aparte
  que lea el formato de origen y escriba lineas `nick:hash:ts:email` en
  `data/udbnick.db` -- y como dBOTS guarda las claves con su propio
  formato (no Argon2), en ese caso concreto lo mas realista es forzar un
  `/SETPASS` a cada usuario en la migracion, no migrar el hash tal cual.
- **Un solo servidor en mente para el flujo de contrasena**: la contrasena
  en si nunca sale de la memoria del servidor al que el cliente esta
  conectado (moddata `MODDATATYPE_LOCAL_CLIENT`, no se sincroniza). Si
  tienes hub+leaves, cada leaf necesita el modulo cargado Y la misma
  `data/udbnick.db` (por ejemplo en un volumen compartido, o replicada por
  tu cuenta) para que el mismo nick se pueda identificar desde cualquier
  leaf.
- **Sin proteccion antifuerza-bruta**: UDB original bloqueaba tras varios
  intentos fallidos (`flood.udb_c`/`udb_t`). Aqui no hay throttling propio
  todavia -- el `nick-flood` generico de Unreal ayuda algo, pero no es lo
  mismo. Fácil de anadir si os hace falta.
- **`strcasecmp` para comparar nicks**, no el casemapping RFC1459 completo
  de IRC (`{}|` == `[]\`). Para la inmensa mayoria de nicks reales da
  igual, pero un nick con esos caracteres podria registrarse dos veces con
  "distinta" mayuscula/minuscula segun el criterio.

## Que decision falta por tu parte

Si instalas esto, **dBOTS deja de ser la autoridad de identificacion** --
lo pasa a ser el propio ircd. Su `ni.mrc` (REGISTER/IDENTIFY/DROP/etc.) se
queda huerfano para esa funcion salvo que lo desactives o lo reprogrames
para que sea un frontend que hable con este modulo (por ejemplo, con el
modulo JSON-RPC de Unreal 6, que dBOTS podria llamar via un socket
adicional -- mIRC ya tiene soporte de sockets en vuestro propio
`sistema/sockets.mrc`). Si preferis que dBOTS siga siendo el maestro real
de las cuentas y el ircd solo sea un atajo de sintaxis, decidmelo y
implemento la alternativa "shim" descrita arriba en su lugar.
