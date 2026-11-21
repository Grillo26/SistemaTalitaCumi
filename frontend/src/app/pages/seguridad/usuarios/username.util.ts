/**
 * Genera un nombre de usuario estable a partir del nombre completo.
 *
 * - Quita tildes y diéresis.
 * - Pasa a minúsculas.
 * - Reemplaza espacios y signos por puntos.
 * - Colapsa puntos repetidos y recorta extremos.
 */
export function usernameFromName(name: string): string {
  const base = name
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '.')
    .replace(/^\.+|\.+$/g, '')
    .replace(/\.{2,}/g, '.')
    .slice(0, 100);

  if (base === '') {
    return '';
  }

  if (base.length < 3) {
    return `usuario.${base}`.slice(0, 100).replace(/^\.+|\.+$/g, '');
  }

  return base;
}
