/**
 * Convert base64 to Blob object
 * @param {string} base64 - Base64 encoded string
 * @param {string} type - MIME type of the file
 * @returns {Blob} - Blob object
 */
function base64ToBlob(base64, type) {
    const binaryString = window.atob(base64);
    const len = binaryString.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return new Blob([bytes], { type: type });
}