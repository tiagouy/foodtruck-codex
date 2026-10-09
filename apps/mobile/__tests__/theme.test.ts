import { colors } from '../src/theme';

function luminance(hex: string) {
  const rgb = hex.match(/[a-f\d]{2}/gi)!.map(value => {
    const channel = parseInt(value, 16) / 255;
    return channel <= 0.04045
      ? channel / 12.92
      : Math.pow((channel + 0.055) / 1.055, 2.4);
  });
  return rgb[0] * 0.2126 + rgb[1] * 0.7152 + rgb[2] * 0.0722;
}
function contrast(a: string, b: string) {
  const values = [luminance(a), luminance(b)].sort((x, y) => y - x);
  return (values[0] + 0.05) / (values[1] + 0.05);
}

test('uses the exact approved logo colors', () => {
  expect(colors.accent).toBe('#FC590B');
  expect(colors.dark).toBe('#05204B');
  expect(colors.background).toBe('#F8F8F8');
});

test('normal text and button labels keep sufficient contrast', () => {
  for (const background of [colors.background, colors.surface]) {
    for (const foreground of [colors.dark, colors.muted, colors.accentText]) {
      expect(contrast(foreground, background)).toBeGreaterThanOrEqual(4.5);
    }
  }
  expect(contrast(colors.onAccent, colors.accent)).toBeGreaterThanOrEqual(4.5);
  expect(contrast(colors.accent, colors.dark)).toBeGreaterThanOrEqual(4.5);
});
