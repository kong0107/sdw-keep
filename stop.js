// import * as fs from 'node:fs/promises';
import { readFile, writeFile } from 'node:fs/promises';

const config = JSON.parse(await readFile('./config.json'));

writeFile('./inputs/stop', 'kong0107');
fetch(config.serverOrigin + '/sdapi/v1/interrupt', {
    method: 'POST'
}).then(res => console.log(res.ok));
