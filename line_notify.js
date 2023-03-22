import { createBoundary, createFormData } from './form_data.js';

export default function lineNotify(params, token) {
    if(typeof params === 'string') params = {message: params};
    if(!params.message) params = {message: ' ', ...params}; // `message` must come before others.
    if(!token) {
        token = params.token;
        delete params.token;
    }
    if(!token) {
        console.warn('no LINE token');
        return Promise.reject('no LINE token');
    }

    const headers = {Authorization: 'Bearer ' + token};
    let body;

    if(params.imageFile) {
        const boundary = createBoundary();
        headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary;
        const parts = [];
        for(let param in params) {
            if(param === 'imageFile') parts.push({
                name: 'imageFile',
                filename: 'image',
                value: params.imageFile
            });
            else parts.push({name: param, value: params[param]});
        }
        body = createFormData(parts, boundary);
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
    );
}
