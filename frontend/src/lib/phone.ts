export function formatPhone(value: string | null | undefined): string {
  const original = (value ?? '').trim()
  if (/[^\d\s+()\-.]/u.test(original)) return original
  let digits = original.replace(/\D/g, '')
  if (digits.startsWith('0060')) digits = digits.slice(2)
  if (digits.startsWith('0') && digits.length >= 9 && digits.length <= 11) digits = '60' + digits.slice(1)
  return digits.startsWith('60') && digits.length >= 10 && digits.length <= 12 ? '+(60) - ' + digits.slice(2) : original
}
