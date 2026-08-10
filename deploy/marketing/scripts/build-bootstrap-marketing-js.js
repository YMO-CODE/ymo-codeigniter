#!/usr/bin/env node
'use strict';

const path = require('path');
const esbuild = require('esbuild');

const root = path.resolve(__dirname, '../../..');
const entry = path.join(root, 'deploy/marketing/bootstrap-marketing-entry.js');
const outfile = path.join(root, 'public/assets/js/bootstrap-marketing.min.js');
const fallback = path.join(root, 'public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js');

async function main() {
  await esbuild.build({
    entryPoints: [entry],
    bundle: true,
    minify: true,
    format: 'iife',
    target: ['es2018'],
    outfile,
    legalComments: 'none',
  });

  const outKb = (require('fs').statSync(outfile).size / 1024).toFixed(1);
  const inKb = (require('fs').statSync(fallback).size / 1024).toFixed(1);
  console.log(`bootstrap-marketing.min.js: ${outKb} KiB (full bundle is ${inKb} KiB)`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
