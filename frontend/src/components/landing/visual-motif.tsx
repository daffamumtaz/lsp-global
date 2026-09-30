export function VisualMotif({ variant = "network", className = "" }: {
  variant?: "network" | "framework" | "global";
  className?: string;
}) {
  return <svg className={`visual-motif ${className}`} viewBox="0 0 320 240" fill="none" aria-hidden="true" focusable="false">
    <g stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
      {variant === "network" ? <>
        <path d="M24 170 88 128 132 184 208 140 284 180M88 128 116 58 208 82 208 140M208 82 282 40M132 184 116 58" />
        <circle cx="24" cy="170" r="4" /><circle cx="88" cy="128" r="7" /><circle cx="132" cy="184" r="4" /><circle cx="116" cy="58" r="5" /><circle cx="208" cy="82" r="7" /><circle cx="208" cy="140" r="4" /><circle cx="284" cy="180" r="5" /><circle cx="282" cy="40" r="4" />
      </> : variant === "framework" ? <>
        <rect x="54" y="42" width="210" height="156" rx="12" />
        <path d="M54 94h210M54 146h210M124 42v156M194 42v156M89 172l70-52 70-52" />
        <circle cx="89" cy="172" r="5" /><circle cx="159" cy="120" r="5" /><circle cx="229" cy="68" r="5" />
      </> : <>
        <ellipse cx="160" cy="120" rx="110" ry="82" /><ellipse cx="160" cy="120" rx="48" ry="82" />
        <path d="M50 120h220M70 72q90 48 180 0M70 168q90-48 180 0M35 176Q155 12 290 78" />
        <circle cx="70" cy="135" r="5" /><circle cx="160" cy="65" r="5" /><circle cx="246" cy="66" r="5" />
      </>}
    </g>
  </svg>;
}
