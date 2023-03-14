import * as fs from 'node:fs';

const boundary = 'whateverYouWant';

export default function lineNotify(params, token) {
    if(typeof params === 'string') params = {message: params};
    if(!params.message) params = {message: ' ', ...params}; // `message` must come before others.
    if(!token) {
        token = params.token;
        delete params.token;
    }
    if(!token) return Promise.resolve('no token');

    const headers = {Authorization: 'Bearer ' + token};
    let body;

    if(params.imageFile) {
        // ref: https://blog.kalan.dev/2021-03-13-html-form-data
        headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary;
        const buffers = [];
        for(let param in params) {
            let partHead = `--${boundary}\r\nContent-Disposition: form-data; name="${param}"`;
            if(param === 'imageFile') {
                buffers.push(partHead + '; filename="image"\r\n\r\n');
                buffers.push(fs.readFileSync(params.imageFile));
            }
            else buffers.push(partHead + '\r\n\r\n' + params[param] + '\r\n');
        }
        buffers.push(`\r\n--${boundary}--\r\n`);
        body = buffers.map(b => (typeof b === 'string') ? Buffer.from(b) : b);
        body = Buffer.concat(body);
    }
    else {
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
        const usp = new URLSearchParams();
        for(let param in params) usp.append(param, params[param]);
        body = usp.toString();
    }

    return fetch(
        'https://notify-api.line.me/api/notify',
        {method: 'POST', headers, body}
    ).then(res => res.json());
}
