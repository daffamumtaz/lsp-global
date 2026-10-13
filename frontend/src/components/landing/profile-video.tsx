"use client";

import { useCallback, useRef, useState } from "react";

export function ProfileVideo({ src, poster }: { src: string; poster: string }) {
  const [failed, setFailed] = useState(false);
  const [started, setStarted] = useState(false);
  const video = useRef<HTMLVideoElement | null>(null);
  const checkInitialError = useCallback((element: HTMLVideoElement | null) => {
    video.current = element;
    if (element?.error) setFailed(true);
  }, []);

  return (
    <div className="profile-video relative w-full overflow-hidden rounded-xl">
      <video
        controls
        playsInline
        preload="metadata"
        poster={poster}
        src={src}
        aria-label="Video profil LSP TIK Global"
        ref={checkInitialError}
        onPlay={() => setStarted(true)}
        onEnded={() => setStarted(false)}
        onError={() => setFailed(true)}
        className="aspect-video w-full bg-black"
      >
        Browser Anda tidak mendukung pemutaran video. <a href={src}>Buka video profil</a>.
      </video>
      {!started && !failed && <button
        type="button"
        aria-label="Putar video profil"
        onClick={() => { void video.current?.play().catch(() => setFailed(true)); }}
        className="absolute left-1/2 top-1/2 flex size-16 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-black/75 text-white shadow-lg hover:bg-black/90 focus-visible:outline-4 focus-visible:outline-offset-4 focus-visible:outline-white"
      ><svg aria-hidden="true" viewBox="0 0 24 24" className="size-8"><path fill="currentColor" d="M8 5v14l11-7z" /></svg></button>}
      {failed && <p role="alert" className="bg-brand-dark p-4 text-sm text-white">Video tidak dapat diputar. <a href={src} className="underline underline-offset-4">Buka video profil</a>.</p>}
    </div>
  );
}
