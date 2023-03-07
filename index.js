/**
 *
 * 1. Read and sort input files by ctime.
 * 2. Request.
 * 3. Save the info and images separately.
 * 4. Go to step 1.
 */
import * as http from 'node:http';
import * as fs from 'node:fs/promises';

while(1) {
    /**
     * Read and sort input files by ctime.
     */
    const inputs = [];
    const inputFileNames = await fs.readdir('./inputs/');
    if(inputFileNames.length === 0) {
        console.warn('no input file');
        break;
    }
    for(let i = 0; i < inputFileNames.length; ++i) {
        const inputPath = './inputs/' + inputFileNames[i];
        const stat = await fs.stat(inputPath);
        const content = await fs.readFile(inputPath);
        inputs.push({name: inputFileNames[i], ctime: stat.ctimeMs, ...JSON.parse(content)});
    }
    inputs.sort((a, b) => b.ctime - a.ctime);
    // console.debug(inputs);

    /**
     * Request for each input.
     */
    for(let i = 0; i < inputs.length; ++i) {
        const time = getTime();
        console.log(time, inputs[i].name);

        let result = await httpRequest({
            host: '127.0.0.1',
            port: 7860,
            path: '/sdapi/v1/txt2img',
            method: 'POST',
            body: inputs[i]
        });
        console.debug('got response');

        const {images, parameters, info, detail: errors} = result;
        if(errors || !images || !images.length) {
            console.warn('no output');
            await fs.writeFile(`./outputs/${time}.json`, JSON.stringify(result, null, '\t'));
            continue;
        }

        await fs.writeFile(`./outputs/${time}.json`,
            JSON.stringify({
                input: inputs[i].name,
                parameters,
                info: JSON.parse(info)
            }, null, '\t')
        );

        for(let j = 0; j < images.length; j++) {
            await fs.writeFile(`./outputs/${time}_${j}.png`, images[j], {encoding: 'base64'});
        }
    }
    // break;
}

/**
 *
 * @param {Object} options
 * @returns {Promise.<Object>}
 */
function httpRequest(options) {
    return new Promise((resolve, reject) => {
        const req = http.request(options, response => {
            let rawData = '';
            response.on('data', chunk => rawData += chunk);
            response.on('end', () => {
                try {
                    resolve(JSON.parse(rawData));
                } catch(err) {
                    reject(err);
                }
            });
        });
        req.write(JSON.stringify(options.body));
        req.on('error', reject);
        req.end();
    });
}

function getTime(d = null) {
    if(!d) d = new Date();
    return (d.getYear() - 100).toString(36)
        + (d.getMonth() + 1).toString(36)
        + d.getDate().toString(36)
        + '_'
        + d.getHours().toString().padStart(2, '0')
        + d.getMinutes().toString().padStart(2, '0')
        + d.getSeconds().toString().padStart(2, '0')
    ;
}
