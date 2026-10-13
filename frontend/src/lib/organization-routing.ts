export type Point = { x: number; y: number };
export type Rectangle = { x: number; y: number; width: number; height: number };

export function crossesRectangle(a: Point, b: Point, box: Rectangle): boolean {
  return a.x === b.x
    ? a.x > box.x && a.x < box.x + box.width && Math.max(a.y, b.y) > box.y && Math.min(a.y, b.y) < box.y + box.height
    : a.y > box.y && a.y < box.y + box.height && Math.max(a.x, b.x) > box.x && Math.min(a.x, b.x) < box.x + box.width;
}

export function simplifyPath(points: Point[]): Point[] {
  const result: Point[] = [];
  for (const point of points) {
    const last = result.at(-1);
    if (last && last.x === point.x && last.y === point.y) continue;
    const previous = result.at(-2);
    if (previous && last && ((previous.x === last.x && last.x === point.x) || (previous.y === last.y && last.y === point.y))) result.pop();
    result.push(point);
  }
  return result;
}

// Search the free corridors around cards; the turn cost keeps connectors readable.
export function routeOrthogonal(start: Point, end: Point, rectangles: Rectangle[]): Point[] {
  const boxes = rectangles.map(box => ({ x: box.x - 12, y: box.y - 12, width: box.width + 24, height: box.height + 24 }));
  const clear = (a: Point, b: Point) => !boxes.some(box => crossesRectangle(a, b, box));
  const candidates = [[start, { x: start.x, y: end.y }, end], [start, { x: end.x, y: start.y }, end]];
  for (const candidate of candidates) if (candidate.slice(1).every((point, index) => clear(candidate[index], point))) return simplifyPath(candidate);
  const xs = [...new Set([start.x, end.x, ...boxes.flatMap(box => [box.x, box.x + box.width])])].sort((a, b) => a - b);
  const ys = [...new Set([start.y, end.y, ...boxes.flatMap(box => [box.y, box.y + box.height])])].sort((a, b) => a - b);
  const index = (x: number, y: number) => y * xs.length + x;
  const point = (id: number) => ({ x: xs[id % xs.length], y: ys[Math.floor(id / xs.length)] });
  const first = index(xs.indexOf(start.x), ys.indexOf(start.y));
  const last = index(xs.indexOf(end.x), ys.indexOf(end.y));
  const distances = new Map<number, number>([[first * 3, 0]]);
  const previous = new Map<number, number>();
  const pending = new Set([first * 3]);
  let final: number | undefined;
  while (pending.size) {
    let current = -1;
    let score = Infinity;
    for (const state of pending) {
      const p = point(Math.floor(state / 3));
      const estimate = distances.get(state)! + Math.abs(p.x - end.x) + Math.abs(p.y - end.y);
      if (estimate < score) { current = state; score = estimate; }
    }
    pending.delete(current);
    const id = Math.floor(current / 3);
    if (id === last) { final = current; break; }
    const x = id % xs.length, y = Math.floor(id / xs.length), a = point(id);
    for (const [nx, ny, direction] of [[x - 1, y, 1], [x + 1, y, 1], [x, y - 1, 2], [x, y + 1, 2]]) {
      if (nx < 0 || ny < 0 || nx >= xs.length || ny >= ys.length) continue;
      const nextId = index(nx, ny), b = point(nextId);
      if (!clear(a, b)) continue;
      const state = nextId * 3 + direction;
      const distance = distances.get(current)! + Math.abs(a.x - b.x) + Math.abs(a.y - b.y) + (current % 3 && current % 3 !== direction ? 28 : 0);
      if (distance < (distances.get(state) ?? Infinity)) {
        distances.set(state, distance);
        previous.set(state, current);
        pending.add(state);
      }
    }
  }
  if (final === undefined) throw new Error('Unable to route organization connector');
  const path: Point[] = [];
  for (let state: number | undefined = final; state !== undefined; state = previous.get(state)) path.push(point(Math.floor(state / 3)));
  return simplifyPath(path.reverse());
}
