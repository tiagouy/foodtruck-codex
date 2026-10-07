// Standard development-only signing material; never overwrite an existing key.
const { existsSync } = require('node:fs');
const { resolve } = require('node:path');
const { spawnSync } = require('node:child_process');
const target = resolve(__dirname, '../android/app/debug.keystore');
if (existsSync(target)) {
  console.log('Keystore de desarrollo existente: conservado.');
} else {
  const result = spawnSync(
    'keytool',
    [
      '-genkeypair',
      '-keystore',
      target,
      '-storepass',
      'android',
      '-keypass',
      'android',
      '-alias',
      'androiddebugkey',
      '-keyalg',
      'RSA',
      '-keysize',
      '2048',
      '-validity',
      '10000',
      '-dname',
      'CN=Android Debug,O=Android,C=US',
    ],
    { stdio: 'inherit' },
  );
  if (result.error || result.status !== 0) {
    throw new Error(
      'No se pudo crear el keystore de desarrollo; verificá JDK 17/keytool.',
    );
  }
}
