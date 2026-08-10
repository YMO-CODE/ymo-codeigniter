#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const { transform } = require('esbuild');

const root = path.resolve(__dirname, '../../..');
const input = path.join(root, 'public/assets/css/ymo.css');
const output = path.join(root, 'public/assets/css/ymo.min.css');

async function main() {
  if (!fs.existsSync(input)) {
    console.error('Missing', input);
    process.exit(1);
  }

  const result = await transform(fs.readFileSync(input, 'utf8'), {
    loader: 'css',
    minify: true,
  });

  fs.writeFileSync(output, result.code, 'utf8');
  const inKb = (fs.statSync(input).size / 1024).toFixed(1);
  const outKb = (fs.statSync(output).size / 1024).toFixed(1);
  console.log(`ymo.min.css: ${outKb} KiB (from ${inKb} KiB ymo.css)`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
