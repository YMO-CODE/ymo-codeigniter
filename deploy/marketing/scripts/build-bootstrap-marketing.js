#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const { PurgeCSS } = require('purgecss');
const fg = require('fast-glob');

const root = path.resolve(__dirname, '../../..');
const cssInput = path.join(root, 'public/assets/vendor/bootstrap/css/bootstrap.min.css');
const cssOutput = path.join(root, 'public/assets/css/bootstrap-marketing.min.css');

const contentGlobs = [
  'application/views/marketing/**/*.php',
  'application/views/layout/marketing.php',
  'application/views/layout/partials/public_header.php',
  'application/views/layout/partials/marketing_*.php',
  'application/views/layout/partials/whatsapp_cta.php',
  'application/views/layout/partials/flash.php',
  'public/assets/js/ymo-marketing.js',
  'public/assets/css/marketing.css',
  'public/assets/css/ymo.css',
  'application/config/marketing_pages_data.php',
  'application/config/marketing_pages_option_a.php',
  'application/helpers/marketing_seo_growth_helper.php',
  'application/helpers/marketing_seo_enhancements_helper.php',
];

const safelist = [
  'show', 'active', 'fade', 'collapsing', 'collapse',
  'modal-backdrop', 'offcanvas-backdrop',
  'carousel-item-next', 'carousel-item-prev', 'carousel-item-start', 'carousel-item-end',
  /^carousel/, /^offcanvas/, /^navbar/, /^nav-/, /^btn-close/,
  /^alert/, /^form-floating/, /^table/, /^col-/, /^row/, /^container/,
  /^d-/, /^flex-/, /^align-/, /^justify-/, /^gap-/, /^g-/, /^gy-/, /^gx-/,
  /^p[trblxye]?-/, /^m[trblxye]?-/, /^w-/, /^h-/, /^text-/, /^opacity-/,
  /^visually-hidden/, /^clearfix/, /^small/, /^list-unstyled/,
  /^offset-/, /^order-/, /^float-/, /^position-/, /^border/, /^rounded/,
  /^vc_/, /^wpb_/, /^page-margin/, /^full-width/, /^accordion/,
];

async function main() {
  const content = fg.sync(contentGlobs, { cwd: root, absolute: true });
  if (!content.length) {
    console.error('No content files matched for PurgeCSS');
    process.exit(1);
  }
  console.log(`Scanning ${content.length} files…`);

  const purgeCSS = new PurgeCSS();
  const result = await purgeCSS.purge({
    content,
    css: [cssInput],
    fontFace: true,
    keyframes: true,
    variables: true,
    safelist: {
      standard: safelist,
      deep: [/carousel/, /offcanvas/, /collapse/],
    },
  });

  if (!result[0] || !result[0].css) {
    console.error('PurgeCSS returned no CSS');
    process.exit(1);
  }

  fs.writeFileSync(cssOutput, result[0].css, 'utf8');
  const outKb = (fs.statSync(cssOutput).size / 1024).toFixed(1);
  const inKb = (fs.statSync(cssInput).size / 1024).toFixed(1);
  console.log(`bootstrap-marketing.min.css: ${outKb} KiB (from ${inKb} KiB bootstrap.min.css)`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
