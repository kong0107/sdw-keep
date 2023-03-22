/**
 * @desc create a body for `fetch()` to send
 * @see {@link https://blog.kalan.dev/2021-03-13-html-form-data }
 * @param {Array} parts
 * @param {string} parts[].name
 * @param {string} [parts[].type] - MIME
 * @param {string} [parts[].filename] - required for files
 * @param {*} parts[].value
 * @param {string} boundary
 * @returns {Buffer}
 *
 * Notes:
 * - Web API has native `FormData` interface.
 *   This function is used in Node.js if one do not want install other packages.
 * - `boundary` is also used in the header of HTTP request.
 */
export function createFormData(parts, boundary) {
    const buffers = [];
    for(let part of parts) {
        buffers.push(`--${boundary}\r\nContent-Disposition: form-data; name="${part.name}"`);
        if(part.filename) buffers.push(`; filename="${part.filename}"`);
        if(part.type) buffers.push(`\r\nContent-Type: ${part.type}`);
        buffers.push('\r\n\r\n', part.value, '\r\n'); // note that `part.value` may not be string; therefore template literals are not used.
    }
    buffers.push(`--${boundary}--\r\n`);
    buffers.forEach((b, i) => {
        if(typeof b === 'string') buffers[i] = Buffer.from(b);
    });
    return Buffer.concat(buffers);
}


/**
 * @desc a simple way to create a boundary string for `multipart/form-data`
 * @returns {string} with length between 64 and 70
 * @see {@link https://stackoverflow.com/questions/1349404/#answer-8084248 }
 * @see {@link https://www.rfc-editor.org/rfc/rfc2046 }
 */
export function createBoundary() {
    let boundary = '';
    while(boundary.length < 64) boundary += Math.random().toString(36).slice(2);
    return boundary.slice(0, 70);
}

export default {createBoundary, createFormData};