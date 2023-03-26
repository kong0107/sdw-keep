// ref: https://discord.com/developers/docs/resources/webhook#execute-webhook

import { createBoundary, createFormData } from './form_data.js';

/**
 *
 * @param {Object | string} data
 * @param {String} [data.content]
 * @param {Array.<Object>} [data.files]
 * @param {Buffer} data.files[].data
 * @param {string} [data.files[].type] - mime type
 * @param {string} [data.files[].filename] - filename
 * @param {string} webhook - Discord type-1 webhook URL to execute
 * @returns {Promise.<Response>}
 */
export default function sendDiscordMessage(data, webhook) {
    if(typeof data === 'string') data = {content: data};
    if(!data.files || data.files.length === 0) return fetch(webhook, {
        method: 'POST',
        headers: {
            "Content-Type": 'application/json'
        },
        body: JSON.stringify(data)
    });

    const formDataParts = [];
    const payload = {};
    if(data.content.trim()) payload.content = data.content;
    payload.attachments = [];

    data.files.forEach((struct, index) => {
        const part = {
            name: `files[${index}]`,
            value: struct.value
        };

        let filename = struct.filename;
        if(struct.type) {
            part.type = struct.type;
            if(!filename) filename = index + '.' + struct.type.split('/')[1];
        }
        if(!filename) filename = index.toString();
        part.filename = filename;

        payload.attachments.push({
            id: index,
            filename
        });
        formDataParts.push(part);
    });
    formDataParts.unshift({
        name: 'payload_json',
        type: 'application/json',
        value: JSON.stringify(payload)
    });

    const boundary = createBoundary();
    return fetch(webhook, {
        method: 'POST',
        headers: {
            "Content-Type": 'multipart/form-data; boundary=' + boundary
        },
        body: createFormData(formDataParts, boundary)
    }).catch(console.error);
}
