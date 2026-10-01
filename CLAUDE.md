# Calculadora de ROI de Fivo

## Qué es
Calculadora web (ES/EN) que estima cuánto se ahorraría y cuánto más ganaría un equipo con Fivo (fivo.ai). Todo el cálculo ocurre en el navegador; solo el formulario "Enviarme mi informe" habla con el servidor (`lead.php`), y ahora está oculto (`CFG.leadEndpoint` vacío). Es una página estática: HTML, CSS y JavaScript en un único `index.html`, más un PHP pequeño para el formulario.

## Estructura de archivos
- `index.html` — la calculadora completa (estilos, textos ES/EN y lógica).
- `favicon.svg`, `favicon-48.png`, `apple-touch-icon.png` — iconos.
- `lead.php` — recibe el formulario por POST JSON, lo valida y lo reenvía al webhook.
- `lead-config.php` — configuración de `lead.php` (webhook, orígenes permitidos, cabecera de IP).
- `.htaccess` — Apache (IONOS Deploy Now): oculta archivos internos y añade cabeceras de seguridad (incluye `frame-src` para el vídeo de YouTube, Vimeo o Loom).
- `CNAME` — dominio de GitHub Pages.
- `dev/server.js` — servidor local solo para pruebas (no se despliega).
- `.gitignore`, `README.md`, `CLAUDE.md`.
- `.github/` y `.deploy-now/` — los crea IONOS Deploy Now (pueden no existir todavía).

## Cómo verlo en local
1. En la carpeta del proyecto: `node dev/server.js`
2. Abrir http://localhost:5050
3. El formulario está simulado en local: `dev/server.js` responde `{"ok":true}` a `POST /lead.php`. No se envía ningún email.
4. Para ver cada variante del cierre: `http://localhost:5050/?ab=A` o `?ab=B` (con 3 o más miembros). Para el idioma: `?lang=en`.

## Cómo está hecho `index.html`
- `CFG`: todas las constantes del modelo, precios, logos, `videoUrl`, `callUrl`, `registerUrl`.
- `STATIC`: textos del HTML (atributos `data-i18n`, `data-i18n-ph`, `data-i18n-aria`) en `es` y `en`. Pueden llevar `{variables}` (porcentajes, precios) que `fill()` rellena desde `CFG`. El texto español por defecto del HTML se mantiene igual que `STATIC.es`.
- `D`: textos que genera el cálculo, con variables `{x}`; se leen con `tr(clave, vars)`.
- `compute(state)`: única fuente de cifras (enteros, no depende del idioma). `tierSet(ingresos)` calcula los tramos de consultores.
- `render()` y `renderTable()` pintan resultado, desglose, barras y la comprobación de cuadre ("Cifras verificadas").
- Formato por idioma: `eur()`, `eur2()`, `pct()`, `dec()` (ES `17.301 €` / EN `€17,301`).
- Pasos: 1 Tu empresa · 2 Tus ventas · 3 Tus herramientas · 4 Tu resultado (`LAST = 4`).
- La barra de direcciones se mantiene limpia: el estado (`#c=...`) solo se genera al pulsar "Copiar enlace con mi cálculo" (`shareLink()`), al enviar el formulario o al ir a reservar llamada/demo. Al abrir un enlace con `#c=...` se lee el estado y se borra el hash. Los enlaces antiguos (sin campo `v`) siguen funcionando: se ignoran los campos que ya no existen y se convierten `people`, `loom` y `others`. Idioma en `localStorage`.

## Modelo de cálculo (todo en enteros, desde `compute()`)
- **Licencias** = comerciales + resto del equipo. **Coste de Fivo** = redondeo(licencias × precio × 12).
- **A. Herramientas** = 12 × gasto sustituible al mes (grabación, transcripción, notas). Si marca "No lo sé": 20 % del gasto en aplicaciones (`unknownShare`). Nunca supera el gasto total en aplicaciones. Etiqueta "Tu dato" o "Estimación".
- **B. Análisis externo** (interruptor `audOn`, activado por defecto, en "Pon a prueba los supuestos"):
  - Con consultores: valor del tramo elegido × `consultantReplace` (50 %). Etiqueta "Tu dato".
  - Sin consultores: valor de referencia = clamp(redondeo a 500 de gasto anual × 1 %, 1.000, 20.000). Etiqueta "Referencia".
- **C. Tiempo recuperado** = redondeo(redondeo(gasto anual × 20 %) × % recuperado / 100), 25 % por defecto. Estimación.
- **D. Ventas extra** = redondeo(ingresos × % de mejora / 100) (interruptor `revOn`). Mejora por defecto según "¿revisáis las llamadas?": No 1,5 % · A veces 1 % · Sí, siempre 0,5 % (0-5 %, ajustable). Estimación.
- **Te ahorrarías** = A + B + C. **Ganarías más** = D. **Beneficio neto** = ahorro + ganarías más − coste de Fivo.
- La pregunta "¿optimizar todos los departamentos?" NO cambia ninguna cifra: solo cualifica al contacto y muestra una frase en el resultado.

### Tramos de gasto en consultores
- Límites proporcionales: 0,5 % · 1,5 % · 3 % de los ingresos, redondeados a múltiplos de 500, siempre que queden estrictamente crecientes y el primero sea > 0 (ingresos de unos 50.000 € o más).
- Valor de cálculo de cada tramo proporcional: 0,25 % · 1 % · 2,25 % · 4 % de los ingresos, redondeado a múltiplos de 500 y mínimo 500.
- Si no se cumple (ingresos bajos): tramos fijos hasta 2.000 · 2.000-6.000 · 6.000-15.000 · más de 15.000 €, con valores 1.000 · 4.000 · 10.500 · 20.000 €.
- Todo está en `CFG` (`consultantBoundsPct`, `consultantMidPct`, `consultantRound`, `consultantMin`, `consultantFixedBounds`, `consultantFixedValues`, `consultantReplace`).

### Precios
- **Mensual 39,99 €/usuario/mes: confirmado** en la página de precios de Fivo.
- **Anual 29,99 €: SIN CONFIRMAR**, deducido del 25 % de ahorro que indica la página de precios (`CFG.priceAnnual`). Cualquier texto lo presenta como aproximado ("≈").
- El mensaje "Fivo cuesta menos de 40 € al mes por persona" se calcula desde `CFG.priceMonthly`; si el precio llegara a 40 € o más, hay que revisarlo.

## Cabecera y banda de logos
- Los dos botones del hero ya no existen; la navegación superior no se toca.
- La banda de logos va encima de la navegación. Sale de `CFG.customerLogos` (nombre + url). Se anima con `transform` (dos mitades idénticas, bucle sin saltos), se pausa con el ratón y no se anima con `prefers-reduced-motion`. Si una imagen falla, se quita de la banda.

## Vídeo
- Apartado "Mira cómo Fivo lo hace" bajo las tarjetas del resultado. Su dirección va en `CFG.videoUrl` (línea con el comentario "← AQUÍ la dirección del vídeo"). Acepta enlaces de compartir o de insertar de YouTube, Vimeo o Loom. Vacío = el apartado no se muestra. El iframe es `loading="lazy"` y el `.htaccess` ya permite esos tres orígenes en `frame-src`.

## Test A/B del cierre del resultado
- Con más de 2 miembros (comerciales + resto): variante A = "¿Quieres hacer una llamada…? Haz clic aquí" + botón "Reservar llamada" (`CFG.callUrl` con los números en el enlace). Variante B = "Empieza a usar Fivo ahora" + botón "Prueba 7 días gratis" (`CFG.registerUrl`).
- Con 1 o 2 miembros no hay test: siempre la versión de conversión (igual que B, `utm_content=cta-conversion`).
- Asignación aleatoria 50/50 **en cada carga de la página y solo en memoria**: NO se usa localStorage, sessionStorage ni cookies para la variante. Se fuerza con `?ab=A` o `?ab=B`. **Guardar la variante entre visitas necesitaría consentimiento y revisión legal.**
- Los enlaces de los botones llevan `utm_source=calculadora&utm_medium=web&utm_campaign=roi-calculator&utm_content=cta-A|cta-B|cta-conversion`.
- `window.dataLayer` (se crea si no existe; no se carga ningún script externo) recibe `calc_step`, `calc_result`, `calc_cta_view` y `calc_cta_click`, solo con `variant`, `step`, `sector` y `team_size` (1-2, 3-10, 11+). Nunca cifras ni datos personales.

## Reglas que no se pueden romper
a) **Los números siempre cuadran.** Toda cifra sale de `compute()`; nada de cifras escritas a mano en los textos (en los textos del HTML se usan `{variables}` de `CFG`). El gasto sustituible en herramientas se muestra exactamente como lo introduce el usuario.
b) **ES y EN siempre a la par.** Cualquier texto nuevo va en los diccionarios `STATIC` o `D` en los dos idiomas (también el formato de números), y en inglés no puede quedar ninguna palabra en español.
c) **Cero afirmaciones sin respaldo.** Nada de testimonios, cifras de clientes ni urgencia falsa. Lo estimado se marca como estimación y las fuentes se citan.
d) **Marca Fivo.** Azul `#296BDC`, degradado `#5173FF` a `#0AC6FF`, fondo negro, tarjetas `#0A0A0A`, titulares en Outfit y texto en Poppins. No cambiar colores ni fuentes sin pedirlo al usuario.
e) **No relajar la seguridad de `lead.php`** (origen, honeypot, límite por IP, saneado).
f) **No borrar las carpetas `.github` ni `.deploy-now`** si existen (las crea IONOS Deploy Now).
g) **No añadir `package.json`, `composer.json` ni dependencias** en la raíz. Los scripts de prueba van fuera de la carpeta del proyecto.
h) **Flujo de trabajo.** Nunca hacer `git push` sin que el usuario lo pida de forma explícita (por ejemplo "súbelo"). Trabajar en ramas. Tras cada cambio, decir qué archivo se tocó y cómo verlo en local. Antes de cambios grandes, resumir el plan en 3 a 5 líneas.
i) **Sin almacenamiento para el test A/B** (ver arriba).

El usuario no es programador: explicar en español y de forma sencilla.

## PENDIENTES
- Poner la dirección del vídeo en `CFG.videoUrl`.
- Confirmar que las empresas de la banda de logos autorizan usar su logo y que "usan Fivo".
- Conectar una analítica (Google Tag Manager u otra, con consentimiento) para leer el test A/B desde `dataLayer`.
- Dirección real para reservar llamadas (`CFG.callUrl`; ahora https://fivo.ai/contact).
- Precio anual real de Fivo (`CFG.priceAnnual`, ahora 29,99 €, deducido del 25 % de ahorro). El mensual (39,99 €) está confirmado.
- Confirmar que existen las rutas `app.fivo.ai/en/auth/`.
- Configurar el webhook en `lead-config.php` y la automatización que envía de verdad el informe por email (el formulario sigue oculto con `leadEndpoint` vacío).
- Revisión legal (consentimiento, política de privacidad, analítica).
- `canonical` y `og:image` (1200×630).
- Un caso real de cliente.
- Excluir la carpeta `dev` del despliegue en `.deploy-now/<proyecto>/config.yaml` al conectar con IONOS.
