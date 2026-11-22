// Browser shim for Node's 'url' module required by @mlc-ai/web-llm
export const fileURLToPath = (url) => (typeof url === 'string' ? url : url?.toString?.() ?? '');
export const pathToFileURL = (filepath) => new URL(`file://${filepath}`);

export default {
    fileURLToPath,
    pathToFileURL,
};
