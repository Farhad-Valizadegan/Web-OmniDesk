import { useEffect, useRef, useState } from 'react'
import { getTelegramMedia, type TelegramMessage } from '../lib/api'

export default function TelegramMedia({ message }: { message: TelegramMessage }) {
  const container = useRef<HTMLDivElement>(null)
  const [requested, setRequested] = useState(false)
  const [attempt, setAttempt] = useState(0)
  const [url, setUrl] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const kind = message.media?.kind

  useEffect(() => {
    if (kind !== 'photo' || message.media?.spoiler || !container.current) return
    if (!('IntersectionObserver' in window)) return
    const observer = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) { setRequested(true); observer.disconnect() }
    })
    observer.observe(container.current)
    return () => observer.disconnect()
  }, [kind, message.media?.spoiler])

  useEffect(() => {
    if (!requested) return
    const controller = new AbortController()
    let objectUrl: string | undefined
    getTelegramMedia(message.chat_id, message.id, controller.signal)
      .then((blob) => {
        if (controller.signal.aborted) return
        objectUrl = URL.createObjectURL(blob)
        setUrl(objectUrl)
      })
      .catch((reason: unknown) => {
        if (!controller.signal.aborted) setError(reason instanceof Error ? reason.message : 'Media could not be loaded.')
      })
    return () => { controller.abort(); if (objectUrl) URL.revokeObjectURL(objectUrl) }
  }, [requested, attempt, message.chat_id, message.id])

  return <div ref={container} className="telegram-media">
    {url ? kind === 'photo'
      ? <img src={url} alt="Telegram photo" onError={() => { setUrl(null); setError('Photo could not be displayed.') }} />
      : kind === 'voice'
      ? <audio src={url} controls preload="metadata" aria-label="Telegram voice message" onError={() => setError('This voice recording cannot be played by your browser.')} />
      : <video src={url} controls playsInline preload="metadata" onError={() => setError('This video format cannot be played by your browser.')} />
      : requested && !error ? <p role="status">Loading {kind}…</p> : null}
    {error && <p role="alert">{error}</p>}
    {(!requested || error) && <button type="button" className="secondary-button" onClick={() => {
      setError(null); setRequested(true); setAttempt((current) => current + 1)
    }}>{error ? 'Retry' : kind === 'voice' ? 'Load voice message' : kind === 'video' ? 'Load video' : 'Show photo'}</button>}
  </div>
}
