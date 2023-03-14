import { request as nativeHttpRequest } from 'node:http';
import { request as nativeHttpsRequest } from 'node:https';

export default function httpRequest(url, options) {
    const request = ((new URL(url)).protocol === 'http:')
        ? nativeHttpRequest : nativeHttpsRequest;
    return new Promise((resolve, reject) => {
        const req = request(url, options, res => {
            let rawData = '';
            res.setEncoding('utf8');
            res.on('data', chunk => rawData += chunk);
            res.on('end', () => {
                // const headers = {};
                // for(let i = 0; i < res.rawHeaders.length; i += 2)
                //     headers[res.rawHeaders[i]] = res.rawHeaders[i + 1];
                resolve({
                    // headers,
                    statusCode: res.statusCode,
                    statusMessage: res.statusMessage,
                    body: rawData
                });
            });
            res.on('error', reject);
        });
        req.on('error', reject);
        if(options.body) req.write(options.body);
        req.end();
    });
}
