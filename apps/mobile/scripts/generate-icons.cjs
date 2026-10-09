// Mechanical resampling of the approved logo; no generative image editing.
// Requires sharp (available in the bundled workspace runtime via NODE_PATH).
const sharp = require('sharp');
const fs = require('node:fs/promises');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const source =
  process.argv[2] || path.join(root, 'assets/branding/app-icon-1024.png');
const bg = '#F8F8F8';
async function writePNG(input, target, size) {
  await fs.mkdir(path.dirname(target), { recursive: true });
  await sharp(input)
    .resize(size, size, { kernel: 'lanczos3' })
    .flatten({ background: bg })
    .removeAlpha()
    .png({ compressionLevel: 9 })
    .toFile(target);
  const m = await sharp(target).metadata();
  assert.equal(m.width, size);
  assert.equal(m.height, size);
  assert.equal(m.hasAlpha, false);
}
async function main() {
  const metadata = await sharp(source).metadata();
  assert.equal(metadata.width, 1024);
  assert.equal(metadata.height, 1024);
  const master = path.join(root, 'assets/branding/app-icon-1024.png');
  const input = await sharp(source)
    .flatten({ background: bg })
    .removeAlpha()
    .png()
    .toBuffer();
  await writePNG(input, master, 1024);
  const ios = path.join(
    root,
    'ios/FoodtrucksUY/Images.xcassets/AppIcon.appiconset',
  );
  const images = [];
  const entries = [
    ['iphone', 20, [2, 3]],
    ['iphone', 29, [2, 3]],
    ['iphone', 40, [2, 3]],
    ['iphone', 60, [2, 3]],
    ['ipad', 20, [1, 2]],
    ['ipad', 29, [1, 2]],
    ['ipad', 40, [1, 2]],
    ['ipad', 76, [1, 2]],
    ['ipad', 83.5, [2]],
    ['ios-marketing', 1024, [1]],
  ];
  for (const [idiom, size, scales] of entries) {
    for (const scale of scales) {
      const pixels = size * scale;
      const filename = `icon-${pixels}.png`;
      await writePNG(input, path.join(ios, filename), pixels);
      images.push({
        idiom,
        size: `${size}x${size}`,
        scale: `${scale}x`,
        filename,
      });
    }
  }
  await fs.writeFile(
    path.join(ios, 'Contents.json'),
    JSON.stringify({ images, info: { author: 'xcode', version: 1 } }, null, 2) +
      '\n',
  );
  const { data: rgb } = await sharp(input)
    .raw()
    .toBuffer({ resolveWithObject: true });
  const mono = Buffer.alloc(1024 * 1024 * 4);
  for (let pixel = 0; pixel < 1024 * 1024; pixel++) {
    // Retain only colored ink as alpha; leave white details/negative space open.
    const minimum = Math.min(
      rgb[pixel * 3],
      rgb[pixel * 3 + 1],
      rgb[pixel * 3 + 2],
    );
    mono[pixel * 4 + 3] = Math.max(
      0,
      Math.min(255, Math.round(((248 - minimum) * 255) / 239)),
    );
  }
  const monoPNG = await sharp(mono, {
    raw: { width: 1024, height: 1024, channels: 4 },
  })
    .png()
    .toBuffer();
  const res = path.join(root, 'android/app/src/main/res');
  for (const [density, scale] of [
    ['mdpi', 1],
    ['hdpi', 1.5],
    ['xhdpi', 2],
    ['xxhdpi', 3],
    ['xxxhdpi', 4],
  ]) {
    const notificationDir = path.join(res, `drawable-${density}`);
    await fs.mkdir(notificationDir, { recursive: true });
    await sharp(monoPNG)
      .resize(24 * scale, 24 * scale)
      .png()
      .toFile(path.join(notificationDir, 'ic_stat_foodtruck.png'));
    const directory = path.join(res, `mipmap-${density}`);
    await writePNG(input, path.join(directory, 'ic_launcher.png'), 48 * scale);
    // Pre-26 round fallback: keep original margins and clip only the background.
    const side = 48 * scale;
    const mask = Buffer.from(
      `<svg width="${side}" height="${side}"><circle cx="${side / 2}" cy="${
        side / 2
      }" r="${side / 2}" fill="white"/></svg>`,
    );
    await sharp(input)
      .resize(side, side)
      .ensureAlpha()
      .composite([{ input: mask, blend: 'dest-in' }])
      .png()
      .toFile(path.join(directory, 'ic_launcher_round.png'));
    const edge = 108 * scale;
    const content = 66 * scale;
    const offset = Math.round((edge - content) / 2);
    // Colored content fits inside the centered 66dp safe zone on a 108dp layer.
    for (const [name, image] of [
      ['foreground', input],
      ['monochrome', monoPNG],
    ]) {
      const layer = await sharp(image)
        .resize(content, content)
        .png()
        .toBuffer();
      await sharp({
        create: {
          width: edge,
          height: edge,
          channels: 4,
          background: { r: 248, g: 248, b: 248, alpha: 0 },
        },
      })
        .composite([{ input: layer, left: offset, top: offset }])
        .png()
        .toFile(path.join(directory, `ic_launcher_${name}.png`));
    }
  }
  await writePNG(
    input,
    path.join(root, 'assets/branding/google-play-icon-512.png'),
    512,
  );
  const mask = Buffer.from(
    '<svg width="288" height="288"><circle cx="144" cy="144" r="144" fill="white"/></svg>',
  );
  const adaptive = await sharp(
    path.join(res, 'mipmap-xxxhdpi/ic_launcher_foreground.png'),
  )
    .flatten({ background: bg })
    .extract({ left: 72, top: 72, width: 288, height: 288 })
    .ensureAlpha()
    .composite([{ input: mask, blend: 'dest-in' }])
    .png()
    .toBuffer();
  const iosMask = Buffer.from(
    '<svg width="288" height="288"><rect width="288" height="288" rx="60" fill="white"/></svg>',
  );
  const iosPreview = await sharp(input)
    .resize(288, 288)
    .ensureAlpha()
    .composite([{ input: iosMask, blend: 'dest-in' }])
    .png()
    .toBuffer();
  const titles = Buffer.from(
    '<svg width="660" height="355"><style>text{font-family:sans-serif;font-size:18px;fill:#28231F}</style><text x="170" y="333" text-anchor="middle">iOS</text><text x="490" y="333" text-anchor="middle">Android circular</text></svg>',
  );
  await sharp({
    create: { width: 660, height: 355, channels: 4, background: '#EEE5DB' },
  })
    .composite([
      { input: iosPreview, left: 26, top: 16 },
      { input: adaptive, left: 346, top: 16 },
      { input: titles },
    ])
    .png()
    .toFile(path.join(root, 'assets/branding/icon-preview.png'));
  console.log(
    'Generated and verified iPhone/iPad/store and Android density/adaptive/monochrome/notification icons.',
  );
}
main().catch(error => {
  console.error(error.message);
  process.exitCode = 1;
});
