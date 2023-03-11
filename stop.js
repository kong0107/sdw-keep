// import * as fs from 'node:fs/promises';
import { writeFile } from 'node:fs/promises';

writeFile('./inputs/stop', 'kong0107');
fetch('http://127.0.0.1:7860/sdapi/v1/interrupt', {
    method: 'POST'
}).then(res => console.log(res.ok));
