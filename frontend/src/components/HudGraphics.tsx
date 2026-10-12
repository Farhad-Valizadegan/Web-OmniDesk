import { useEffect, useState } from 'react'

export function HudIcon({ kind }: { kind: 'mail' | 'telegram' | 'sun' | 'moon' | 'motion' | 'pause' | 'arrow' }) {
  const paths = {
    mail: <><rect x="3" y="5" width="18" height="14" rx="1" /><path d="m3 6 9 7 9-7" /></>,
    telegram: <><path d="m3 11 18-8-5 18-5-7-8-3Z" /><path d="m11 14 6-7" /></>,
    sun: <><circle cx="12" cy="12" r="4" /><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l2 2m10 10 2 2M5 19l2-2M17 7l2-2" /></>,
    moon: <path d="M20 15a8 8 0 0 1-11-11 8.5 8.5 0 1 0 11 11Z" />,
    motion: <><path d="m5 4 15 8-15 8V4Z" /><path d="M2 8v8" /></>,
    pause: <><path d="M8 5v14M16 5v14" /></>,
    arrow: <><path d="M4 12h16m-6-6 6 6-6 6" /></>,
  }
  return <svg className="hud-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="square" strokeLinejoin="miter" aria-hidden="true">{paths[kind]}</svg>
}

export function BrandMark() {
  return <svg className="brand-mark" viewBox="0 0 48 48" aria-hidden="true">
    <path className="brand-frame" d="M2 2h33l11 11v33H13L2 35Z" />
    <path className="brand-glyph" d="M13 13h22v22H13Z M19 19h10v10H19Z" fillRule="evenodd" />
    <path className="brand-track" d="M2 24h8m28 0h8M24 2v8m0 28v8" />
  </svg>
}

export function MotionToggle() {
  const [reduced, setReduced] = useState(() => {
    const saved = localStorage.getItem('omni_desk_motion')
    return saved ? saved === 'reduced' : window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false
  })
  const [systemReduced, setSystemReduced] = useState(() => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false)
  const effectiveReduced = reduced || systemReduced
  useEffect(() => {
    const media = window.matchMedia?.('(prefers-reduced-motion: reduce)')
    if (!media) return
    const update = (event: MediaQueryListEvent) => setSystemReduced(event.matches)
    media.addEventListener?.('change', update)
    return () => media.removeEventListener?.('change', update)
  }, [])
  useEffect(() => {
    document.documentElement.dataset.motion = effectiveReduced ? 'reduced' : 'full'
  }, [effectiveReduced])
  function toggle() {
    const next = !reduced
    localStorage.setItem('omni_desk_motion', next ? 'reduced' : 'full')
    setReduced(next)
  }
  return <button type="button" className="motion-toggle" aria-label={systemReduced ? 'Interface motion reduced by system preference' : reduced ? 'Enable interface motion' : 'Reduce interface motion'} aria-pressed={!effectiveReduced} disabled={systemReduced} title={systemReduced ? 'Your system reduced-motion preference is enabled.' : undefined} onClick={toggle}>
    <HudIcon kind={effectiveReduced ? 'pause' : 'motion'} /><span>{effectiveReduced ? 'Motion off' : 'Motion on'}</span>
  </button>
}

export function ConnectionDiagram() {
  return <figure className="connection-diagram">
    <svg viewBox="0 0 460 158" role="img" aria-label="Gmail and Telegram feed into one OmniDesk workspace. Diagram illustrates the supported providers, not their connection status.">
      <path className="diagram-route" d="M122 36h72l42 43h64M122 122h72l42-43" />
      <path className="diagram-traveler" d="M122 36h72l42 43h64M122 122h72l42-43" />
      <rect className="diagram-node" x="2" y="12" width="120" height="48" />
      <rect className="diagram-node" x="2" y="98" width="120" height="48" />
      <path className="diagram-hub" d="M300 48h142l16 16v45H316l-16-16Z" />
      <circle className="diagram-junction" cx="236" cy="79" r="6" />
      <text x="62" y="42" textAnchor="middle">GMAIL</text>
      <text x="62" y="128" textAnchor="middle">TELEGRAM</text>
      <text x="379" y="73" textAnchor="middle">OMNIDESK</text>
      <text className="diagram-small" x="379" y="94" textAnchor="middle">ONE WORKSPACE</text>
    </svg>
    <figcaption>Two providers. One place to focus.</figcaption>
  </figure>
}

type InboxTelemetryProps = { provider: 'gmail' | 'telegram'; total: number; unread: number; loading: boolean; failed: boolean }

export function InboxTelemetry({ provider, total, unread, loading, failed }: InboxTelemetryProps) {
  const [expanded, setExpanded] = useState(false)
  const read = Math.max(0, total - unread)
  const unreadWidth = total > 0 ? unread / total * 240 : 0
  const unavailable = loading || failed
  return <section className={`inbox-telemetry${expanded ? ' expanded' : ''}`} aria-label="Loaded inbox overview" aria-busy={loading}>
    <div className="telemetry-heading"><span className={`signal-dot${unavailable ? ' signal-waiting' : ''}`} aria-hidden="true" /><strong>{provider === 'gmail' ? 'Gmail' : 'Telegram'} overview</strong><span>{loading ? 'Loading view' : failed ? 'Data unavailable' : 'Loaded view'}</span></div>
    <button type="button" className="telemetry-toggle" aria-expanded={expanded} onClick={() => setExpanded(current => !current)}>{expanded ? 'Hide chart' : 'Show chart'}</button>
    <div className="telemetry-chart">
      <svg viewBox="0 0 260 34" role="img" aria-label={unavailable ? 'Overview unavailable until data loads successfully' : `${unread} unread and ${read} read ${provider === 'gmail' ? 'conversations' : 'chats'} in the loaded view`}>
        <path className="chart-ticks" d="M10 1v4m60-4v4m60-4v4m60-4v4m60-4v4M10 30v3m60-3v3m60-3v3m60-3v3m60-3v3" />
        <rect className="chart-track" x="10" y="9" width="240" height="14" />
        {!unavailable && total > 0 && <><rect className="chart-read" x="10" y="9" width="240" height="14" /><rect className="chart-unread" x="10" y="9" width="240" height="14" style={{ transform: `scaleX(${unreadWidth / 240})` }} /></>}
      </svg>
      <span className="telemetry-legend"><span><i />Unread <b>{unavailable ? '—' : unread}</b></span><span><i />Read <b>{unavailable ? '—' : read}</b></span></span>
    </div>
    <p className="telemetry-total"><strong>{unavailable ? '—' : total}</strong><span>{provider === 'gmail' ? 'conversations on this page' : 'chats currently loaded'}</span></p>
  </section>
}
