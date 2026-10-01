import { useEffect, useState, type FormEvent } from 'react'
import { resendLoginCode, verifyLoginCode, type AuthUser, type LoginChallenge } from '../lib/api'

export default function LoginVerification({ challenge, onSuccess, onBack }: {
  challenge: LoginChallenge
  onSuccess: (user: AuthUser) => void
  onBack: () => void
}) {
  const [code, setCode] = useState('')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('Check your account email for a six-digit code.')
  const [resendAt, setResendAt] = useState(() => Date.now() + challenge.resend_after * 1000)
  const [expiresAt] = useState(() => Date.now() + challenge.expires_in * 1000)
  const [now, setNow] = useState(Date.now)
  useEffect(() => {
    const timer = window.setInterval(() => setNow(Date.now()), 1000)
    return () => window.clearInterval(timer)
  }, [])
  const expired = now >= expiresAt
  const remaining = Math.max(0, Math.ceil((resendAt - now) / 1000))

  async function verify(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError('')
    try {
      const result = await verifyLoginCode(challenge.challenge, code)
      onSuccess(result.user)
    } catch (failure) {
      setError(failure instanceof Error ? failure.message : 'Verification failed.')
    } finally { setBusy(false) }
  }

  async function resend() {
    setBusy(true)
    setError('')
    try {
      const result = await resendLoginCode(challenge.challenge)
      setResendAt(Date.now() + result.resend_after * 1000)
      setCode('')
      setNotice('A new code was sent. Use the most recent email.')
    } catch (failure) {
      setError(failure instanceof Error ? failure.message : 'The code could not be sent.')
    } finally { setBusy(false) }
  }

  return <form className="auth-form" onSubmit={verify}>
    <h2>Verify your sign-in</h2>
    <p role="status">{expired ? 'Your code has expired. Please sign in again.' : notice}</p>
    <p>Codes expire after 10 minutes and work only once.</p>
    <label>Email verification code
      <input value={code} onChange={event => setCode(event.target.value)}
        inputMode="numeric" autoComplete="one-time-code" pattern="[0-9]{6}"
        maxLength={6} required autoFocus disabled={busy || expired} />
    </label>
    {error && <p className="form-error" role="alert">{error}</p>}
    <button className="primary-button" disabled={busy || expired} type="submit">
      {busy ? 'Please wait...' : 'Verify and sign in'}
    </button>
    <button className="secondary-button" type="button" disabled={busy || expired || remaining > 0} onClick={() => void resend()}>
      {remaining > 0 ? `Resend code in ${remaining}s` : 'Resend code'}
    </button>
    <button className="secondary-button" type="button" disabled={busy} onClick={onBack}>Back to sign in</button>
  </form>
}
