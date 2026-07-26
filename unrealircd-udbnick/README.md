> **Actualizacion v2**: `udbnick.c` se reescribio como shim puro (ya NO
> mantiene su propia base de datos -- ver "Historial de diseno" abajo).
> Ademas, este directorio incluye `src/dbotsbridge.c`, `dbots-adapted/`
> (parches reales, probados, para hacer que dBOTS conecte y funcione de
> verdad sobre Unreal 6) y **[`DBOTS-MIGRATION.md`](DBOTS-MIGRATION.md)**
> -- la auditoria completa, con transcripts de pruebas reales con mIRC +
> dBOTS + UnrealIRCd 6.2.7-git compilado, no solo teoria. Empieza por ahi
> si tu objetivo es dBOTS funcionando, no solo el atajo `/NICK nick:clave`.

# udbnick — `/NICK usuario:contrasena` para UnrealIRCd 6.2.6

Modulo de terceros para UnrealIRCd 6.x que reproduce la funcion concreta de
[trocotronic/udb](https://github.com/trocotronic/udb) (fork de UnrealIRCd
3.2.8 usado por redes estilo IRC-Hispano) que necesitan los bots
[dBOTS](https://github.com/juanjiyo/dBOTS/tree/main/dbots): poder
identificarte metiendo la clave en el propio `/NICK`:

```
/NICK Usuario:MiContrasena
/NICK Usuario!MiContrasena
```

## Historial de diseno (por que cambio de v1 a v2)

**v1** (primera entrega): el ircd mantenia su propia base de datos de
cuentas independiente (registro/identify/setpass/drop, con Argon2). Esto
tenia sentido como "puerto fiel de UDB", pero era **la decision
equivocada para un despliegue real**: los datos reales (70 000+ usuarios,
~20 años de historial) viven **solo** en las `.db` de dBOTS (mIRC), no en
ningun UDB. Una segunda base de datos en el ircd, vacia al arrancar,
habria hecho huerfanas todas las cuentas existentes.

**v2** (actual): shim puro, sin base de datos propia. `dbots-adapted/`
(ver mas abajo) hace que dBOTS mismo -- con su NickServ real, su base de
datos real -- se conecte a Unreal 6 como cliente opereado en vez de como
servidor enlazado. Con eso funcionando, `udbnick.c` deja de necesitar
duplicar nada: solo separa `nick:clave`, dejar pasar el nick tal cual, y
en cuanto el cliente esta conectado le manda, en su nombre, un
`PRIVMSG <NickServ>@<este servidor> :IDENTIFY <clave>` -- exactamente lo
que el usuario habria escrito a mano. El NickServ real de dBOTS
(`ni.mrc`, sin modificar) hace la comprobacion de verdad contra su propia
base de datos real.

**Trade-off, dicho sin adornos**: la v1 podia RECHAZAR de raiz un `/NICK`
a un nick registrado sin clave o con clave incorrecta (como hacia UDB
original, "NICK ignorado"). La v2 no puede -- no sabe localmente si un
nick esta registrado, asi que un `/NICK` sin `:clave` a un nick ajeno
nunca se bloquea aqui. Cualquier proteccion contra "robo" de nick
registrado tiene que venir de dBOTS mismo (por ejemplo forzando
ghost/kill a quien lo tenga sin autenticar), igual que antes de que
existiera UDB.

## Instalacion

1. UnrealIRCd 6.2.6 (o superior; probado contra 6.2.7-git) compilado con
   soporte de modulos de terceros (viene de serie).
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
5. Si el nick de tu NickServ real NO es `NiCK`, edita
   `#define UDBNICK_NICKSERV_NICK "NiCK"` al principio de `udbnick.c`
   para que coincida con tu `dbots.conf`.
6. `/REHASH` o reinicio. **No hace falta ningun directorio `data/`
   escribible** -- esta version no persiste nada propio.

## Uso

No expone comandos propios (`/REGISTER`, `/IDENTIFY`, etc. de la v1
desaparecieron -- serian una segunda base de datos disfrazada). Solo:

- `/NICK nick:contrasena` o `/NICK nick!contrasena` -- registra el nick
  normalmente y, en cuanto la conexion esta lista, envia
  `PRIVMSG NickServ@tuservidor :IDENTIFY contrasena` en tu nombre. La
  respuesta (exito o fallo) te la contesta el NickServ real de dBOTS,
  igual que si lo hubieras escrito tu.

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
   dependencias) que ejecuta pruebas de extremo a extremo contra el ircd
   real, incluyendo los cinco subcomandos de `DBOTSSVS`
   (`SWHOIS`/`SVS2MODE`/`SVSNICK`/`SVSSILENCE`/`SVSNOLAG`) desde una
   sesion realmente opereada, y el rechazo `Permission Denied` a un
   cliente no-oper.

**Nota sobre `tests/test_udbnick.py`**: se escribio contra la v1 del
modulo (base de datos propia, comandos `/REGISTER` `/IDENTIFY`
`/SETPASS` `/DROP`). Esos comandos ya no existen en la v2 (shim puro,
ver "Historial de diseno" arriba) -- los subtests de `DBOTSSVS` y el de
permisos siguen siendo validos (no dependen de la base de datos propia),
pero los de registro/identify de la v1 fallaran contra el `.c` actual.
Pendiente de reescribir un `tests/test_udbnick_v2.py` que en su lugar
verifique el envio del `PRIVMSG ... IDENTIFY` sintetico (mockeando o
usando un NickServ de prueba). Lo honesto es dejarlo dicho aqui en vez
de dejar un test suite que miente sobre lo que cubre.

## mIRC REAL + dBOTS REAL, no solo simulacion

Instale Wine + Xvfb + openbox en este entorno Linux, descargue el
`mirc.exe` real que trae el propio repositorio de dBOTS, y lo arranque
-- primero solo contra el ircd (para probar `dbotsbridge.c` con mIRC
autentico: `/oper`, `/quote DBOTSSVS SWHOIS ...` confirmado por
`/WHOIS`), y despues **con dBOTS real cargado**, aplicando los parches
de `dbots-adapted/` a una copia de dBOTS.

Resultado con dBOTS real: las personas NickServ y ChanServ conectaron
como clientes opereados, y `/msg NiCK REGISTER pruebas@gmail.com` desde
un tercer cliente disparo el flujo REAL de registro de `ni.mrc` (sin
modificar), incluida la generacion del codigo de verificacion por email.
**Detalle completo, con las respuestas textuales de dBOTS, en
[`DBOTS-MIGRATION.md`](DBOTS-MIGRATION.md) "Pruebas reales (parte 2)".**
Esa ronda de pruebas tambien encontro y corrigio tres bugs reales mas
(uno en `udbnick.c`: ruta relativa vs `PERMDATADIR`; dos en el propio
dBOTS adaptado: `p.m` forjaba un prefijo que Unreal 6 rechaza, y mi
suposicion inicial sobre la sintaxis de `REGISTER` era incorrecta).

(Para reproducir `tests/test_udbnick.py` tal cual: `python3
unrealircd-udbnick/tests/test_udbnick.py`, editando `HOST`/`PORT` si tu
ircd no esta en `127.0.0.1:6667` -- pero recuerda la nota de arriba
sobre que subtests siguen aplicando a la v2.)

## Limitaciones conocidas (honestas, no las escondo)

- **`tests/test_udbnick.py` esta desactualizado** respecto a la v2 del
  `.c` (ver nota arriba) -- los subtests de `DBOTSSVS` y permisos siguen
  validos, los de cuentas propias no.
- **No probado en una red multi-servidor real (hub+leaf)**: el relay de
  `DBOTSSVS` a un servidor remoto (`sendto_one` cuando `!MyUser(target)`)
  esta implementado y usa el mismo patron que el codigo oficial de
  Unreal, pero solo tengo un servidor en este entorno de pruebas -- no
  pude verificar en vivo que el reenvio entre dos `unrealircd` enlazados
  funciona byte a byte.
- **Verificacion de email por SMTP no probada** en el flujo de
  `REGISTER` de dBOTS (SMTP desactivado a proposito en las pruebas) --
  el codigo se genera y guarda correctamente del lado de dBOTS, falta
  solo el envio real, que es config de SMTP, no de la adaptacion.
- **Sin bloque de configuracion todavia**: el nick de NickServ
  (`UDBNICK_NICKSERV_NICK`) es un `#define` en el propio `.c`, no
  `set::udbnick { }`. Facil de anadir despues si hace falta
  (`ConfigCheck`/`ConfigRun`), lo deje fuera para no meter API que no
  pude verificar con la misma confianza que el resto.
- **Un solo servidor en mente para el flujo de contrasena**: la contrasena
  en si nunca sale de la memoria del servidor al que el cliente esta
  conectado (moddata `MODDATATYPE_LOCAL_CLIENT`, no se sincroniza). Si
  tienes hub+leaves, cada leaf necesita el modulo cargado para que el
  atajo `NICK nick:clave` funcione desde cualquiera de ellos (el
  `PRIVMSG ... IDENTIFY` resultante SI viaja normalmente por la red como
  cualquier mensaje).
