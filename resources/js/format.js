export const rm = (value) =>
    'RM' + Number(value).toLocaleString('ms-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export const compact = (value) =>
    value >= 1000 ? `${(value / 1000).toFixed(value >= 10000 ? 1 : 1).replace(/\.0$/, '')}K` : String(value);

export const variantLabel = (item) => [item.size, item.color].filter(Boolean).join(' / ');

export const itemSummary = (items) =>
    items.map((i) => `${i.quantity}× ${i.product_name}${variantLabel(i) ? ` (${variantLabel(i)})` : ''}`).join(', ');
