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
IRCv3 (SASL, cap-notify, echo-message, etc.), TLS (OpenSSL), cloaking
hmac-sha256, integracion con servicios, anti-flood (connectban,
connflood, join/message/nickflood), filtro de mensajes, `/MONITOR`,
`/WATCH`, `/SILENCE`, base de datos de X-lines persistente, y el
juego completo de comandos de oper (SAJOIN, SAMODE, override,
CBAN, etc.) ya coordinado con las clases definidas en `opers.conf`.

Al final de `modules.conf` hay una lista de modulos opcionales
(WebSocket, DNSBL, geolocalizacion, LDAP, SQL, HTTP...) comentados,
por si tu red los necesita.

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
