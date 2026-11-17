/**
 * Polyfill kecil untuk iPhone/iPad lama (iOS 15.0–16.3). Mesti diimport paling awal.
 */
if (!Array.prototype.at) {
    Object.defineProperty(Array.prototype, 'at', {
        value(index) {
            const i = Math.trunc(index) || 0;
            return this[i < 0 ? this.length + i : i];
        },
        configurable: true,
        writable: true,
    });
}

if (!Array.prototype.toSorted) {
    Object.defineProperty(Array.prototype, 'toSorted', {
        value(compare) {
            return [...this].sort(compare);
        },
        configurable: true,
        writable: true,
    });
}

if (!Object.hasOwn) {
    Object.hasOwn = (object, key) => Object.prototype.hasOwnProperty.call(object, key);
}

if (typeof globalThis.structuredClone !== 'function') {
    // Cukup untuk data biasa (props, borang); tidak menyalin File/Date sebagai objek asal.
    globalThis.structuredClone = (value) => (value === undefined ? undefined : JSON.parse(JSON.stringify(value)));
}
