// Servidor local SOLO para ver la calculadora en tu ordenador. No se usa en el servidor real.
// Uso:  node dev/server.js   →   http://localhost:5050
const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = 5050;
const ROOT = path.resolve(__dirname, '..');
const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.png': 'image/png',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8'
};
const PRIVATE_DIRS = ['dev', '.git', '.github', '.deploy-now', 'node_modules'];

const server = http.createServer((req, res) => {
  let url = '/';
  try { url = decodeURIComponent(req.url.split('?')[0]); } catch (e) {}

  // Formulario "Enviarme mi informe": en local solo se simula (no se envía ningún email)
  if (req.method === 'POST' && url === '/lead.php') {
    let body = '';
    req.on('data', (c) => { body += c; if (body.length > 50000) req.destroy(); });
    req.on('end', () => {
      try {
        const d = JSON.parse(body);
        console.log('\n[PRUEBA] Formulario recibido:', d.email, '| beneficio neto:', d.results && d.results.net);
      } catch (e) {}
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ok: true }));
    });
    return;
  }

  const rel = url === '/' ? '/index.html' : url;
  const file = path.normalize(path.join(ROOT, rel));
  const relPath = path.relative(ROOT, file);
  const inside = relPath !== '' && !relPath.startsWith('..') && !path.isAbsolute(relPath);
  const topDir = relPath.split(path.sep)[0].toLowerCase();
  const ext = path.extname(file).toLowerCase();
  if (!inside || PRIVATE_DIRS.includes(topDir) || !TYPES[ext]) { res.writeHead(404); return res.end('404'); }
  fs.readFile(file, (err, data) => {
    if (err) { res.writeHead(404); return res.end('404'); }
    res.writeHead(200, { 'Content-Type': TYPES[ext] });
    res.end(data);
  });
});

server.on('error', (e) => {
  if (e.code === 'EADDRINUSE') console.error('El puerto ' + PORT + ' ya está en uso. Cierra la otra ventana donde se esté ejecutando y vuelve a probar.');
  else console.error(e);
  process.exit(1);
});
server.listen(PORT, () => console.log('Calculadora de Fivo lista en http://localhost:' + PORT));
