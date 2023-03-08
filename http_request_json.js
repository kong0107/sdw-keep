import request from 'node:http';

export default function httpRequestJSON(url, options) {
    return new Promise((resolve, reject) => {
        const req = request(url, options, res => {
            let rawData = '';
            res.setEncoding('utf8');
            res.on('data', chunk => rawData += chunk);
            res.on('end', () => {
                try {
                    resolve(JSON.parse(rawData));
                } catch(err) {
                    reject(err);
                }
            });
            res.on('error', reject);
        });
        req.on('error', reject);
        if(options.body) req.write(options.body);
        req.end();
    });
}
