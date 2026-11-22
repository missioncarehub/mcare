// Tiptap 2 is required by RK Editor 4. Apply the documented GHSA-cp6q-959q-f8rh
// defense at build time, including internal helper calls, without modifying
// installed packages. Fail the build if the upstream helper shape changes.
export function tiptapAttributeGuard() {
    return {
        name: 'mcare-tiptap-attribute-guard', enforce: 'pre',
        transform(code, id) {
            if (!/\/node_modules\/@tiptap\/core\/dist\/index\.(js|mjs)(?:\?|$)/.test(id.replaceAll('\\', '/'))) return null;
            const needle = 'Object.entries(item).forEach(([key, value]) => {';
            if (code.split(needle).length !== 2) throw new Error('Review the Tiptap attribute guard for this dependency version.');
            return { code: code.replace(needle, needle+"\n            if (['__proto__', 'prototype', 'constructor'].includes(key)) return;"), map: null };
        },
    };
}
