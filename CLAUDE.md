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
4. Para ver cada versión del CTA: `http://localhost:5050/?ab=A` (llamada) o `?ab=B` (conversión). Para el idioma: `?lang=en`. En localhost (o con `?preview=1` en cualquier dirección) se ve el marco del vídeo "Aquí irá el vídeo" si no hay vídeo configurado (y las tarjetas "VISTA PREVIA – NO REAL" del carrusel si se activa `CFG.showTestimonials`). Con `?public=1` se simula la web publicada: no se ve ningún relleno.

## Cómo está hecho `index.html`
- `CFG`: todas las constantes del modelo, precios, logos, `videoUrl`, `callUrl`, `registerUrl`, `callDuration`, `abTest`, `showTestimonials`, `showExamples`.
- `STATIC`: textos del HTML (atributos `data-i18n`, `data-i18n-ph`, `data-i18n-aria`) en `es` y `en`. Pueden llevar `{variables}` (porcentajes, precios) que `fill()` rellena desde `CFG`. El texto español por defecto del HTML se mantiene igual que `STATIC.es`.
- `D`: textos que genera el cálculo, con variables `{x}`; se leen con `tr(clave, vars)`.
- `compute(state)`: única fuente de cifras (enteros, no depende del idioma). `tierSet(ingresos)` calcula los tramos de consultores.
- `render()` y `renderTable()` pintan la tarjeta del resultado, el desglose y la comprobación de cuadre ("Cifras verificadas"). `renderCta()` pinta el CTA de la tarjeta y la barra fija.
- Formato por idioma: `eur()`, `eur2()`, `pct()`, `dec()` (ES `17.301 €` / EN `€17,301`).
- Pasos: 1 Tu empresa · 2 Tus ventas · 3 Tus herramientas · 4 Tu resultado (`LAST = 4`).
- No hay aviso de gasto por persona ni garantías bajo el título del hero (se eliminaron a propósito; no volver a añadirlos). Bajo "Gasto anual" solo hay una ayuda fija.
- La barra de direcciones se mantiene limpia: el estado (`#c=...`) solo se genera al pulsar "Copiar enlace con mi cálculo" (`shareLink()`), al enviar el formulario o al ir a reservar llamada. Al abrir un enlace con `#c=...` se lee el estado y se borra el hash. Los enlaces antiguos (sin campo `v`) siguen funcionando: se ignoran los campos que ya no existen y se convierten `people`, `loom` y `others`. Idioma en `localStorage`.

## Estructura de la página
- **Pasos 1 a 3 (preguntas):** navegación · hero (título y subtítulo) · barra de pasos · el paso activo · pie. No existe barra fija.
- **Paso 4 "Tu resultado"**, de arriba abajo y **nada más** (el hero grande, los rombos decorativos y la barra de pasos se ocultan y no ocupan sitio; la navegación superior se queda):
  1. Enlace pequeño "← Cambiar mis datos" / "← Edit my answers" (vuelve al paso 1 conservando las respuestas).
  2. **Vídeo** ("Por qué y cómo puedes ahorrar dinero con Fivo") con el enlace discreto "Ver mi resultado ↓" / "See my results ↓", que baja suavemente hasta la tarjeta. Sin vídeo (en la web publicada) no se ven ni el vídeo ni el enlace.
  3. **Empresas que ya usan Fivo:** banda de logos compacta, con espacio arriba y abajo.
  4. **Carrusel de testimonios** (`CFG.showTestimonials = true`): compacto, entre la banda de empresas y la tarjeta. Solo muestra testimonios reales con `consent: true`; en localhost o con `?preview=1` salen 5 rellenos "VISTA PREVIA – NO REAL"; en la web publicada, sin testimonios reales, no se muestra. (Los ejemplos de cálculo, desactivados, irían aquí también.)
  5. **Tarjeta de resultado con el CTA** (última pieza antes del pie): título · dos cifras grandes "Te ahorrarías" y "Ganarías más" (siempre en dos columnas) · **la cuenta**: "{ahorro} + {ganancia} − {coste} de Fivo = {neto} de beneficio neto al año" (el "= neto" va junto y destacado; con neto ≤ 0 se muestra "= ({neto})" sin la palabra "beneficio" y debajo "Con estos datos el resultado neto es {neto}. Ajusta los supuestos o cuéntanos tu caso.") · línea de precio ("menos de 40 € al mes por persona", con el techo de `CFG.priceMonthly`) · "Estimación a partir de tus datos · Ver cómo se calcula" (el segundo es un botón que abre la ventana) · línea fina · **CTA**.
- **Tarjeta compacta:** sin alturas mínimas, padding de 28 a 44 px (`clamp`), altura igual al contenido (se mide en las pruebas: no sobra nada), ancho máximo de 760 px, centrada. No volver a poner `min-height` ni centrados verticales.
- **Barra fija con el CTA** (`#stickyBar`, en ordenador y en móvil): existe solo en el paso 4, aparece en cuanto se abre el resultado y se oculta mientras el botón de la tarjeta está a la vista (`IntersectionObserver`, `armCtaObserver()`); vuelve a aparecer si ese botón sale de la pantalla. A la izquierda "Beneficio estimado: {cifra}/año" / "Estimated profit: {figure}/yr" (si el beneficio no es positivo: "¿Quieres una llamada para implementarlo?" / "Want a call to set it up?") y a la derecha el botón de la versión activa. La página tiene padding inferior (en el pie) para que la barra no lo tape. Sustituye a cualquier barra fija anterior. **Cuidado:** no debe quedar ninguna regla CSS `.sticky-net{display:none}` suelta; las pruebas comprueban que de verdad se ve.
- **Ventana "Cómo se calcula tu resultado"** (`#calcModal`, diálogo modal): se abre desde "Ver cómo se calcula" de la tarjeta (evento `calc_details_open`). Es un `role="dialog"` con `aria-modal="true"` y `aria-labelledby`; atrapa el foco (Tab y Mayús+Tab), se cierra con Escape, con la X o haciendo clic fuera, devuelve el foco al botón que la abrió y bloquea el scroll del fondo (`html.md-open`, `inert` en la página y en la barra fija). Dentro: el desglose completo con "Cifras verificadas", el selector Mensual/Anual con la frase del ahorro anual, "Pon a prueba los supuestos" (tiempo recuperado, mejora de ventas, herramientas sustituidas e interruptor del análisis externo), los botones "Copiar enlace con mi cálculo" y "Descargar PDF", el formulario de email (oculto) y la línea de fuentes. Al cambiar un supuesto, la tarjeta y la barra fija se actualizan. **"Descargar PDF" imprime solo el desglose** (con la ventana abierta, el CSS de impresión oculta el resto).
- **Se eliminaron a propósito (no volver a añadir sin pedirlo):** el panel plegado "Ver cómo se calcula y ajustar supuestos" (ahora es la ventana), la sección de preguntas frecuentes, el bloque final grande de conversión, "Cómo se calcula", "Construye memoria. Decide mejor.", las tres tarjetas pequeñas (días para amortizar, euros al día y al mes), la tarjeta de "Sin memoria compartida…", el gráfico de barras y su leyenda, el botón negro "Reservar demo con mis números", la nota "¿Prefieres ir paso a paso?", la tarjeta de compartir y el cierre personalizado con la cifra.
- **Animación:** el titular y el botón del CTA salen al instante (sin retraso, sin opacidad cero y sin animación de entrada). Solo las cifras grandes cuentan de 0 a su valor (800 ms, `countUp()`), una sola vez, cuando la tarjeta entra en pantalla (`armCardObserver()`); el CTA no depende de eso.

## Modelo de cálculo (todo en enteros, desde `compute()`)
- **Licencias** = comerciales + resto del equipo. **Coste de Fivo** = redondeo(licencias × precio × 12).
- **A. Herramientas** = 12 × gasto sustituible al mes (grabación, transcripción, notas). Si marca "No lo sé": 20 % del gasto en aplicaciones (`unknownShare`). Nunca supera el gasto total en aplicaciones. Etiqueta "Tu dato" o "Estimación".
- **B. Análisis externo** (interruptor `audOn`, activado por defecto, en la ventana de cálculo):
  - Con consultores: valor del tramo elegido × `consultantReplace` (50 %). Etiqueta "Tu dato".
  - Sin consultores: valor de referencia = clamp(redondeo a 500 de gasto anual × 1 %, 1.000, 20.000). Etiqueta "Referencia".
- **C. Tiempo recuperado** = redondeo(redondeo(gasto anual × 20 %) × % recuperado / 100), 25 % por defecto. Estimación.
- **D. Ventas extra** = redondeo(ingresos × % de mejora / 100) (interruptor `revOn`). Mejora por defecto según "¿Grabáis y revisáis las llamadas de venta?": No 1,5 % · A veces 1 % · Sí, siempre 0,5 % (0-5 %, ajustable). Estimación.
- **Te ahorrarías** = A + B + C. **Ganarías más** = D. **Beneficio neto** = ahorro + ganarías más − coste de Fivo.
- **UNA SOLA FUENTE PARA EL BENEFICIO.** `compute()` es el único sitio donde se calcula dinero y devuelve, en enteros: `save` (ahorro = A+B+C), `earn` (ganancia = D), `gross` (bruto = save + earn), `cost` (coste de Fivo del plan elegido) y `net` (neto = gross − cost). También devuelve `value` (tiempo + ventas, para el formulario), `costMonthly`, `costAnnual` y `netIfAnnual` (para la frase del ahorro con pago anual). Ningún elemento de la interfaz recalcula nada: la tarjeta, la cuenta, la barra fija, la ventana de desglose, el formulario y las notas **solo leen estos campos**. Si hace falta una cifra nueva, se añade a `compute()`.
- **Mismo nombre y misma cifra en todas partes:** "Beneficio neto estimado" / "Estimated net profit" en la cuenta de la tarjeta, en la barra fija ("Beneficio neto estimado: {neto}/año") y en la fila final de la ventana de desglose. La nota del plan mensual solo habla de "beneficio" con pago anual si ese neto es positivo.
- Todo se actualiza en el mismo ciclo (`render()` es síncrono). Si algo cambia durante la animación de las cifras grandes, la animación se cancela y se pintan ya los valores finales; la animación siempre termina en los valores de `compute()`.
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
- Va en el paso 4, debajo del vídeo y encima de la tarjeta (ya no está en el hero). Título pequeño: "Empresas que ya usan Fivo" / "Companies already using Fivo". Compacta (altura base de 24 px) con 44 px de espacio arriba y abajo.
- Se configura en `CFG.customerLogos`: lista de `{ name, url, scale }`. `scale` (por defecto 1) multiplica la altura base para igualar el peso visual: logos finos o pequeños con scale mayor (Embat 1,9; Thinking Heads 1,7; Universidad de Navarra 1,6; Catalana Occidente 1,5) y los grandes algo menor (Gameloft, Rubau y Mapei 0,9; Eurofins 0,95). Ajustar mirando la banda en pantalla.
- Se anima con `transform` de -50 % a 0 (izquierda a derecha, dos mitades idénticas, bucle sin saltos), se pausa con el ratón y no se anima con `prefers-reduced-motion`. Los bordes se difuminan con `mask-image`. Opacidad 0,8, logos en blanco. Si una imagen falla, se quita de la banda (de ambas mitades). Las imágenes se precargan al abrir la página porque el navegador no descarga las de elementos ocultos.

## Vídeo
- Es lo primero del resultado (tras el enlace de volver), con el título "Por qué y cómo puedes ahorrar dinero con Fivo" / "Why and how you can save money with Fivo", marco 16:9 con el estilo de tarjeta y debajo el enlace "Ver mi resultado ↓" / "See my results ↓" (baja suave hasta la tarjeta `#netCard`, sin añadir `#` a la dirección).
- Se configura en `CFG.videoUrl = { es: "", en: "" }` (línea con el comentario "← AQUÍ las direcciones del vídeo"). Si el idioma activo está vacío se usa el otro. Acepta enlaces de compartir o insertar de YouTube (se convierten a `youtube-nocookie.com`), Vimeo y Loom, y un archivo `.mp4` (etiqueta `<video controls playsinline preload="metadata">`, con `CFG.videoPoster` opcional para la portada). Los iframes llevan `loading="lazy"`. Nunca hay reproducción automática.
- Sin ninguna dirección: en localhost o con `?preview=1` (y sin `?public=1`) se enseña el marco con "Aquí irá el vídeo"; en la web publicada el apartado entero (y el enlace) no se muestra.
- **Seguridad:** el `.htaccess` ya incluye `frame-src` para YouTube (nocookie), Vimeo y Loom. Si se añade o cambia una política CSP (en Apache, en Cloudflare o en otro sitio) hay que permitir `frame-src` para el proveedor del vídeo; para un `.mp4` alojado fuera hay que permitir también `media-src` de ese dominio. GitHub Pages no usa el `.htaccess`.

## Testimonios (carrusel)
- **Regla de oro: NUNCA se inventan testimonios, nombres, cargos, empresas, puntuaciones ni cifras de reseñas. Solo se muestran los que el dueño del proyecto entregue con consentimiento.** Los textos de relleno son siempre claramente de relleno.
- **`CFG.showTestimonials = true`** (valor actual): el carrusel se muestra entre la banda de empresas y la tarjeta del resultado (que sigue siendo lo último antes del pie). Con `false` no sale (ni siquiera los rellenos "VISTA PREVIA – NO REAL"), pero el código y los datos siguen en el archivo. Es compacto (tarjetas de poca altura y márgenes reducidos) para no alejar el CTA. Título "Lo que dicen nuestros usuarios" / "What our users say".
- **Cómo añadir un testimonio real** (solo cuando la persona haya dado su permiso por escrito): añadir un objeto a `CFG.testimonials` con esta forma y rellenar TODO con datos reales entregados por el dueño del proyecto:
  `{ name: "", role: "", company: "", text: { es: "", en: "" }, rating: null, consent: true, sourceName: "", sourceUrl: "" }`
  - `consent: true` es obligatorio; sin él el testimonio no se muestra. `rating` es un número de 1 a 5 solo si de verdad existe (si no, `null`: no se dibujan estrellas). `sourceName` y `sourceUrl` (https) solo si hay una fuente verificable (el nombre enlaza a ella en pestaña nueva). Si falta el texto de un idioma se usa el otro. Las iniciales del avatar salen del nombre. Después de añadir uno, los rellenos desaparecen solos.
- Los datos salen de `CFG.testimonials`, que **empieza vacía**. Cada testimonio real tiene esta forma: `{ name, role, company, text: {es, en}, rating, consent, sourceName, sourceUrl }`.
  - Solo se muestran los que tengan `consent === true` (permiso por escrito de la persona). Sin `consent`, el testimonio se ignora aunque esté en la lista.
  - Si falta el texto de un idioma se usa el otro. Las estrellas solo se dibujan si `rating` es un número entre 1 y 5. Si hay `sourceUrl` (https), el nombre de la fuente enlaza a ella en pestaña nueva con `rel="noopener"`.
- `CFG.reviews = { count, storeName, url }`: el subtítulo "Con más de {count} reseñas en {storeName}…" solo sale si los tres están rellenos (count número, url https). Si no, no se muestra nada. No rellenar con datos que no sean reales.
- Sin testimonios reales: en la web publicada la sección no se muestra; en localhost o con `?preview=1` salen 5 tarjetas de relleno ("NOMBRE APELLIDO · Cargo · Empresa" y la etiqueta amarilla "VISTA PREVIA – NO REAL"). `?public=1` simula la web publicada.
- Interacción: flechas, puntos, deslizar con dedo o ratón (pointer events), teclado (← → con el carrusel enfocado), clic en una tarjeta vecina. Pase automático cada 7 s: se pausa con el ratón o el foco y se detiene del todo en cuanto el usuario interactúa; sin pase automático con `prefers-reduced-motion`. Bucle infinito; en móvil (< 700 px) una tarjeta cada vez. Eventos `calc_testimonial_view` y `calc_testimonial_nav` (solo `variant` y `step`).
- Accesibilidad: `role="region"` + `aria-roledescription="carrusel"/"carousel"`, cada tarjeta `role="group"` con "n de N", las tarjetas fuera del centro son `aria-hidden` y sus enlaces no se enfocan; `aria-live` está en `off` durante el pase automático y en `polite` cuando navega el usuario.

## Ejemplos de cálculo (DESACTIVADOS)
- `CFG.showExamples = false`: la sección no se ve, pero el código y los datos siguen en el archivo. **Para recuperarla, poner `CFG.showExamples = true`**: sale entre la banda de empresas y la tarjeta, con el aviso "Ejemplos ilustrativos con datos de muestra. No son clientes reales." y la etiqueta "EJEMPLO ILUSTRATIVO" en cada tarjeta.
- Perfiles en `CFG.examples`: `{ label: {es,en}, sector, comerciales, resto, gastoAnual, ingresos, revisaLlamadas ("no"|"some"|"yes"), consultores (null = no tiene; 0-3 = tramo), gastoApps, gastoGrabacion }`. Hoy: agencia de marketing (12 personas), inmobiliaria (6) y consultora (30). Las cifras salen de `compute()`; nunca se escriben a mano. "Cargar este ejemplo" rellena el estado, recalcula, pasa al paso 4 y sube hasta la tarjeta.

## CTA del resultado y test A/B
- **Versión LLAMADA** (3 o más miembros, comerciales + resto): titular "¿Quieres hacer una llamada para que lo implementemos en tu empresa?" · botón "Reservar mi llamada" con flecha (`CFG.callUrl`, con los números en el enlace como `#c=...`) · "Revisamos tus números contigo y te decimos por dónde empezar." · enlace muy discreto "o prueba 7 días gratis" (registro).
- **Versión CONVERSIÓN** (1 o 2 miembros): titular "Empieza a usar Fivo hoy mismo" · botón "Prueba 7 días gratis" (`CFG.registerUrl`) · "También tienes el plan Free." Sin enlace secundario.
- **`CFG.abTest = false`** (valor actual): con 3 o más miembros se ve SIEMPRE la versión llamada y con 1-2 miembros SIEMPRE la de conversión. Con `true`: variante aleatoria 50/50, **solo en memoria** (en cada carga; NO se usa localStorage, sessionStorage ni cookies). `?ab=A` y `?ab=B` fuerzan cada versión (A = llamada, B = conversión) para poder verlas; con 1-2 miembros siempre sale la de conversión. **Guardar la variante entre visitas necesitaría consentimiento y revisión legal.**
- El botón: fondo blanco, texto oscuro, mínimo 60 px de alto, texto de 18 px, sombra suave, sube 2 px al pasar el ratón, ancho completo en móvil, respeta `prefers-reduced-motion`. **No prometer lo que no podemos cumplir:** nada de "sin compromiso", plazos ni duración de la llamada. `CFG.callDuration = null`: solo si tiene un número de minutos se añade al botón de la versión llamada.
- **Enlaces `utm`:** `utm_source=calculadora&utm_medium=web&utm_campaign=roi-calculator` con `utm_content` = `cta-main-A|cta-main-B|cta-main-conversion` (botón de la tarjeta y su enlace secundario) o `cta-sticky-A|cta-sticky-B|cta-sticky-conversion` (barra fija). (A = llamada, B = conversión forzada con `?ab=B` o variante B si `abTest` es true, conversion = 1-2 miembros.)
- **Eventos `window.dataLayer`** (se crea si no existe; no se carga ningún script externo): `calc_step`, `calc_result` (al llegar al resultado), `calc_cta_view` (primera vez que el botón de la tarjeta se ve, una vez por versión y carga), `calc_cta_click` (botón de la tarjeta y enlace secundario), `calc_sticky_click` (barra fija), `calc_details_open` (se abre la ventana de cálculo), `calc_video_view`, `calc_video_skip`, `calc_testimonial_view` y `calc_testimonial_nav`. Todos llevan solo `variant`, `step`, `sector` y `team_size` (1-2, 3-10, 11+), salvo los de testimonios (solo `variant` y `step`). Nunca cifras ni datos personales.

## Reglas que no se pueden romper
a) **Los números siempre cuadran.** Toda cifra sale de `compute()`; nada de cifras escritas a mano en los textos (en los textos del HTML se usan `{variables}` de `CFG`). El gasto sustituible en herramientas se muestra exactamente como lo introduce el usuario. Las cifras de la tarjeta, la barra fija y la ventana son las mismas.
b) **ES y EN siempre a la par.** Cualquier texto nuevo va en los diccionarios `STATIC` o `D` en los dos idiomas (también el formato de números), y en inglés no puede quedar ninguna palabra en español.
c) **Cero afirmaciones sin respaldo.** Nada de testimonios, cifras de clientes ni urgencia falsa. Lo estimado se marca como estimación y las fuentes se citan. **NUNCA se inventan testimonios, nombres, cargos, empresas, puntuaciones ni cifras de reseñas; solo se muestran los que el dueño del proyecto entregue con consentimiento.**
d) **Marca Fivo.** Azul `#296BDC`, degradado `#5173FF` a `#0AC6FF`, fondo negro, tarjetas `#0A0A0A`, titulares en Outfit y texto en Poppins. No cambiar colores ni fuentes sin pedirlo al usuario.
e) **No relajar la seguridad de `lead.php`** (origen, honeypot, límite por IP, saneado).
f) **No borrar las carpetas `.github` ni `.deploy-now`** si existen (las crea IONOS Deploy Now).
g) **No añadir `package.json`, `composer.json` ni dependencias** en la raíz. Los scripts de prueba van fuera de la carpeta del proyecto.
h) **Flujo de trabajo.** Nunca hacer `git push` sin que el usuario lo pida de forma explícita (por ejemplo "súbelo"). Trabajar en ramas. Tras cada cambio, decir qué archivo se tocó y cómo verlo en local. Antes de cambios grandes, resumir el plan en 3 a 5 líneas.
i) **Sin almacenamiento para el test A/B** (ver arriba).
j) **El resultado es corto y termina en el CTA:** vídeo, empresas, carrusel de testimonios y tarjeta, y después solo el pie; la tarjeta no lleva alturas mínimas ni huecos.
k) **Una sola fuente para el beneficio:** el dinero solo se calcula en `compute()`; la interfaz lee `save`, `earn`, `gross`, `cost` y `net` y no recalcula (ver "Modelo de cálculo").

El usuario no es programador: explicar en español y de forma sencilla.

## PENDIENTES
- Poner las direcciones del vídeo en `CFG.videoUrl` (`es` y `en`; hoy vacías).
- Confirmar **por escrito** que las empresas de la banda de logos autorizan usar su logo y que "ya usan Fivo".
- Conseguir testimonios reales **con permiso por escrito** de cada persona y añadirlos a `CFG.testimonials` con `consent: true` (el carrusel ya está activado con `CFG.showTestimonials = true`, pero hasta entonces solo muestra el relleno en localhost y nada en la web publicada). Definir `CFG.reviews` si se usan reseñas de una tienda (count, storeName y url reales; hoy `null`).
- Conectar una analítica (Google Tag Manager u otra, con consentimiento) para leer los eventos de CTA, barra fija, ventana de cálculo, vídeo y testimonios desde `dataLayer`; si se quiere medir el test A/B, poner `CFG.abTest = true` (con revisión legal si se guarda la variante).
- Dirección real para reservar llamadas (`CFG.callUrl`; ahora https://fivo.ai/contact).
- Precio anual real de Fivo (`CFG.priceAnnual`, ahora 29,99 €, deducido del 25 % de ahorro). El mensual (39,99 €) está confirmado.
- **Texto del botón:** el estilo "clica aquí" se descartó por un botón con verbo ("Reservar mi llamada" / "Book my call", "Prueba 7 días gratis" / "Try free for 7 days"). El texto del botón vive en las claves `ctaBtnA` y `ctaBtnB` del diccionario `D` (ES y EN) por si hay que cambiarlo.
- Decidir si se recuperan los ejemplos de cálculo (`CFG.showExamples`) y, si se hace, revisar sus cifras de muestra.
- Confirmar que existen las rutas `app.fivo.ai/en/auth/`.
- Configurar el webhook en `lead-config.php` y la automatización que envía de verdad el informe por email (el formulario sigue oculto con `leadEndpoint` vacío).
- Revisión legal (consentimiento, política de privacidad, analítica).
- `canonical` y `og:image` (1200×630).
- Un caso real de cliente.
- Excluir la carpeta `dev` del despliegue en `.deploy-now/<proyecto>/config.yaml` al conectar con IONOS.
