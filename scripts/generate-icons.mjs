// Generates simple placeholder PWA icons (solid brand square + a lighter glyph circle).
// Pure Node (zlib only) PNG encoder so we need no image libraries. Real art is a later task.
import { writeFileSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';
import { deflateSync } from 'node:zlib';

const BRAND = [99, 102, 241]; // indigo-500
const GLYPH = [238, 242, 255]; // indigo-50

function crc32(buf) {
    let c = ~0;

    for (let i = 0; i < buf.length; i++) {
        c ^= buf[i];

        for (let k = 0; k < 8; k++) {
c = (c >>> 1) ^ (0xedb88320 & -(c & 1));
}
    }

    return ~c >>> 0;
}

function chunk(type, data) {
    const len = Buffer.alloc(4);
    len.writeUInt32BE(data.length, 0);
    const typeBuf = Buffer.from(type, 'ascii');
    const crc = Buffer.alloc(4);
    crc.writeUInt32BE(crc32(Buffer.concat([typeBuf, data])), 0);

    return Buffer.concat([len, typeBuf, data, crc]);
}

function png(size, { maskable = false } = {}) {
    const cx = size / 2;
    const cy = size / 2;
    // Maskable icons need their glyph inside the ~80% safe zone.
    const r = size * (maskable ? 0.26 : 0.32);

    const raw = Buffer.alloc(size * (size * 4 + 1));
    let p = 0;

    for (let y = 0; y < size; y++) {
        raw[p++] = 0; // filter byte per scanline

        for (let x = 0; x < size; x++) {
            const dist = Math.hypot(x - cx, y - cy);
            const [rr, gg, bb] = dist <= r ? GLYPH : BRAND;
            raw[p++] = rr;
            raw[p++] = gg;
            raw[p++] = bb;
            raw[p++] = 255;
        }
    }

    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(size, 0);
    ihdr.writeUInt32BE(size, 4);
    ihdr[8] = 8; // bit depth
    ihdr[9] = 6; // RGBA
    const sig = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);

    return Buffer.concat([
        sig,
        chunk('IHDR', ihdr),
        chunk('IDAT', deflateSync(raw)),
        chunk('IEND', Buffer.alloc(0)),
    ]);
}

const targets = [
    ['public/icons/icon-192.png', png(192)],
    ['public/icons/icon-512.png', png(512)],
    ['public/icons/maskable-512.png', png(512, { maskable: true })],
    ['public/icons/apple-touch-icon.png', png(180)],
];

for (const [path, buf] of targets) {
    mkdirSync(dirname(path), { recursive: true });
    writeFileSync(path, buf);
    console.log('wrote', path, buf.length, 'bytes');
}
