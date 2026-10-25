'use strict';
// Address book server: serves index.html and stores all data in one JSON file.
// Only accepts connections from allowed networks (default: 192.168.0.0/24, plus loopback).
// Zero dependencies. Usage: node server.js   (env: PORT, DATA_FILE, ALLOW="cidr,cidr")
const http = require('http');
const fs = require('fs');
const path = require('path');
const net = require('net');

const PORT = +process.env.PORT || 3000;
const DATA_FILE = path.resolve(process.env.DATA_FILE || path.join(__dirname, 'data', 'contacts.json'));
const BACKUP_DIR = path.join(path.dirname(DATA_FILE), 'backups');
const ALLOW = (process.env.ALLOW || '192.168.0.0/24').split(',').map(s => s.trim()).filter(Boolean);
const MAX_BODY = 20 * 1024 * 1024;

const allowed = new net.BlockList();
allowed.addSubnet('127.0.0.0', 8, 'ipv4');
allowed.addAddress('::1', 'ipv6');
for (const c of ALLOW) {
  const [addr, bits] = c.split('/');
  allowed.addSubnet(addr, +bits, net.isIPv6(addr) ? 'ipv6' : 'ipv4');
}
function isAllowed(remote) {
  if (!remote) return false;
  const ip = remote.replace(/^::ffff:/i, '');
  return allowed.check(ip, net.isIPv6(ip) ? 'ipv6' : 'ipv4');
}

fs.mkdirSync(BACKUP_DIR, { recursive: true });

function readData() {
  try { return JSON.parse(fs.readFileSync(DATA_FILE, 'utf8')); } catch { return { rev: 0, data: null }; }
}
function writeData(obj) {
  // one dated backup per day of the previous state, keep the newest 30
  if (fs.existsSync(DATA_FILE)) {
    const b = path.join(BACKUP_DIR, `contacts-${new Date().toISOString().slice(0, 10)}.json`);
    if (!fs.existsSync(b)) fs.copyFileSync(DATA_FILE, b);
    const old = fs.readdirSync(BACKUP_DIR).filter(f => f.startsWith('contacts-')).sort().slice(0, -30);
    old.forEach(f => fs.unlinkSync(path.join(BACKUP_DIR, f)));
  }
  const tmp = DATA_FILE + '.tmp';
  fs.writeFileSync(tmp, JSON.stringify(obj));
  fs.renameSync(tmp, DATA_FILE);
}

const server = http.createServer((req, res) => {
  const url = req.url.split('?')[0];
  const send = (code, body, type = 'application/json') => { res.writeHead(code, { 'Content-Type': type, 'Cache-Control': 'no-store' }); res.end(body); };

  if (req.method === 'GET' && (url === '/' || url === '/index.html'))
    return send(200, fs.readFileSync(path.join(__dirname, 'index.html')), 'text/html; charset=utf-8');

  if (url === '/api/data' && req.method === 'GET') return send(200, JSON.stringify(readData()));

  if (url === '/api/data' && req.method === 'PUT') {
    let size = 0; const chunks = [];
    req.on('data', c => { size += c.length; if (size > MAX_BODY) { send(413, '{"error":"too large"}'); req.destroy(); } else chunks.push(c); });
    req.on('end', () => {
      if (size > MAX_BODY) return;
      try {
        const body = JSON.parse(Buffer.concat(chunks).toString('utf8'));
        if (!body || !Array.isArray(body.contacts)) return send(400, '{"error":"bad data"}');
        const cur = readData();
        if (String(cur.rev) !== String(req.headers['if-match'])) return send(409, JSON.stringify({ error: 'conflict', rev: cur.rev }));
        const next = { rev: cur.rev + 1, data: { contacts: body.contacts, returnAddr: String(body.returnAddr || ''), lastBackup: body.lastBackup || null } };
        writeData(next);
        send(200, JSON.stringify({ rev: next.rev }));
      } catch (e) { send(400, '{"error":"bad json"}'); }
    });
    return;
  }
  send(404, '{"error":"not found"}');
});

// Refuse disallowed clients at the TCP level, before any HTTP is parsed.
server.on('connection', sock => { if (!isAllowed(sock.remoteAddress)) sock.destroy(); });

server.listen(PORT, '0.0.0.0', () => {
  console.log(`Address book on port ${PORT}; data: ${DATA_FILE}; allowed: 127.0.0.0/8, ::1, ${ALLOW.join(', ')}`);
});
