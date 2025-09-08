/**
 * Format number as currency (e.g., "1.000,00 €")
 */
export function formatCurrency(
  value: number | undefined | null,
  options?: Intl.NumberFormatOptions
): string {
  if (value === undefined || value === null) {
    return "-";
  }

  const formatter = new Intl.NumberFormat("de-DE", {
    style: "currency",
    currency: "EUR",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
    ...options,
  });

  return formatter.format(value);
}

/**
 * Format number as regular number (e.g., "1.000,00" without currency)
 */
export function formatNumber(
  value: number | undefined | null,
  options?: Intl.NumberFormatOptions
): string {
  if (value === undefined || value === null) {
    return "-";
  }

  const formatter = new Intl.NumberFormat("de-DE", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
    ...options,
  });

  return formatter.format(value);
}

export const parseGermanNumber = (input: string): number | null => {
  if (!input) return null;

  // Remove thousands separator "." (only if followed by 3 digits)
  const cleaned = input.replace(/\.(?=\d{3}(?:[,.]|$))/g, "");

  // Replace German decimal comma with dot
  const normalized = cleaned.replace(",", ".");

  const parsed = parseFloat(normalized);
  return isNaN(parsed) ? null : parsed;
};

export function formatNumberToGerman(value: number): string {
  return new Intl.NumberFormat("de-DE", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value);
}

/**
 * Format integer with thousand separators (e.g., 1000 -> "1.000")
 */
export function formatIntegerToGerman(value: number): string {
  return new Intl.NumberFormat("de-DE").format(value);
}
