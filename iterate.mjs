/**
 * Iterate all models and all inputs
 * except the inputs starts with `_model`.
 */

import * as fs from 'node:fs/promises';
import * as kongUtilArray from '../kong-util/mod/array.mjs';
import showElapsedTime from './show_elapsed_time.js';

kongUtilArray.use();
const config = JSON.parse(await fs.readFile('./config.json'));
const models = await fetch(config.serverOrigin + '/sdapi/v1/sd-models').then(res => res.json());

const inputFileNames = (await fs.readdir('./inputs'))
    .filter(fn => fn.endsWith('.json') && !fn.startsWith('_model_'))
;
const inputs = await mapAsync(async (basename) => {
    const path = './inputs/' + basename;
    return Object.assign(
        JSON.parse(await fs.readFile(path)),
        {
            title: basename.slice(0, -5),
            steps: 32,
            batch_size: 2,
            n_iter: 2,
            override_settings: {},
            override_settings_restore_afterwards: true
        }
    );
}, inputFileNames);

await forEachAsync(async (model) => {
    await forEachAsync(async (input) => {
        input.override_settings.sd_model_checkpoint = model.title;
        console.log(`Using ${model.model_name} to draw ${input.title}`);

        const stopTimer = showElapsedTime();
        let result, elapsed_time;
        try {
            const res = await fetch(config.serverOrigin + '/sdapi/v1/txt2img', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(input)
            });
            elapsed_time = stopTimer();
            result = res.ok ? (await res.json()) : ({detail: [res]});
        }
        catch (err) {
            stopTimer();
            console.error(err);
            return;
        }

        const d = new Date();
        // const date = [d.getFullYear(), pad2(d.getMonth() + 1), pad2(d.getDate())].join('-'); // `YYYY-MM-DD` with hyphens
        const date = d.getFullYear() + '-'
            + (d.getMonth() + 1).toString().padStart(2, '0') + '-'
            + d.getDate().toString().padStart(2, '0');
        await fs.mkdir('./outputs/' + date, {recursive: true});

        // const time = pad2(d.getHours()) + pad2(d.getMinutes()) + pad2(d.getSeconds()); // `HHmmss` without colons
        const time = [d.getHours(), d.getMinutes(), d.getSeconds()]
            .map(n => n.toString().padStart(2, '0')).join('');
        const outputPath = `./outputs/${date}/${input.title}-${time}.json`;

        const {detail, images, parameters} = result;
        if(detail || !images || !images.length) {
            console.warn('no output');
            fs.writeFile(outputPath, JSON.stringify(result, null, '\t'));
            return;
        }
        console.log(`Received ${images.length} image` + (images.length > 1 ? 's' : ''));

        const info = JSON.parse(result.info);
        delete info.all_prompts;
        delete info.all_negative_prompts;
        delete info.infotexts;

        const output = {
            model: model.title,
            elapsed_time,
            parameters,
            info,
        };
        await fs.writeFile(outputPath, JSON.stringify(output, null, '\t'));

        await forEachAsync(async (image, i) => {
            const seed = info.all_seeds[i];
            const path = `./outputs/${date}/${input.title}-${time}-${seed}.png`;
            await fs.writeFile(path, image, {encoding: 'base64'});
        }, images);

        await new Promise(resolve => setTimeout(resolve, 100));
    }, inputs);
}, models);
