import * as fs from 'node:fs/promises';
import * as kongUtilArray from '../kong-util/mod/array.mjs';
import showElapsedTime from './show_elapsed_time.js';

const config = JSON.parse(await fs.readFile('./config.json'));
const emojiList = JSON.parse(await fs.readFile('./emoji.json'));

emojiList.sort(() => Math.random() - 0.5); // https://shubo.io/javascript-random-shuffle/

await kongUtilArray.forEachAsync(async (emoji) => {
    const {code, cldr} = emoji;
    console.log(code, cldr);

    const stopTimer = showElapsedTime();
    const response = await fetch(config.serverOrigin + '/sdapi/v1/txt2img', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            prompt: `anthro male bear, solo, (${cldr}: 1.5)`,
            negative_prompt: 'EasyNegative, bad-artist, boring_e621',
            steps: 24,
            batch_size: 2,
            sampler_name: 'Euler a'
        })
    });
    stopTimer();

    if (!response.ok) {
        const message = await response.text();
        throw new Error(message);
    }
    const result = await response.json();

    const d = new Date();
    const date = d.getFullYear() + '-'
        + (d.getMonth() + 1).toString().padStart(2, '0') + '-'
        + d.getDate().toString().padStart(2, '0'); // `YYYY-MM-DD` with hyphens
    const time = [d.getHours(), d.getMinutes(), d.getSeconds()]
        .map(n => n.toString().padStart(2, '0')).join(''); // `HHmmss` without colons
    await fs.mkdir('./outputs/' + date, {recursive: true});

    const {detail, images} = result;
    if (detail || !images || !images.length)
        return console.warn('no output');

    const info = JSON.parse(result.info);
    await kongUtilArray.forEachAsync(async (image, i) => {
        const seed = info.all_seeds[i];
        const imagePath = `./outputs/${date}/emoji_${cldr.replaceAll(' ', '_')}-${time}-${seed}.png`;
        await fs.writeFile(imagePath, image, {encoding: 'base64'});
    }, images);
}, emojiList);
