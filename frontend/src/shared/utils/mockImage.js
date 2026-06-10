export function createTextPlaceholderDataUrl(label, options = {}) {
  const text = String(label || 'Preview').trim() || 'Preview';
  const width = Number(options.width) || 160;
  const height = Number(options.height) || 80;
  const backgroundStart = options.backgroundStart || '#eff6f0';
  const backgroundEnd = options.backgroundEnd || '#dcefe1';
  const border = options.border || '#b7d4c0';
  const textColor = options.textColor || '#386641';
  const fontSize = options.fontSize || 18;

  return `data:image/svg+xml;utf8,${encodeURIComponent(`
    <svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
      <defs>
        <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="${backgroundStart}"/>
          <stop offset="100%" stop-color="${backgroundEnd}"/>
        </linearGradient>
      </defs>
      <rect width="${width}" height="${height}" rx="14" fill="url(#g)"/>
      <rect x="3" y="3" width="${width - 6}" height="${height - 6}" rx="11" fill="none" stroke="${border}" stroke-width="2"/>
      <text
        x="50%"
        y="50%"
        dominant-baseline="middle"
        text-anchor="middle"
        font-family="Arial, sans-serif"
        font-size="${fontSize}"
        font-weight="700"
        fill="${textColor}"
      >${escapeXml(text)}</text>
    </svg>
  `)}`;
}

function escapeXml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}
