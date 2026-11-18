export function shouldReduceMotion(): boolean {
  return document.documentElement.dataset.motion !== "full"
    && matchMedia("(prefers-reduced-motion: reduce)").matches;
}

export function shouldPauseCarousel(root: HTMLElement | null): boolean {
  return shouldReduceMotion()
    || Boolean(root?.matches(":focus-within"))
    || (matchMedia("(hover: hover) and (pointer: fine)").matches && Boolean(root?.matches(":hover")));
}
