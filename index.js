/**
 *
 * 1. Read and sort input settings.
 * 2. Request.
 * 3. Save the info and images separately.
 *    `./outputs/<YYYY-MM-DD>/<name>-<HHmmss>.json`
 *    `./outputs/<YYYY-MM-DD>/<name>-<HHmmss>-<seed>.png`
 * 4. Go to step 1.
 */
import * as fs from 'node:fs/promises';
import showElapsedTime from './show_elapsed_time.js';
import httpRequest from './http_request.js'; // note: fetch() has timeout limit 300 seconds.

import lineNotify from './line_notify.js';
import sendDiscordMessage from './discord_webhook.js';

const config = JSON.parse(await fs.readFile('./config.json'));

while(1) {
    /**
     * Read and sort input files by model.
     * Check this at the beginning of every loop.
     */
    const inputs = [];
    const inputFileNames = await fs.readdir('./inputs/');
    for(let i = 0; i < inputFileNames.length; ++i) {
        if(!inputFileNames[i].endsWith('.json')) continue;
        const name = inputFileNames[i].slice(0, -5);
        const inputPath = './inputs/' + inputFileNames[i];
        try {
            const content = JSON.parse(await fs.readFile(inputPath));
            if(content.disabled) continue;
            const model = content?.override_settings?.sd_model_checkpoint;
            if(model) content.override_settings_restore_afterwards = false;
            inputs.push({name, model, ...content});
        }
        catch {
            console.warn('Unable to load ' + inputFileNames[i]);
        }
    }
    if(inputs.length === 0) {
        console.log('No valid input file. Check later.');
        await new Promise(resolve => setTimeout(resolve, 6000));
        continue;
    }
    inputs.sort((a, b) => a.model > b.model ? 1 : -1);
    console.log(inputs.map(i => i.name + ' \xd7' + (i.batch_size * i.n_iter)).join(', ') + '\n');
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
        await fs.mkdir('./outputs/' + date, {recursive: true});

        const name = inputs[i].name;
        const image_quantity = inputs[i].batch_size * inputs[i].n_iter;
        const plural = (image_quantity > 1) ? 's': '';
        const stopTimer = showElapsedTime();
        console.log(`Requesting for ${image_quantity} ${name} image${plural} with size ${inputs[i].width}\xd7${inputs[i].height}`);
        console.log('prompt: ' + inputs[i].prompt.substring(0, 60) + '...');

        let result;
        try {
            if(i) await new Promise(resolve => setTimeout(resolve, 100));
            result = await httpRequest(config.serverOrigin + '/sdapi/v1/txt2img', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(inputs[i])
            });
            stopTimer();

            if(result.statusCode !== 200) { // server responsed, but error
                console.warn(result.statusCode, result.statusMessage);
                result = {detail: [result]};
            }
            else {
                fs.writeFile("./last_result.json", result.body);
                result = JSON.parse(result.body);
            }
        }
        catch(err) { // no such server
            stopTimer();
            console.error(err);
            inputs.splice(0, Infinity);
            break;
        }

        /**
         * Save information or error into one file.
         */
        const outputPath = `./outputs/${date}/${name}-${time}.json`;
        const {detail, images, parameters} = result;
        if(detail || !images || !images.length) {
            console.warn('no output');
            fs.writeFile(outputPath, JSON.stringify(result, null, '\t'));
            continue;
        }
        console.log(`Received ${images.length} image` + (images.length > 1 ? 's' : ''));

        const info = JSON.parse(result.info);
        delete info.all_prompts;
        delete info.all_negative_prompts;
        delete info.infotexts;
        info.elapsed_time = Date.now() - d.getTime();

        const specified_model = parameters.override_settings?.sd_model_checkpoint;
        const real_model = info.sd_model_name + ` [${info.sd_model_hash}]`;
        if (! specified_model) console.warn(`model not specified; used ${real_model}`);
        else if (! real_model.startsWith(specified_model))
            console.warn(`model ${specified_model} not found;\nused ${real_model}`);

        await fs.writeFile(outputPath, JSON.stringify(info, null, '\t'));

        // Save images.
        const message = [
            name,
            info.sd_model_name,
            info.prompt
        ].join('\n');
        for(let j = 0; j < images.length; j++) {
            const seed = info.all_seeds[j];
            const imagePath = `./outputs/${date}/${name}-${time}-${seed}.png`;
            await fs.writeFile(imagePath, images[j], {encoding: 'base64'});
        }

        // Send notification(s).
        if(!inputs[i]?.skip_notify) {
            const shortDate = date.slice(2, 4) + date.slice(5, 7) + date.slice(8);
            const files = info.all_seeds.map((seed, i) => ({
                type: 'image/png',
                filename: `${name}-${shortDate}-${time}-${seed}.png`,
                value: Buffer.from(images[i], 'base64')
            }));
            files.push({
                type: 'application/json',
                filename: `${name}-${shortDate}-${time}.json`,
                value: JSON.stringify(info, null, '\t')
            });
            notify(message, files);
        }

        console.log(''); // newline
    }

    // console.log('');
    if(!inputs.length) break;

    await new Promise(resolve => setTimeout(resolve, 1000));
}

function pad2(num) {
    return num.toString().padStart(2, '0');
}


/**
 * Send message(s) to Discord and / or LINE.
 * @param {String} text
 * @param {Array.<Object>} files
 * @param {String} files[].type - mime type
 * @param {String} files[].filename
 * @param {Buffer | String} files[].value
 *
 * One LINE notify API contains only at most one image,
 * so it shall be called several times if there are more than 1 image.
 *
 * If Discord message is enabled,
 * then the image URLs from the response are used
 * to save network traffic.
 */
async function notify(text, files) {
    const {lineToken, discordWebhook} = config;
    let imageUrls = null;

    if(discordWebhook) {
        try {
            const data = {content: text};
            if(files?.length) data.files = files;
            const res = await sendDiscordMessage(data, discordWebhook);
            if(res.status === 200) {
                const resBody = await res.json();
                imageUrls = resBody.attachments
                    ?.filter(a => a.content_type.startsWith('image/'))
                    ?.map(a => a.url)
                ;
            }
        }
        catch(err) {
            console.warn('Failed to send discord message', err);
        }
    }

    if(lineToken) {
        try {
            if(imageUrls?.length) {
                for(let i = 0; i < imageUrls.length; ++i) {
                    await lineNotify({
                        message: i ? text : ' ',
                        imageFullsize: imageUrls[i],
                        imageThumbnail: imageUrls[i]
                    }, lineToken);
                }
            }
            else if(files?.length) {
                for(let i = 0; i < files.length; ++i) {
                    if(!files[i].type.startsWith('image/')) continue;
                    await lineNotify({
                        message: i ? text : ' ',
                        imageFile: files[i].value
                    }, lineToken);
                }
            }
            else await lineNotify(text, lineToken);
        }
        catch(err) {
            console.warn('Failed to send line notify', err);
        }
    }
}
