# Configuracion de ejemplo para InspIRCd v4.11.0

Config de ejemplo curada a partir de los archivos oficiales de
[InspIRCd 4](https://github.com/inspircd/inspircd/tree/insp4/docs/conf)
(`inspircd.example.conf`, `modules.example.conf`, `opers.example.conf`,
`links.example.conf`, `filter.example.conf`), pensada para arrancar
rapido una red publica con servicios (Anope/Atheme), TLS y proteccion
anti-flood ya activada.

## Estructura

```
conf/
  inspircd.conf   # archivo principal, incluye a todos los demas
  modules.conf     # modulos activados y su configuracion
  opers.conf       # clases, tipos y cuentas de IRCop  <- EDITAR
  links.conf       # enlaces de servidor y de servicios <- EDITAR
  filter.conf       # filtro de palabras/spam (opcional, vacio por defecto)
  help.conf         # base de /HELP (oficial de InspIRCd, no hace falta tocarlo)
  dnsbl.conf        # listas negras de IP (DroneBL), no hace falta tocarlo
  motd.txt          # mensaje del dia para usuarios
  opermotd.txt       # mensaje del dia para IRCops
```

## Que tienes que tocar tu

1. **`conf/inspircd.conf`**: solo el bloque `<define>` de arriba del
   todo (`networkName`, `networkDomain`) y el bloque `<admin>`. El
   resto de opciones ya son valores razonables.
2. **`conf/modules.conf`**: la ruta de tus certificados TLS
   (`certfile`/`keyfile` en los `<sslprofile>`) y la clave secreta de
   cloaking (`<cloak method="hmac-sha256" key="...">`). Genera una
   clave nueva con `openssl rand -hex 32`, nunca uses la del ejemplo.
3. **`conf/opers.conf`**: tus IRCops. Genera contrasenas hasheadas con
   `/MKPASSWD bcrypt tu_contrasena` una vez el ircd este arrancado.
4. **`conf/links.conf`**: tus servidores (hub/leaves) y el enlace a
   servicios. Borra la linea `<die>` al final de `opers.conf` y
   `links.conf` una vez los hayas editado de verdad (es un seguro
   para que no arranques con los ejemplos sin cambiar).

## Modulos activados por defecto

`modules.conf` activa un set "completo" pensado para una red seria:
IRCv3 (SASL, cap-notify, echo-message, etc.), TLS (OpenSSL) con
recarga de certificados por senal (`sslrehashsignal`, para renovar con
Let's Encrypt sin reiniciar), cloaking (cuenta + hmac-sha256, ver mas
abajo), integracion con servicios, `/HELP`, anti-flood (connectban,
connflood, join/message/nickflood, callerid), **DNSBL activo contra
bots/proxies conocidos** (DroneBL, ver `dnsbl.conf`), filtro de
mensajes, proteccion de nombres de canal contra colores/phishing
(`channames`), `/MONITOR`, `/WATCH`, `/SILENCE`, base de datos de
X-lines persistente, historial de chat (`+H`), auto-join de canales,
`/CLEARCHAN` para cortar raids de golpe, un canal `#IRCops` que recibe
en vivo las notificaciones clave de conexiones/bans/kills
(`chanlog`), y el juego completo de comandos de oper (SAJOIN, SAMODE,
override, CBAN, etc.) ya coordinado con las clases definidas en
`opers.conf`.

Al final de `modules.conf` hay una lista larga de modulos opcionales
(WebSocket, geolocalizacion, LDAP, SQL, HTTP, anti-repeat-flood,
HSTS/STS, ident, y varios extbans mas de nicho) comentados con una
linea explicando cada uno, por si tu red los necesita.

## Cloaks: "chateanos/user/cuenta" y "chateanos/support/cuenta"

- **Usuarios normales**: en cuanto se identifican a una cuenta de
  servicios (SASL o `/msg NickServ IDENTIFY`), su host mostrado pasa
  a ser `chateanos/user/<su_cuenta>` de forma automática -- no hay que
  hacer nada mas, es el modulo `cloak_user` (metodo `account`) el que
  lo hace, y se recalcula solo en cuanto inician sesion. Mientras NO
  esten identificados siguen viendo un cloak normal basado en su IP
  (hmac-sha256), para que tengan privacidad tambien como anonimos.
- **IRCops**: InspIRCd no tiene forma de generar automaticamente un
  cloak distinto solo por el hecho de ser oper (el sistema de cloaks
  no sabe si alguien "es oper", solo conoce cuenta/IP/nick/certificado).
  Por eso el vhost `chateanos/support/<cuenta>` de cada IRCop se pone
  a mano en su propio bloque `<oper>` de `opers.conf` (ya viene
  rellenado como ejemplo) -- es exactamente como lo hacian redes reales
  tipo Freenode/Libera con `red/staff/nick`. Como de todas formas vas a
  crear cada cuenta de IRCop tu mismo, es el mismo paso, solo hay que
  poner el nombre correcto en el campo `vhost`.

## Canales automaticos: #Chateanos y #IRCops

- `#Chateanos`: se crea solo al arrancar el servidor (modulo
  `permchannels`) y **todos** los usuarios entran a el automaticamente
  al conectar (modulo `conn_join`). Tiene historial de chat (`+H`)
  activado por defecto.
- `#IRCops`: tambien se crea solo, pero es `+s +i` (oculto e
  invitacion-only) -- los usuarios normales no lo ven ni pueden
  entrar. Los IRCops (las 3 categorias) entran automaticamente en
  cuanto hacen `/OPER` (modulo `operjoin`, con `override="yes"` para
  saltarse el `+i`).
- Puedes cambiar los nombres de canal editando `<autojoin channel=...>`
  y `<operjoin channel=...>` en `modules.conf`, y los `<permchannels
  channel=...>` correspondientes.

## Historial de chat (duracion)

El modulo `chanhistory` esta activo con un tope de **100 lineas / 3
dias** (`<chanhistory maxlines="100" maxduration="3d">` en
`modules.conf`); eso es el MAXIMO que se puede pedir en cualquier
canal. Cada canal decide su propio historial con `/MODE #canal +H
lineas:duracion` (por ejemplo `+H 50:1d`) -- los chanops lo activan, o
via plantilla de ChanServ si usas servicios. `#Chateanos` (50
lineas/1 dia) y `#IRCops` (100 lineas/3 dias) ya lo traen activado
desde que se crean.

## Modos de usuario y canal ya disponibles

Con los modulos activados por defecto tienes, entre otros:
- **Usuario**: `+x` cloak (automatico al conectar), `+c` commonchans
  (solo recibe PM de quien comparta un canal contigo), `+g` callerid
  (bloquea PMs de desconocidos salvo `/ACCEPT`), `+B` bot, `+z`
  solo-TLS, `+s`/snomasks y demas modos de oper.
- **Canal**: `+e` excepciones de ban, `+I` excepciones de invitacion,
  `+H` historial, `+g` filtro de chanops, `+f`/`+j`/`+F` anti-flood
  (mensajes/joins/nicks), `+C`/`+T` bloquear CTCP/NOTICE, `+N`/`+Q`
  bloquear nick-change/kicks, `+S`/`+c` quitar/bloquear color, `+P`
  canal permanente, `+z` solo-TLS (con sslmodes), y las mascaras
  extendidas (extbans) `m:` `a:` `s:` `n:` etc.

Lista completa de modos: https://docs.inspircd.org/4/user-modes/ y
https://docs.inspircd.org/4/channel-modes/

## Categorias de IRCop

| Tipo            | Puede...                                             |
|-----------------|-------------------------------------------------------|
| **Administrador** | Todo: DIE/RESTART/REHASH, enlaces de servidor, bans/kills, SA*, cambiar host/ident de otros. |
| **IRCop**          | Bans/kills/SA* y moderacion completa, pero SIN apagar el servidor ni tocar enlaces. |
| **Soporte** (novato) | Solo `/CHECK` (investigar), `/OJOIN` (entrar a mediar) y `/SAKICK`. Nada de bans, kills, ni cambiar host/ident. |

Las clases y comandos exactos de cada tipo estan al principio de
`opers.conf`, en `<class>`/`<type>`.

## Escala: ¿aguanta 10 000+ usuarios?

La configuracion por si sola ya no limita a 10 000 (se subieron
`<connect:limit>` y `<performance:softlimit>` a 20000, `somaxconn` a
1024, y `<whowas:maxgroups>` a 20000), pero para sostener esa carga en
produccion de verdad tambien necesitas, FUERA de estos archivos:

1. **Descriptores de fichero (ulimit)**: cada usuario conectado, cada
   enlace y cada archivo de log consume uno. Con systemd, en el
   `.service` de inspircd:
   ```
   [Service]
   LimitNOFILE=65535
   ```
   Sin systemd, `ulimit -n 65535` antes de arrancar (y ajustar
   `/etc/security/limits.conf` para que sea persistente).
2. **sysctl del sistema** (para que `somaxconn=1024` de arriba sirva
   de algo, y para no quedarte sin puertos/conexiones en TIME_WAIT):
   ```
   net.core.somaxconn = 1024
   net.ipv4.ip_local_port_range = 1024 65535
   net.ipv4.tcp_tw_reuse = 1
   ```
3. **CPU/RAM**: InspIRCd es esencialmente de un solo hilo para el bucle
   principal; con 10 mil usuarios activos (mensajes, joins, floods)
   conviene una CPU con buen rendimiento por nucleo, no muchos nucleos.
   Calcula RAM aproximada: unos pocos KB por usuario conectado mas el
   historial de canales (`chanhistory`) y bans.
4. **Varios servidores enlazados (hub + leaves)**: a partir de varios
   miles de usuarios concurrentes, muchas redes reparten la carga en
   varios servidores `leaf` conectados a un `hub`, en vez de un unico
   proceso. `links.conf` ya trae la plantilla de `<link>` para esto;
   solo tienes que anadir mas servidores. Reparte tambien la
   resolucion DNS/TLS entre ellos.
5. **DNSBL/anti-abuso**: con mas usuarios, mas bots/abuso. Considera
   activar el modulo `dnsbl` (comentado al final de `modules.conf`).

Sin los puntos 1 y 2 (limites del sistema operativo), el ircd se
quedara sin descriptores de fichero mucho antes de llegar a 10 000
conexiones, sin importar lo que digan estos `.conf`.

## Instalacion rapida

1. Instala InspIRCd 4.11.0 (paquete de tu distro o compilado desde
   [fuente](https://github.com/inspircd/inspircd), rama `insp4`).
2. Copia el contenido de `conf/` al directorio de configuracion de tu
   instalacion (por defecto algo como `/etc/inspircd` o
   `<prefix>/conf`).
3. Genera un certificado TLS (por ejemplo con Let's Encrypt, o uno
   autofirmado para pruebas) y coloca `cert.pem`/`key.pem` en ese
   mismo directorio, o ajusta las rutas en `modules.conf`.
4. Edita `inspircd.conf`, `modules.conf`, `opers.conf` y `links.conf`
   como se indica arriba.
5. Valida la config antes de arrancar:
   ```
   inspircd --config /ruta/a/conf/inspircd.conf --validate
   ```
6. Arranca el servidor: `inspircd start` (o el metodo que use tu
   paquete/systemd).

## Referencias oficiales

- Documentacion general: https://docs.inspircd.org/4/configuration/
- Lista de modulos: https://docs.inspircd.org/4/modules
- Archivos de ejemplo originales:
  - https://github.com/inspircd/inspircd/blob/insp4/docs/conf/inspircd.example.conf
  - https://github.com/inspircd/inspircd/blob/insp4/docs/conf/modules.example.conf
  - https://github.com/inspircd/inspircd/blob/insp4/docs/conf/opers.example.conf
  - https://github.com/inspircd/inspircd/blob/insp4/docs/conf/links.example.conf
  - https://github.com/inspircd/inspircd/blob/insp4/docs/conf/filter.example.conf
