// Formats numbers as currency (e.g. 1234.56 → "$1,234.56")
function CurrencyFormatter ({
    amount,
    currency = "USD", // defaults to US Dollar
    locale            // browser locale if not provided
} : {
    amount: number;    // required
    currency?: string; // optional (? means optional in TypeScript)
    locale?: string;   // optional
})  {

  // Uses browser's built-in currency formatter
  // Handles currency symbols, decimals, and number formatting automatically
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency
  }).format(amount)
}

export default CurrencyFormatter