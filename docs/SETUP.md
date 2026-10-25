# Address Book – server setup (Linux)

The app is one page (`index.html`) plus a small Node server (`server.js`) that stores all data in a
single JSON file. No database, no npm packages. Only machines on your LAN can connect.

## 1. Requirements
- Linux box with **Node.js 18+** (`node -v`)
- Clients: any current browser on the same network

## 2. Install
```bash
sudo mkdir -p /opt/address-book
sudo cp index.html server.js package.json /opt/address-book/
```

## 3. Try it
```bash
cd /opt/address-book
ALLOW=192.168.0.0/24 DATA_FILE=/tmp/test.json node server.js
```
Open `http://<server-ip>:3000` from another device on the LAN. Stop with Ctrl+C.

### Settings (environment variables)
| Variable | Default | Meaning |
|---|---|---|
| `PORT` | `3000` | Listen port |
| `HOST` | `0.0.0.0` | Bind address. Use `127.0.0.1` when behind a reverse proxy |
| `ALLOW` | `192.168.0.0/24` | Comma-separated CIDRs allowed to connect (loopback is always allowed) |
| `DATA_FILE` | `./data/contacts.json` | Where contacts are stored |

Disallowed clients are dropped at the TCP level before any HTTP is read.

## 4. Run as a service (systemd)
```bash
sudo cp docs/address-book.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now address-book
systemctl status address-book
journalctl -u address-book -f        # logs
```
Edit `ALLOW`, `PORT`, and the `node` path (`which node`) in the unit file if needed.
Data is in `/var/lib/address-book/contacts.json`, with one dated copy per day in
`/var/lib/address-book/backups/` (newest 30 kept).

## 5. Firewall (second layer)
```bash
sudo ufw allow from 192.168.0.0/24 to any port 3000 proto tcp
```

## 6. Using it
- Open `http://<server-ip>:3000` on each device (bookmark it / add to home screen).
- First visit on a device that already has contacts in its browser offers to copy them to the server.
- Import `AddressBook.csv` with **Import CSV**; check the column mapping, then Import.
- If two devices edit at once, the later save is refused and the page reloads the latest data.

## 7. Backups
- In-app **Backup** button downloads a JSON file (includes the return address). **Restore** loads it back.
- Server side: copy `/var/lib/address-book/` with whatever backup you already use (e.g. rsync, restic).
- Never commit `contacts.json` to git (it holds names and home addresses).

## 8. Do I need another web server?
**No.** `server.js` already is the web server. Plain HTTP on a home LAN is fine for this app and
avoids all certificate warnings. The only reason to add one is to get HTTPS.

Lightest option if you want HTTPS: **Caddy** (single binary, ~20 MB RAM, automatic certificates).
nginx also works but needs manual certificate handling.

### HTTPS without browser warnings
Browsers warn when a certificate isn't signed by a CA they trust. For a private LAN there are three ways to avoid it:

1. **Caddy internal CA + install its root certificate on your devices (simplest, no internet needed).**
   Caddy issues certs for `addressbook.lan` (or the IP) from its own CA. Install `root.crt` once on each device
   (Windows: double-click → Trusted Root; Mac: Keychain → Always Trust; iOS/Android: install profile/cert).
   After that there are no warnings.
2. **A real domain you own + Let's Encrypt via DNS challenge.** Create e.g. `book.example.com` pointing to the
   *private* IP (192.168.0.x) in public DNS and let Caddy obtain a real certificate using the DNS-01 challenge
   (needs a Caddy build with your DNS provider's plugin). Trusted everywhere, nothing to install on devices.
3. **Tailscale**: `tailscale serve` / `tailscale cert` gives a trusted HTTPS name. Also gives access away from home.

### Caddy example (option 1)
`/etc/caddy/Caddyfile`:
```
addressbook.lan {
    tls internal
    @lan remote_ip 192.168.0.0/24
    handle @lan {
        reverse_proxy 127.0.0.1:3000
    }
    respond 403
}
```
**Important:** when a proxy sits in front, Node only sees the proxy (127.0.0.1), which is always allowed – so
Node's network filter no longer protects you. Therefore:
- set `HOST=127.0.0.1` in the systemd unit so Node is reachable only through Caddy, and
- keep the `remote_ip` restriction in Caddy (as above) and the firewall rule for port 443 instead of 3000.

The root cert to install is at `/var/lib/caddy/.local/share/caddy/pki/authorities/local/root.crt`.
You also need `addressbook.lan` to resolve (router DNS entry or each device's hosts file).

## Troubleshooting
- **Page loads but data doesn't save / "Could not save to the server"**: check `journalctl -u address-book`.
- **Connection refused/times out from a device**: wrong subnet in `ALLOW`, or firewall.
- **Edits vanish between devices**: you opened the file directly (`file://`) instead of the server URL; that mode uses browser-local storage only.
