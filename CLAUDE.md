# Calculadora de ROI de Fivo

## Qué es
Calculadora web (ES/EN) que estima el beneficio neto anual que deja Fivo (fivo.ai) a un equipo. Todo el cálculo ocurre en el navegador; solo el formulario "Enviarme mi informe" habla con el servidor (`lead.php`). Es una página estática: HTML, CSS y JavaScript en un único `index.html`, más un PHP pequeño para el formulario.

## Estructura de archivos
- `index.html` — la calculadora completa (estilos, textos ES/EN y lógica).
- `lead.php` — recibe el formulario por POST JSON, lo valida y lo reenvía al webhook.
- `lead-config.php` — configuración de `lead.php` (webhook, orígenes permitidos, cabecera de IP).
- `.htaccess` — Apache (IONOS Deploy Now): oculta archivos internos y añade cabeceras de seguridad.
- `dev/server.js` — servidor local solo para pruebas (no se despliega).
- `.gitignore`, `README.md`, `CLAUDE.md`.
- `.github/` y `.deploy-now/` — los crea IONOS Deploy Now (pueden no existir todavía).

## Cómo verlo en local
1. En la carpeta del proyecto: `node dev/server.js`
2. Abrir http://localhost:5050
3. El formulario está simulado en local: `dev/server.js` responde `{"ok":true}` a `POST /lead.php` y escribe el email y el beneficio neto en la consola. No se envía ningún email.

## Cómo está hecho `index.html`
- `CFG`: constantes (precios 39,99 / 29,99 €, 20 % de búsqueda, 12 meses, endpoint `lead.php`).
- `STATIC`: textos del HTML (atributos `data-i18n`, `data-i18n-ph`, `data-i18n-aria`) en `es` y `en`.
- `D`: textos que genera el cálculo, con variables `{x}`; se leen con `tr(clave, vars)`.
- `compute(state)`: única fuente de cifras (enteros, no depende del idioma).
- `render()` y `renderTable()` pintan resultado, desglose, barras y comprobación de cuadre.
- Formato por idioma: `eur()`, `pct()`, `dec()` (ES `17.301 €` / EN `€17,301`).
- Estado en la URL (`#c=...`) para compartir el cálculo; idioma en `localStorage`.

## Modelo de cálculo
Beneficio neto = herramientas (Loom + otras, × % sustituido) + auditorías (coach + analista, si se activan) + tiempo recuperado (gasto del equipo × 20 % × % recuperado, por defecto 25 %) + ventas extra (ingresos por llamadas × % de mejora, si se activa) − coste de Fivo (personas × precio mensual × 12; Business 39,99 €/mes o ≈ 29,99 €/mes en anual).

## Reglas que no se pueden romper
a) **Los números siempre cuadran.** Toda cifra sale de `compute()`; nada de cifras escritas a mano en los textos. Loom y "otras herramientas" se muestran exactamente como los introduce el usuario.
b) **ES y EN siempre a la par.** Cualquier texto nuevo va en los diccionarios `STATIC` o `D` en los dos idiomas, y en inglés no puede quedar ninguna palabra en español.
c) **Cero afirmaciones sin respaldo.** Nada de testimonios, cifras de clientes ni urgencia falsa. Lo estimado se marca como estimación y las fuentes se citan.
d) **Marca Fivo.** Azul `#296BDC`, degradado `#5173FF` a `#0AC6FF`, fondo negro, tarjetas `#0A0A0A`, titulares en Outfit y texto en Poppins. No cambiar colores ni fuentes sin pedirlo al usuario.
e) **No relajar la seguridad de `lead.php`** (origen, honeypot, límite por IP, saneado).
f) **No borrar las carpetas `.github` ni `.deploy-now`** si existen (las crea IONOS Deploy Now).
g) **No añadir `package.json`, `composer.json` ni dependencias** en la raíz.
h) **Flujo de trabajo.** Nunca hacer `git push` sin que el usuario lo pida de forma explícita. Trabajar en ramas. Tras cada cambio, decir qué archivo se tocó y cómo verlo en local. Antes de cambios grandes, resumir el plan en 3 a 5 líneas.

El usuario no es programador: explicar en español y de forma sencilla.

## PENDIENTES
- Precio anual real de Fivo (`CFG.priceAnnual`, ahora 29,99 €, deducido del 25 % de ahorro).
- Confirmar que existen las rutas `app.fivo.ai/en/auth/` (el selector de idioma las usa).
- Configurar el webhook en `lead-config.php` y la automatización que envía de verdad el informe por email.
- Revisión legal (consentimiento y política de privacidad).
- Analítica del embudo.
- `canonical` y `og:image` (1200×630).
- Un favicon real (`favicon.png` está referenciado pero no existe).
- Un caso real de cliente.
- Excluir la carpeta `dev` del despliegue en `.deploy-now/<proyecto>/config.yaml` al conectar con IONOS.
