// Mechanical conversion of the user's local credential document; no values logged.
const { execFileSync } = require('node:child_process');
const { writeFileSync } = require('node:fs');
const { resolve } = require('node:path');
const text = execFileSync('/usr/bin/textutil', ['-convert', 'txt', '-stdout',
  resolve(__dirname, '../../../dato.txt.rtf')], { encoding: 'utf8' });
const appToken = text.match(/Token\s+App\s*:\s*(\S+)/i)?.[1];
const apiKey = text.match(/push\s+api\s+key\s*[:=]?\s+(\S+)/i)?.[1];
if (!appToken || !apiKey || !/^ID:\s*12\s*$/m.test(text)) {
  throw new Error('Revisar formato local de configuración push; no se muestran valores.');
}
writeFileSync(resolve(__dirname, '../pushv3.local.json'), JSON.stringify({
  apiUrl: 'https://useful-media-push.org/pushv3/api/', appId: '12', appToken, apiKey,
}, null, 2) + '\n', { mode: 0o600 });
console.log('Configuración push local generada; valores ocultos.');
