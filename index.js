/**
 *
 * 1. Read and sort input files by ctime.
 * 2. Request.
 * 3. Save the info and images separately.
 *    `./outputs/<YYYY-MM-DD>/<name>-<HHmmss>.json`
 *    `./outputs/<YYYY-MM-DD>/<name>-<HHmmss>-<seed>.png`
 * 4. Go to step 1.
 */
import * as fs from 'node:fs/promises';
import lineNotify from './line_notify.js';
import httpRequest from './http_request.js'; // note: fetch() has timeout limit 300 seconds.

const config = JSON.parse(await fs.readFile('./config.json'));

// await lineNotify('Stable Diffusion WebUI keep requesting');
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
            if(content.disabled) continue;
            inputs.push({name, ctime: stat.ctimeMs, ...content});
        }
        catch {
            console.warn('Unable to load ' + inputFileNames[i]);
        }
    }
    if(inputs.length === 0) {
        console.warn('No valid input file');
        break;
    }
    inputs.sort((a, b) => b.ctime - a.ctime);
    console.log(inputs.map(i => i.name + ' \xd7' + (i.batch_size * i.n_iter)).join(', '));
    // console.debug(inputs);

    /**
     * Request for each input.
     */
    for(let i = 0; i < inputs.length; ++i) {
        try { // If the file exists, stop the program.
            await fs.unlink('./inputs/stop');
            inputs.splice(0, Infinity);
            console.log('Interrupted');
            break;
        } catch {}

        const d = new Date();
        const date = [d.getFullYear(), pad2(d.getMonth() + 1), pad2(d.getDate())].join('-'); // `YYYY-MM-DD` with hyphens
        const time = pad2(d.getHours()) + pad2(d.getMinutes()) + pad2(d.getSeconds()); // `HHmmss` without colons
        fs.mkdir('./outputs/' + date, {recursive: true});

        const name = inputs[i].name;
        const image_quantity = inputs[i].batch_size * inputs[i].n_iter;
        const plural = (image_quantity > 1) ? 's': '';
        const intervalID = setInterval(() => process.stdout.write('.'), 3000);
        console.time(name);
        console.log(`Requesting for ${image_quantity} ${name} image${plural} with size ${inputs[i].width}\xd7${inputs[i].height}`);

        let result;
        try {
            // if(i) await new Promise(resolve => setTimeout(resolve, 3000));

            result = await httpRequest(config.serverOrigin + '/sdapi/v1/txt2img', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(inputs[i])
            });
            clearInterval(intervalID);
            process.stdout.write('\n');

            if(result.statusCode !== 200) { // server responsed, but error
                console.warn(result.statusCode, result.statusMessage);
                lineNotify(result);
                result = {detail: [result]};
            }
            else result = JSON.parse(result.body);
        }
        catch(err) { // no such server
            clearInterval(intervalID);
            process.stdout.write('\n');
            console.error(err);
            lineNotify(err.toString(), config.lineToken);
            inputs.splice(0, Infinity);
            break;
        }

        /**
         * Save information or error into one file.
         */
        const outputPath = `./outputs/${date}/${name}-${time}.json`;
        const {detail: errors, images, parameters} = result;
        if(errors || !images || !images.length) {
            console.warn('no output');
            await fs.writeFile(outputPath, JSON.stringify(result, null, '\t'));
            continue;
        }
        console.timeEnd(name);
        console.log(`Received ${images.length} image` + (images.length > 1 ? 's' : ''));

        const info = JSON.parse(result.info);
        delete info.all_prompts;
        delete info.all_negative_prompts;
        delete info.infotexts;

        const output = {
            parameters,
            info,
            model: parameters?.override_settings?.sd_model_checkpoint
        };
        if(!output.model) {
            const models = await fetch(config.serverOrigin + '/sdapi/v1/sd-models').then(res => res.json());
            output.model = models[models.length - 1].title;
            console.log('by using default model', output.model);
        }

        await fs.writeFile(outputPath,
            JSON.stringify(output, null, '\t')
        );

        /**
         * Save images into multiple files and send LINE notify.
         */
        const message = [
            name,
            output.model,
            info.sampler_name,
            info.prompt
        ].join('\n');
        for(let j = 0; j < images.length; j++) {
            const seed = info.all_seeds[j];
            const imagePath = `./outputs/${date}/${name}-${time}-${seed}.png`;
            await fs.writeFile(imagePath, images[j], {encoding: 'base64'});
            await lineNotify({
                message: j ? ' ' : message,
                imageFile: imagePath,
                token: config.lineToken
            }).catch(console.error);
        }
    }

    console.log('\n');
    if(!inputs.length)
        break;

    // await new Promise(resolve => setTimeout(resolve, 10000));
}

function pad2(num) {
    return num.toString().padStart(2, '0');
}
