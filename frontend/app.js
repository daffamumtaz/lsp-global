process.env.NODE_ENV ||= 'production';

async function start() {
  const { createServer } = await import('node:http');
  const { default: next } = await import('next');
  const app = next({ dev: false, dir: __dirname });
  const handle = app.getRequestHandler();
  await app.prepare();
  const server = createServer((request, response) => handle(request, response));
  server.listen(Number(process.env.PORT || 3000), process.env.HOST || '0.0.0.0');
}

start().catch((error) => {
  console.error('Failed to start Next.js:', error);
  process.exit(1);
});
