"use client";

import { useEffect, useSyncExternalStore } from "react";

const storageKey = "lsp-motion";
let enabled = false;

function snapshot() {
  try { return localStorage.getItem(storageKey) === "full"; } catch { return enabled; }
}

function subscribe(listener: () => void) {
  window.addEventListener("storage", listener);
  window.addEventListener("lsp-motion-change", listener);
  return () => {
    window.removeEventListener("storage", listener);
    window.removeEventListener("lsp-motion-change", listener);
  };
}

export function MotionControl() {
  const fullMotion = useSyncExternalStore(subscribe, snapshot, () => false);
  useEffect(() => {
    if (fullMotion) document.documentElement.dataset.motion = "full";
    else delete document.documentElement.dataset.motion;
  }, [fullMotion]);

  function toggle() {
    enabled = !fullMotion;
    try {
      if (enabled) localStorage.setItem(storageKey, "full");
      else localStorage.removeItem(storageKey);
    } catch { /* Keep the choice for this tab when storage is unavailable. */ }
    window.dispatchEvent(new Event("lsp-motion-change"));
  }

  return <div className="motion-control">
    <span>{fullMotion ? "Animasi situs aktif" : "Animasi mengikuti pengaturan perangkat"}</span>
    <button type="button" aria-pressed={fullMotion} onClick={toggle}>{fullMotion ? "Ikuti pengaturan perangkat" : "Aktifkan animasi"}</button>
  </div>;
}
