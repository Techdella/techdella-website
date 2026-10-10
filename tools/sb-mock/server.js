// Startbuddi mock (§11.5). Accepts form forwards from WordPress, logs them,
// and lets e2e tests read them back. No dependencies.
//
//   POST   /forms/:key      accept a forward (JSON or form-encoded) → 200 {ok:true,id}
//   GET    /_log            every request received, newest last (?key= filters)
//   DELETE /_log            clear the log
//   POST   /_fail           {"key":"audit","times":2,"status":503} → next N forwards for key fail
//   GET    /_health         200
'use strict';
const http = require('node:http');
const { randomUUID } = require('node:crypto');

const PORT = Number(process.env.PORT || 8090);
let log = [];
const failures = new Map(); // key → {times, status}

function send(res, status, body) {
  const json = JSON.stringify(body);
  res.writeHead(status, { 'content-type': 'application/json', 'content-length': Buffer.byteLength(json) });
  res.end(json);
}

function parseBody(raw, type) {
  if (!raw) return null;
  if ((type || '').includes('application/json')) {
    try { return JSON.parse(raw); } catch { return { _invalid_json: raw }; }
  }
  if ((type || '').includes('application/x-www-form-urlencoded')) {
    return Object.fromEntries(new URLSearchParams(raw));
  }
  return { _raw: raw };
}

const server = http.createServer((req, res) => {
  const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
  const chunks = [];
  req.on('data', (c) => chunks.push(c));
  req.on('end', () => {
    const raw = Buffer.concat(chunks).toString('utf8');

    if (url.pathname === '/_health') return send(res, 200, { ok: true });

    if (url.pathname === '/_log') {
      if (req.method === 'DELETE') { log = []; failures.clear(); return send(res, 200, { ok: true }); }
      const key = url.searchParams.get('key');
      return send(res, 200, key ? log.filter((e) => e.key === key) : log);
    }

    if (url.pathname === '/_fail' && req.method === 'POST') {
      const { key, times = 1, status = 503 } = parseBody(raw, 'application/json') || {};
      if (!key) return send(res, 400, { ok: false, error: 'key required' });
      failures.set(key, { times: Number(times), status: Number(status) });
      return send(res, 200, { ok: true });
    }

    const m = url.pathname.match(/^\/forms\/([a-z0-9_-]+)\/?$/);
    if (m && req.method === 'POST') {
      const key = m[1];
      const entry = {
        id: randomUUID(),
        key,
        received_at: new Date().toISOString(),
        headers: {
          'content-type': req.headers['content-type'] || null,
          'user-agent': req.headers['user-agent'] || null,
          'x-techdella-signature': req.headers['x-techdella-signature'] || null,
          'idempotency-key': req.headers['idempotency-key'] || null,
        },
        body: parseBody(raw, req.headers['content-type']),
      };
      const fail = failures.get(key);
      if (fail && fail.times > 0) {
        fail.times -= 1;
        entry.failed_with = fail.status;
        log.push(entry);
        console.log(`[sb-mock] ${key} → forced ${fail.status}`);
        return send(res, fail.status, { ok: false, error: 'forced failure' });
      }
      log.push(entry);
      console.log(`[sb-mock] ${key} ← ${raw.slice(0, 2000)}`);
      return send(res, 200, { ok: true, id: entry.id });
    }

    send(res, 404, { ok: false, error: 'not found' });
  });
});

server.listen(PORT, () => console.log(`[sb-mock] listening on :${PORT}`));
