/**
 *
 * 1. Read and sort input files by ctime.
 * 2. Request.
 * 3. Save the info and images separately.
 * 4. Go to step 1.
 */
import * as fs from 'node:fs/promises';
import line_notify from './line_notify.js';

await line_notify('Stable Diffusion WebUI keep requesting');

while(1) {
    /**
     * Read and sort input files by ctime.
     */
    const inputs = [];
    const inputFileNames = await fs.readdir('./inputs/');
    for(let i = 0; i < inputFileNames.length; ++i) {
        if(!inputFileNames[i].endsWith('.json')) continue;
        const name = inputFileNames[i].slice(0, -5);
        const inputPath = './inputs/' + inputFileNames[i];
        try {
            const stat = await fs.stat(inputPath);
            let content = await fs.readFile(inputPath);
            content = JSON.parse(content);
            inputs.push({name, ctime: stat.ctimeMs, ...content});
        }
        catch {
            console.warn('unable to load ' + inputFileNames[i]);
        }
    }
    if(inputs.length === 0) {
        console.warn('no valid input file');
        break;
    }
    inputs.sort((a, b) => b.ctime - a.ctime);
    // console.debug(inputs);


    /**
     * Request for each input.
     */
    for(let i = 0; i < inputs.length; ++i) {
        const time = getTime();
        const name = inputs[i].name;
        console.log(time, name);

        const result = await fetch('http://127.0.0.1:7860/sdapi/v1/txt2img', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(inputs[i])
        }).then(res => res.json(), console.error);

        /**
         * Save information or error into one file.
         */
        const outputPath = `./outputs/${name}_${time}.json`;
        const {detail: errors, images, parameters} = result;
        if(errors || !images || !images.length) {
            console.warn('no output');
            await fs.writeFile(outputPath, JSON.stringify(result, null, '\t'));
            continue;
        }
        console.log(`got ${images.length} image` + (images.length > 1 ? 's' : ''));

        const info = JSON.parse(result.info);
        delete info.all_prompts;
        delete info.all_negative_prompts;
        delete info.infotexts;

        if(images.length > 1) line_notify({
            message: info.prompt,
            notificationDisabled: true
        }).catch(console.error);

        await fs.writeFile(outputPath,
            JSON.stringify({parameters, info}, null, '\t')
        );

        /**
         * Save images into multiple files.
         */
        for(let j = 0; j < images.length; j++) {
            const seed = info.all_seeds[j];
            const imagePath = `./outputs/${name}_${time}_${seed}.png`;
            await fs.writeFile(imagePath, images[j], {encoding: 'base64'});
            line_notify({
                message: (images.length > 1) ? seed : info.prompt,
                imageFile: imagePath,
                notificationDisabled: true
            }).catch(console.error);
        }
    }

    break;
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
