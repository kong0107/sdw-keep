import {readFile} from 'node:fs/promises';
import sendDiscordMessage from './discord_webhook.js';
import lineNotify from './line_notify.js';

const config = JSON.parse(await readFile('./config.json'));
const image = await readFile('./outputs/2023-03-19/bear-071158-4191516414.png');
let res;

res = await sendDiscordMessage({
    content: (new Date()).toLocaleTimeString(),
    files: [{
        filename: 'qq.png',
        type: 'image/png',
        value: image
    }]
}).catch(console.error);

console.log(res.status);

// const imageUrls = res.attachments.map(att => att.url);

// res = await lineNotify({
//     message: (new Date()).toLocaleTimeString(),
//     imageThumbnail: imageUrls[0],
//     imageFullsize: imageUrls[0]
// }, config.lineToken).then(res => res.json());
// console.log(res);
