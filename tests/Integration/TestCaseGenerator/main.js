import avro from 'avsc';
import fs from 'fs/promises';
import fsLegacy from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { dirname } from 'path';
import readline from 'readline';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const testCasesDir = path.join(__dirname, '../TestCases');

const files = await fs.readdir(testCasesDir);
for (const file of files) {
    if (!file.endsWith('.jsonl')) {
        continue;
    }

    const output = [];
    let schema = null;
    const filePath = path.join(testCasesDir, file);
    let lineCount = 0;
    let binarySchema = false;

    for await (const line of readline.createInterface({ input: fsLegacy.createReadStream(filePath) })) {
        lineCount++;
        if (line.trim() === '') {
            output.push(line);
            continue;
        }

        const lineData = JSON.parse(line);
        if (typeof lineData.schema === 'undefined' && typeof lineData.data === 'undefined') {
            throw new Error(`Invalid line in ${file}: ${line}`);
        }

        if (lineData.schema) {
            output.push(line);
            schema = avro.Type.forSchema(lineData.schema, {wrap: 0});
            binarySchema = ['bytes', 'fixed'].includes(lineData.schema.type);
            continue;
        }

        if (!schema) {
            throw new Error(`Schema not defined for data in ${file} at line: ${lineCount}`);
        }

        const data = binarySchema ? Buffer.from(lineData.data) : lineData.data;
        const hex = schema.toBuffer(data).toString('hex');
        lineData.hex = hex;
        output.push(JSON.stringify(lineData));
    }

    await fs.writeFile(filePath, output.join('\n'));
}