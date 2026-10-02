# Calculadora de ROI de Fivo

## Qué es
Calculadora web (ES/EN) que estima cuánto se ahorraría y cuánto más ganaría un equipo con Fivo (fivo.ai) y lleva a reservar una llamada o probar Fivo. Todo el cálculo ocurre en el navegador; solo el formulario "Enviarme mi informe" habla con el servidor (`lead.php`), y ahora está oculto (`CFG.leadEndpoint` vacío). Es una página estática: HTML, CSS y JavaScript en un único `index.html`, más un PHP pequeño para el formulario.

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
4. Para ver cada variante del CTA: `http://localhost:5050/?ab=A` o `?ab=B` (con 3 o más miembros). Para el idioma: `?lang=en`. En localhost (o con `?preview=1` en cualquier dirección) se ven los marcos de relleno: el del vídeo ("Aquí irá el vídeo") y las 5 tarjetas "VISTA PREVIA – NO REAL" del carrusel de testimonios. Con `?public=1` se simula la web publicada: no se ve ningún relleno.

## Cómo está hecho `index.html`
- `CFG`: todas las constantes del modelo, precios, logos, `videoUrl`, `callUrl`, `registerUrl`, `callDuration`, `showExamples`.
- `STATIC`: textos del HTML (atributos `data-i18n`, `data-i18n-ph`, `data-i18n-aria`) en `es` y `en`. Pueden llevar `{variables}` (porcentajes, precios) que `fill()` rellena desde `CFG`. El texto español por defecto del HTML se mantiene igual que `STATIC.es`.
- `D`: textos que genera el cálculo, con variables `{x}`; se leen con `tr(clave, vars)`.
- `compute(state)`: única fuente de cifras (enteros, no depende del idioma). `tierSet(ingresos)` calcula los tramos de consultores.
- `render()` y `renderTable()` pintan la tarjeta del resultado, el desglose y la comprobación de cuadre ("Cifras verificadas"). `renderCta()` pinta el CTA principal y la barra fija.
- Formato por idioma: `eur()`, `eur2()`, `pct()`, `dec()` (ES `17.301 €` / EN `€17,301`).
- Pasos: 1 Tu empresa · 2 Tus ventas · 3 Tus herramientas · 4 Tu resultado (`LAST = 4`).
- No hay aviso de gasto por persona ni garantías bajo el título del hero (se eliminaron a propósito; no volver a añadirlos). Bajo "Gasto anual" solo hay una ayuda fija.
- La barra de direcciones se mantiene limpia: el estado (`#c=...`) solo se genera al pulsar "Copiar enlace con mi cálculo" (`shareLink()`), al enviar el formulario o al ir a reservar llamada. Al abrir un enlace con `#c=...` se lee el estado y se borra el hash. Los enlaces antiguos (sin campo `v`) siguen funcionando: se ignoran los campos que ya no existen y se convierten `people`, `loom` y `others`. Idioma en `localStorage`.

## Estructura de la página
- **Pasos 1 a 3 (preguntas):** navegación · hero (título y subtítulo) · barra de pasos · el paso activo · pie.
- **Paso 4 "Tu resultado"** (una sola columna centrada de 760 px como máximo; la barra de pasos y el hero grande se ocultan y no ocupan sitio):
  1. Enlace pequeño arriba a la izquierda "← Cambiar mis datos" / "← Edit my answers" (vuelve al paso 1 conservando las respuestas).
  2. **Tarjeta principal** con el degradado de la marca: título corto · dos cifras grandes "Te ahorrarías" y "Ganarías más" (siempre en dos columnas, también en móvil) · "Estimación a partir de tus datos" · línea de beneficio neto (solo si es mayor que 0) · línea de precio ("menos de 40 € al mes por persona", con el techo de `CFG.priceMonthly`) · **CTA grande** separado por una línea fina.
  3. **Banda de logos** compacta ("Empresas que ya usan Fivo").
  4. **Vídeo** ("Por qué y cómo puedes ahorrar dinero con Fivo" + "Ir a mi cálculo ↓", que baja hasta el panel plegado).
  5. (Desactivados por defecto) ejemplos de cálculo.
  6. **Carrusel de testimonios** (solo con testimonios reales o en vista previa).
  7. **Panel plegado**, cerrado por defecto, "Ver cómo se calcula y ajustar supuestos": desglose completo con "Cifras verificadas", selector Mensual/Anual con la frase del ahorro anual, "Pon a prueba los supuestos" (tiempo recuperado, mejora de ventas, herramientas sustituidas e interruptor del análisis externo), botones "Copiar enlace con mi cálculo" y "Descargar PDF" (el PDF abre el panel antes de imprimir), el formulario de email (oculto) y la línea de fuentes (McKinsey Global Institute y precios públicos de Fivo).
- **Se eliminaron a propósito (no volver a añadir sin pedirlo):** la sección de preguntas frecuentes, el bloque final grande de conversión, "Cómo se calcula", "Construye memoria. Decide mejor.", las tres tarjetas pequeñas (días para amortizar, euros al día y al mes), la tarjeta de "Sin memoria compartida…", el gráfico de barras y su leyenda, el botón negro "Reservar demo con mis números", la nota "¿Prefieres ir paso a paso?", la tarjeta de compartir y el cierre personalizado con la cifra. La frase "Fivo te enseña dónde se pierden tiempo y ventas…" también desapareció con la tarjeta de pérdida.
- **El CTA se ve nada más llegar:** el botón principal debe verse completo SIN scroll en 1440×900, 1280×720, 390×844 y 360×740. La tarjeta tiene `min-height` de pantalla completa (menos la navegación) con el contenido centrado, para que la banda y el vídeo queden debajo del primer pantallazo. El titular y el botón salen al instante: sin retraso, sin opacidad cero y sin animación de entrada. Solo la cifra grande cuenta de 0 a su valor (800 ms como máximo, `countUp()`), y el CTA no depende de esa animación.
- **Presupuesto de texto de la primera pantalla del resultado: 70 palabras como máximo** (sin la navegación; se cuentan las palabras con letras o números, no los símbolos sueltos como `←` o `€`). Medido: ES variante A 69, B 65; EN variante A 70, B 60. Antes de añadir cualquier texto a la tarjeta hay que volver a contar. En inglés se acortaron dos textos para cumplirlo ("Estimate from your data" y "We'll review your numbers with you.").

## Modelo de cálculo (todo en enteros, desde `compute()`)
- **Licencias** = comerciales + resto del equipo. **Coste de Fivo** = redondeo(licencias × precio × 12).
- **A. Herramientas** = 12 × gasto sustituible al mes (grabación, transcripción, notas). Si marca "No lo sé": 20 % del gasto en aplicaciones (`unknownShare`). Nunca supera el gasto total en aplicaciones. Etiqueta "Tu dato" o "Estimación".
- **B. Análisis externo** (interruptor `audOn`, activado por defecto, en el panel de supuestos):
  - Con consultores: valor del tramo elegido × `consultantReplace` (50 %). Etiqueta "Tu dato".
  - Sin consultores: valor de referencia = clamp(redondeo a 500 de gasto anual × 1 %, 1.000, 20.000). Etiqueta "Referencia".
- **C. Tiempo recuperado** = redondeo(redondeo(gasto anual × 20 %) × % recuperado / 100), 25 % por defecto. Estimación.
- **D. Ventas extra** = redondeo(ingresos × % de mejora / 100) (interruptor `revOn`). Mejora por defecto según "¿Grabáis y revisáis las llamadas de venta?": No 1,5 % · A veces 1 % · Sí, siempre 0,5 % (0-5 %, ajustable). Estimación.
- **Te ahorrarías** = A + B + C. **Ganarías más** = D. **Beneficio neto** = ahorro + ganarías más − coste de Fivo.
- La pregunta "¿optimizar todos los departamentos?" NO cambia ninguna cifra: se guarda en el estado y en el enlace compartido para cualificar al contacto, pero el resultado ya no muestra ninguna frase con ella.

### Tramos de gasto en consultores
- Límites proporcionales: 0,5 % · 1,5 % · 3 % de los ingresos, redondeados a múltiplos de 500, siempre que queden estrictamente crecientes y el primero sea > 0 (ingresos de unos 50.000 € o más).
- Valor de cálculo de cada tramo proporcional: 0,25 % · 1 % · 2,25 % · 4 % de los ingresos, redondeado a múltiplos de 500 y mínimo 500.
- Si no se cumple (ingresos bajos): tramos fijos hasta 2.000 · 2.000-6.000 · 6.000-15.000 · más de 15.000 €, con valores 1.000 · 4.000 · 10.500 · 20.000 €.
- Todo está en `CFG` (`consultantBoundsPct`, `consultantMidPct`, `consultantRound`, `consultantMin`, `consultantFixedBounds`, `consultantFixedValues`, `consultantReplace`).

### Precios
- **Mensual 39,99 €/usuario/mes: confirmado** en la página de precios de Fivo.
- **Anual 29,99 €: SIN CONFIRMAR**, deducido del 25 % de ahorro que indica la página de precios (`CFG.priceAnnual`). Cualquier texto lo presenta como aproximado ("≈").
- La línea "Fivo cuesta menos de 40 € al mes por persona" calcula el 40 como `Math.floor(CFG.priceMonthly) + 1` (el techo del precio) y lo formatea según el idioma; si el precio cambia, se actualiza sola.

## Banda de logos
- Va debajo de la tarjeta principal, dentro del paso 4 (ya no está en el hero ni encima de la navegación). Título pequeño: "Empresas que ya usan Fivo" / "Companies already using Fivo". Compacta: altura base de 24 px.
- Se configura en `CFG.customerLogos`: lista de `{ name, url, scale }`. `scale` (por defecto 1) multiplica la altura base para igualar el peso visual: logos finos o pequeños con scale mayor (Embat 1,9; Thinking Heads 1,7; Universidad de Navarra 1,6; Catalana Occidente 1,5) y los grandes algo menor (Gameloft, Rubau y Mapei 0,9; Eurofins 0,95). Ajustar mirando la banda en pantalla.
- Se anima con `transform` de -50 % a 0 (izquierda a derecha, dos mitades idénticas, bucle sin saltos), se pausa con el ratón y no se anima con `prefers-reduced-motion`. Los bordes se difuminan con `mask-image`. Opacidad 0,8, logos en blanco. Si una imagen falla, se quita de la banda (de ambas mitades). Las imágenes se precargan al abrir la página porque el navegador no descarga las de elementos ocultos.
- En el paso 4 los rombos decorativos (`.dia`) se ocultan (clase `is-result` en `body`).

## Vídeo
- Va después de la banda y antes del carrusel, con el título "Por qué y cómo puedes ahorrar dinero con Fivo" / "Why and how you can save money with Fivo", marco 16:9 con el estilo de tarjeta y debajo el botón discreto "Ir a mi cálculo ↓" / "Go to my numbers ↓" (baja suave hasta el panel plegado `#foldBox`, sin añadir `#` a la dirección).
- Se configura en `CFG.videoUrl = { es: "", en: "" }` (línea con el comentario "← AQUÍ las direcciones del vídeo"). Si el idioma activo está vacío se usa el otro. Acepta enlaces de compartir o insertar de YouTube (se convierten a `youtube-nocookie.com`), Vimeo y Loom, y un archivo `.mp4` (etiqueta `<video controls playsinline preload="metadata">`, con `CFG.videoPoster` opcional para la portada). Los iframes llevan `loading="lazy"`. Nunca hay reproducción automática.
- Sin ninguna dirección: en localhost o con `?preview=1` (y sin `?public=1`) se enseña el marco con "Aquí irá el vídeo"; en la web publicada el apartado no se muestra.
- **Seguridad:** el `.htaccess` ya incluye `frame-src` para YouTube (nocookie), Vimeo y Loom. Si se añade o cambia una política CSP (en Apache, en Cloudflare o en otro sitio) hay que permitir `frame-src` para el proveedor del vídeo; para un `.mp4` alojado fuera hay que permitir también `media-src` de ese dominio. GitHub Pages no usa el `.htaccess`.

## Testimonios (carrusel)
- **Regla de oro: NUNCA se inventan testimonios, nombres, cargos, empresas, puntuaciones ni cifras de reseñas. Solo se muestran los que el dueño del proyecto entregue con consentimiento.** Los textos de relleno son siempre claramente de relleno.
- Va dentro del paso 4 (a todo el ancho de la pantalla), entre el vídeo y el panel plegado. Solo se ve en "Tu resultado".
- Los datos salen de `CFG.testimonials`, que **empieza vacía**. Cada testimonio real tiene esta forma: `{ name, role, company, text: {es, en}, rating, consent, sourceName, sourceUrl }`.
  - Solo se muestran los que tengan `consent === true` (permiso por escrito de la persona). Sin `consent`, el testimonio se ignora aunque esté en la lista.
  - Si falta el texto de un idioma se usa el otro. Las estrellas solo se dibujan si `rating` es un número entre 1 y 5. Si hay `sourceUrl` (https), el nombre de la fuente enlaza a ella en pestaña nueva con `rel="noopener"`.
- `CFG.reviews = { count, storeName, url }`: el subtítulo "Con más de {count} reseñas en {storeName}…" solo sale si los tres están rellenos (count número, url https). Si no, no se muestra nada. No rellenar con datos que no sean reales.
- **Sin testimonios reales:** en la web publicada la sección entera no se muestra. En localhost o con `?preview=1` salen 5 tarjetas de relleno ("NOMBRE APELLIDO · Cargo · Empresa" y la etiqueta amarilla "VISTA PREVIA – NO REAL"). `?public=1` simula la web publicada (el relleno no se ve).
- Interacción: flechas, puntos, deslizar con dedo o ratón (pointer events), teclado (← → con el carrusel enfocado), clic en una tarjeta vecina. Pase automático cada 7 s: se pausa con el ratón o el foco y se detiene del todo en cuanto el usuario interactúa; sin pase automático con `prefers-reduced-motion`. Bucle infinito; en móvil (< 700 px) una tarjeta cada vez.
- Accesibilidad: `role="region"` + `aria-roledescription="carrusel"/"carousel"`, cada tarjeta `role="group"` con "n de N", las tarjetas fuera del centro son `aria-hidden` y sus enlaces no se enfocan; `aria-live` está en `off` durante el pase automático y en `polite` cuando navega el usuario.

## Ejemplos de cálculo (DESACTIVADOS)
- `CFG.showExamples = false`: la sección no se ve, pero el código y los datos siguen en el archivo. **Para recuperarla, poner `CFG.showExamples = true`**: sale entre el vídeo y el carrusel, con el aviso "Ejemplos ilustrativos con datos de muestra. No son clientes reales." y la etiqueta "EJEMPLO ILUSTRATIVO" en cada tarjeta.
- Perfiles en `CFG.examples`: `{ label: {es,en}, sector, comerciales, resto, gastoAnual, ingresos, revisaLlamadas ("no"|"some"|"yes"), consultores (null = no tiene; 0-3 = tramo), gastoApps, gastoGrabacion }` (los dos últimos, en €/mes, hacen falta para la línea de herramientas). Hoy: agencia de marketing (12 personas), inmobiliaria (6) y consultora (30).
- Las cifras salen de `compute()` con el formato del idioma; nunca se escriben a mano. "Cargar este ejemplo" rellena el estado, recalcula, pasa al paso 4 y sube hasta la tarjeta del resultado. Probado con una copia temporal con la opción activada.

## Test A/B del CTA del resultado
- **Variante A** (3 o más miembros, comerciales + resto): titular "¿Quieres que lo implementemos en tu empresa?" · botón "Reservar mi llamada" (`CFG.callUrl`, con los números en el enlace como `#c=...`) · "Revisamos tus números contigo." · enlace muy discreto "o prueba 7 días gratis" (registro).
- **Variante B y versión de conversión** (B, y siempre con 1 o 2 miembros): titular "Empieza a usar Fivo hoy mismo" · botón "Prueba 7 días gratis" (`CFG.registerUrl`) · "También tienes el plan Free." Sin enlace secundario.
- El botón: fondo blanco, texto oscuro, mínimo 60 px de alto, texto de 18 px, flecha, sombra suave, ancho completo en móvil. **No prometer lo que no podemos cumplir:** nada de "sin compromiso", plazos ni duración de la llamada. `CFG.callDuration = null`: solo si tiene un número de minutos se añade al botón de la variante A.
- Asignación aleatoria 50/50 **en cada carga de la página y solo en memoria**: NO se usa localStorage, sessionStorage ni cookies para la variante. Se fuerza con `?ab=A` o `?ab=B`. **Guardar la variante entre visitas necesitaría consentimiento y revisión legal.**
- **Barra fija** (`#stickyBar`, en ordenador y en móvil): aparece cuando el botón principal sale de la pantalla (`IntersectionObserver`, `armCtaObserver()`) y se oculta cuando vuelve a verse. Muestra "Beneficio estimado: {cifra}/año" / "Estimated profit: {figure}/yr" (si el beneficio no es positivo, "Ahorro estimado: …/año") y el mismo botón de la variante activa: A "Reservar llamada" / "Book a call", B y conversión "Probar gratis" / "Try free". Sustituye a la antigua barra del móvil.
- **Enlaces `utm`:** `utm_source=calculadora&utm_medium=web&utm_campaign=roi-calculator` con `utm_content` = `cta-main-A|cta-main-B|cta-main-conversion` (botón principal y su enlace secundario) o `cta-sticky-A|cta-sticky-B|cta-sticky-conversion` (barra fija).
- **Eventos `window.dataLayer`** (se crea si no existe; no se carga ningún script externo): `calc_step`, `calc_result` (al llegar al resultado), `calc_cta_view` (primera vez que el botón principal se ve, una vez por variante y carga), `calc_cta_click` (botón principal y enlace secundario), `calc_sticky_click` (barra fija), `calc_video_view` (el vídeo entra en pantalla; solo con vídeo de verdad), `calc_video_skip` (clic en "Ir a mi cálculo"), `calc_testimonial_view` y `calc_testimonial_nav`. Todos llevan solo `variant`, `step`, `sector` y `team_size` (1-2, 3-10, 11+), salvo los de testimonios, que llevan solo `variant` y `step`. Nunca cifras ni datos personales.

## Reglas que no se pueden romper
a) **Los números siempre cuadran.** Toda cifra sale de `compute()`; nada de cifras escritas a mano en los textos (en los textos del HTML se usan `{variables}` de `CFG`). El gasto sustituible en herramientas se muestra exactamente como lo introduce el usuario. Las cifras de la tarjeta y las del panel plegado son las mismas.
b) **ES y EN siempre a la par.** Cualquier texto nuevo va en los diccionarios `STATIC` o `D` en los dos idiomas (también el formato de números), y en inglés no puede quedar ninguna palabra en español.
c) **Cero afirmaciones sin respaldo.** Nada de testimonios, cifras de clientes ni urgencia falsa. Lo estimado se marca como estimación y las fuentes se citan. **NUNCA se inventan testimonios, nombres, cargos, empresas, puntuaciones ni cifras de reseñas; solo se muestran los que el dueño del proyecto entregue con consentimiento.**
d) **Marca Fivo.** Azul `#296BDC`, degradado `#5173FF` a `#0AC6FF`, fondo negro, tarjetas `#0A0A0A`, titulares en Outfit y texto en Poppins. No cambiar colores ni fuentes sin pedirlo al usuario.
e) **No relajar la seguridad de `lead.php`** (origen, honeypot, límite por IP, saneado).
f) **No borrar las carpetas `.github` ni `.deploy-now`** si existen (las crea IONOS Deploy Now).
g) **No añadir `package.json`, `composer.json` ni dependencias** en la raíz. Los scripts de prueba van fuera de la carpeta del proyecto.
h) **Flujo de trabajo.** Nunca hacer `git push` sin que el usuario lo pida de forma explícita (por ejemplo "súbelo"). Trabajar en ramas. Tras cada cambio, decir qué archivo se tocó y cómo verlo en local. Antes de cambios grandes, resumir el plan en 3 a 5 líneas.
i) **Sin almacenamiento para el test A/B** (ver arriba).
j) **La tarjeta del resultado es corta:** el CTA completo a la vista al llegar y como máximo 70 palabras en la primera pantalla (ver "Estructura de la página").

El usuario no es programador: explicar en español y de forma sencilla.

## PENDIENTES
- Conseguir testimonios reales **con permiso por escrito** de cada persona y añadirlos a `CFG.testimonials` con `consent: true` (hasta entonces el carrusel no sale en la web publicada).
- Definir `CFG.reviews` si se van a usar reseñas de una tienda (count, storeName y url reales).
- Poner las direcciones del vídeo en `CFG.videoUrl` (`es` y `en`; hoy vacías).
- Confirmar **por escrito** que las empresas de la banda de logos autorizan usar su logo y que "ya usan Fivo".
- Conectar una analítica (Google Tag Manager u otra, con consentimiento) para leer el test A/B y los eventos de CTA, barra fija, vídeo y testimonios desde `dataLayer`.
- Dirección real para reservar llamadas (`CFG.callUrl`; ahora https://fivo.ai/contact).
- Precio anual real de Fivo (`CFG.priceAnnual`, ahora 29,99 €, deducido del 25 % de ahorro). El mensual (39,99 €) está confirmado.
- Decidir si se recuperan los ejemplos de cálculo (`CFG.showExamples`) y, si se hace, revisar sus cifras de muestra.
- Confirmar que existen las rutas `app.fivo.ai/en/auth/`.
- Configurar el webhook en `lead-config.php` y la automatización que envía de verdad el informe por email (el formulario sigue oculto con `leadEndpoint` vacío).
- Revisión legal (consentimiento, política de privacidad, analítica).
- `canonical` y `og:image` (1200×630).
- Un caso real de cliente.
- Excluir la carpeta `dev` del despliegue en `.deploy-now/<proyecto>/config.yaml` al conectar con IONOS.
