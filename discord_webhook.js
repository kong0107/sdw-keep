// ref: https://discord.com/developers/docs/resources/webhook#execute-webhook

import { createBoundary, createFormData } from './form_data.js';

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

    data.files.forEach((buffer, index) => {
        const filename = index + '.png';
        payload.attachments.push({
            id: index,
            filename
        });
        formDataParts.push({
            name: `files[${index}]`,
            filename,
            type: 'image/png',
            value: buffer
        });
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
