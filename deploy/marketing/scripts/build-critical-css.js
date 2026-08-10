#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const { PurgeCSS } = require('purgecss');
const { transform } = require('esbuild');
const fg = require('fast-glob');

const root = path.resolve(__dirname, '../../..');
const cssInputs = [
  path.join(root, 'public/assets/css/bootstrap-marketing.min.css'),
  path.join(root, 'public/assets/css/ymo.css'),
  path.join(root, 'public/assets/css/marketing.css'),
];
const cssOutput = path.join(root, 'public/assets/css/marketing-critical.min.css');

const contentGlobs = [
  'application/views/layout/partials/public_header.php',
  'application/views/layout/partials/marketing_header.php',
  'application/views/layout/partials/flash.php',
  'application/views/layout/partials/whatsapp_cta.php',
  'application/views/marketing/partials/hero_home.php',
  'application/views/marketing/partials/hero_image.php',
  'application/views/marketing/partials/hero_trust_proof.php',
  'application/views/marketing/partials/hero_actions.php',
  'application/views/marketing/home.php',
];

const safelist = {
  standard: [
    'show', 'active', 'fade', 'collapse', 'collapsing',
    'offcanvas', 'offcanvas-end', 'offcanvas-backdrop',
    'navbar', 'navbar-expand-lg', 'navbar-collapse', 'navbar-toggler',
    'md-theme', 'ymo-marketing', 'is-hover-open', 'is-active',
  ],
  deep: [/offcanvas/, /navbar/, /ymo-hero/, /ymo-nav/, /ymo-topbar/, /ymo-drawer/],
  greedy: [/^::?(before|after)/],
};

async function main() {
  for (const file of cssInputs) {
    if (!fs.existsSync(file)) {
      console.error('Missing CSS input:', file);
      process.exit(1);
    }
  }

  const content = fg.sync(contentGlobs, { cwd: root, absolute: true });
  if (!content.length) {
    console.error('No content files matched for critical CSS');
    process.exit(1);
  }

  console.log(`Extracting critical CSS from ${content.length} templates…`);

  const purgeCSS = new PurgeCSS();
  const parts = [];
  for (const cssFile of cssInputs) {
    const result = await purgeCSS.purge({
      content,
      css: [cssFile],
      fontFace: true,
      keyframes: false,
      variables: true,
      safelist,
    });
    if (result[0] && result[0].css) {
      parts.push(result[0].css);
    }
  }

  const merged = parts.join('\n');
  const minified = await transform(merged, { loader: 'css', minify: true });
  fs.writeFileSync(cssOutput, minified.code, 'utf8');
  const outKb = (fs.statSync(cssOutput).size / 1024).toFixed(1);
  console.log(`marketing-critical.min.css: ${outKb} KiB`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
