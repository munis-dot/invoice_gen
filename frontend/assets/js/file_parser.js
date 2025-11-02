/**
 * Parse CSV file content
 * @param {File} file - The file object
 * @returns {Promise<Array>} - Array of objects representing rows
 */
async function parseCSV(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function(event) {
            try {
                const text = event.target.result;
                const lines = text.split('\n');
                const headers = lines[0].split(',').map(header => header.trim());
                const items = [];

                for(let i = 1; i < lines.length; i++) {
                    if(!lines[i].trim()) continue;
                    const values = lines[i].split(',');
                    const item = {};
                    headers.forEach((header, index) => {
                        item[header] = values[index] ? values[index].trim() : '';
                    });
                    items.push(item);
                }
                resolve(items);
            } catch(error) {
                reject(error);
            }
        };
        reader.onerror = error => reject(error);
        reader.readAsText(file);
    });
}

/**
 * Parse Excel file content
 * @param {File} file - The file object
 * @returns {Promise<Array>} - Array of objects representing rows
 */
async function parseExcel(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = async function(event) {
            try {
                const data = event.target.result;
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const items = XLSX.utils.sheet_to_json(firstSheet);
                resolve(items);
            } catch(error) {
                reject(error);
            }
        };
        reader.onerror = error => reject(error);
        reader.readAsArrayBuffer(file);
    });
}

// Export functions for use
window.fileParser = {
    parseCSV,
    parseExcel
};