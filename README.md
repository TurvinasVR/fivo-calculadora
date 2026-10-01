# Calculadora de ROI de Fivo

Una página web (en español e inglés) que calcula en 1 minuto el beneficio neto anual que deja Fivo. Todo el cálculo se hace en el navegador de quien la usa.

## Verla en tu ordenador
1. Abre una terminal en esta carpeta.
2. Ejecuta: `node dev/server.js`
3. Abre http://localhost:5050 en el navegador.

En local el formulario "Enviarme mi informe" es una simulación: no se envía ningún email.

## Qué hay en la carpeta
- `index.html`: la calculadora.
- `lead.php` y `lead-config.php`: reciben el formulario y lo reenvían a tu automatización.
- `.htaccess`: protege archivos internos y añade seguridad en el servidor.
- `dev/`: solo para verla en local (no se publica).

## Cómo se publica
1. Subir el proyecto a GitHub.
2. Conectarlo a IONOS Deploy Now como proyecto PHP.
3. Apuntar el dominio al despliegue.
