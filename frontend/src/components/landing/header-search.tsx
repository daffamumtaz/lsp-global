"use client";

import { useEffect, useRef, useState } from "react";
import { MagnifyingGlassIcon } from "@heroicons/react/24/outline";
import { SearchField } from "./ui";

export function HeaderSearch() {
  const [open, setOpen] = useState(false);
  const dialog = useRef<HTMLDialogElement>(null);
  useEffect(() => {
    if (!open) return;
    const modal = dialog.current;
    const overflow = document.body.style.overflow;
    modal?.showModal();
    modal?.querySelector<HTMLInputElement>('input[type="search"]')?.focus();
    document.body.style.overflow = "hidden";
    return () => { modal?.close(); document.body.style.overflow = overflow; };
  }, [open]);

  return <>
    <button className="header-search-trigger" type="button" aria-label="Buka pencarian skema" aria-haspopup="dialog" aria-controls="header-search-dialog" onClick={() => setOpen(true)}><MagnifyingGlassIcon aria-hidden="true" /></button>
    <dialog ref={dialog} id="header-search-dialog" className="header-search-dialog" onClose={() => setOpen(false)} onClick={event => {
      if (event.target !== event.currentTarget) return;
      const bounds = event.currentTarget.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) setOpen(false);
    }}>
      <SearchField />
    </dialog>
  </>;
}
