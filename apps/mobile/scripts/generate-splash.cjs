// Mechanical resampling of the approved wordmark, no redrawing.
const sharp = require('sharp');
const fs = require('node:fs/promises');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
async function main() {
  const source =
    process.argv[2] || path.join(root, 'assets/branding/splash-logo-1024.png');
  const input = await sharp(source)
    .flatten({ background: '#F8F8F8' })
    .removeAlpha()
    .png()
    .toBuffer();
  const metadata = await sharp(input).metadata();
  if (metadata.width !== 1024 || metadata.height !== 1024) {
    throw new Error('Expected 1024 square source');
  }
  await fs.writeFile(
    path.join(root, 'assets/branding/splash-logo-1024.png'),
    input,
  );
  const asset = path.join(
    root,
    'ios/FoodtrucksUY/Images.xcassets/SplashLogo.imageset',
  );
  await fs.mkdir(asset, { recursive: true });
  const images = [];
  for (const scale of [1, 2, 3]) {
    const filename = `splash-${scale}x.png`;
    await sharp(input)
      .resize(320 * scale, 320 * scale)
      .png()
      .toFile(path.join(asset, filename));
    images.push({ idiom: 'universal', scale: `${scale}x`, filename });
  }
  await fs.writeFile(
    path.join(asset, 'Contents.json'),
    JSON.stringify({ images, info: { author: 'xcode', version: 1 } }, null, 2) +
      '\n',
  );
  const res = path.join(root, 'android/app/src/main/res');
  for (const [density, scale] of [
    ['mdpi', 1],
    ['hdpi', 1.5],
    ['xhdpi', 2],
    ['xxhdpi', 3],
    ['xxxhdpi', 4],
  ]) {
    const directory = path.join(res, `drawable-${density}`);
    await sharp(input)
      .resize(280 * scale, 280 * scale)
      .png()
      .toFile(path.join(directory, 'splash_logo.png'));
    const side = 288 * scale,
      logo = 164 * scale;
    const resized = await sharp(input).resize(logo, logo).png().toBuffer();
    await sharp({
      create: { width: side, height: side, channels: 3, background: '#F8F8F8' },
    })
      .composite([
        { input: resized, left: (side - logo) / 2, top: (side - logo) / 2 },
      ])
      .png()
      .toFile(path.join(directory, 'splash_system_logo.png'));
  }
  await sharp({
    create: { width: 390, height: 844, channels: 3, background: '#F8F8F8' },
  })
    .composite([
      {
        input: await sharp(input).resize(280, 280).png().toBuffer(),
        left: 55,
        top: 282,
      },
    ])
    .png()
    .toFile(path.join(root, 'assets/branding/splash-preview.png'));
  console.log('Splash assets generated for both platforms.');
}
main().catch(error => {
  console.error(error.message);
  process.exitCode = 1;
});
