/**
 * Shared formatting utilities used across multiple views/components.
 */

/**
 * Format a date string (MySQL datetime) into a human-readable date + time.
 * Uses regex replace so the first space (MySQL separator) is handled safely.
 *
 * @param {string|null} dateString
 * @param {boolean} includeTime
 * @returns {string}
 */
export function formatDate(dateString, includeTime = true) {
  if (!dateString) return 'N/A';
  try {
    // MySQL datetime "2024-01-15 14:30:00" → ISO "2024-01-15T14:30:00Z"
    const iso = dateString.toString().replace(/\s/, 'T') + 'Z';
    const date = new Date(iso);
    if (isNaN(date.getTime())) return dateString;
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    if (includeTime) {
      options.hour = '2-digit';
      options.minute = '2-digit';
    }
    return date.toLocaleDateString('en-GB', options);
  } catch {
    return dateString;
  }
}

/**
 * Format a number or string as a Euro price.
 *
 * @param {number|string} price
 * @returns {string}
 */
export function formatPrice(price) {
  return `€${parseFloat(price || 0).toFixed(2)}`;
}
