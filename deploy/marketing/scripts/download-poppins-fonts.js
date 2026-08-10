#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const https = require('https');

const root = path.resolve(__dirname, '../../..');
const fontsDir = path.join(root, 'public/assets/fonts');
const files = [
  {
    url: 'https://fonts.gstatic.com/s/poppins/v24/pxiEyp8kv8JHgFVrJJfecg.woff2',
    name: 'poppins-latin-400.woff2',
  },
  {
    url: 'https://fonts.gstatic.com/s/poppins/v24/pxiByp8kv8JHgFVrLEj6Z1xlFQ.woff2',
    name: 'poppins-latin-600.woff2',
  },
  {
    url: 'https://fonts.gstatic.com/s/poppins/v24/pxiByp8kv8JHgFVrLCz7Z1xlFQ.woff2',
    name: 'poppins-latin-700.woff2',
  },
];

function download(url, dest) {
  return new Promise((resolve, reject) => {
    https.get(url, (res) => {
      if (res.statusCode !== 200) {
        reject(new Error(`HTTP ${res.statusCode} for ${url}`));
        return;
      }
      const chunks = [];
      res.on('data', (c) => chunks.push(c));
      res.on('end', () => {
        fs.writeFileSync(dest, Buffer.concat(chunks));
        resolve(fs.statSync(dest).size);
      });
    }).on('error', reject);
  });
}

async function main() {
  fs.mkdirSync(fontsDir, { recursive: true });
  for (const file of files) {
    const dest = path.join(fontsDir, file.name);
    const bytes = await download(file.url, dest);
    console.log(`  ${file.name} (${Math.round(bytes / 1024)} KiB)`);
  }
  console.log('Poppins woff2 files ready in public/assets/fonts/');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
