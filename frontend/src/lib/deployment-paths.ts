export function frontendPath(path: string): string {
  const base = (process.env.NEXT_PUBLIC_BASE_PATH ?? '').replace(/\/$/, '');
  if (!base || !path.startsWith('/') || path.startsWith('//') || path === base || path.startsWith(`${base}/`)) return path;
  return `${base}${path}`;
}

export function publicAsset(path: string): string {
  return /^\/(figma\/|images\/|profile-video-poster\.jpg(?:$|[?#])|favicon\.ico(?:$|[?#]))/.test(path) ? frontendPath(path) : path;
}
