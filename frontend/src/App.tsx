import { useCallback, useEffect, useState, type FormEvent } from 'react'
import './App.css'
import GoogleCaptcha from './components/GoogleCaptcha'
import Workspace from './components/Workspace'
import {
  getCaptchaChallenge,
  getCurrentUser,
  getHealth,
  loginUser,
  logoutUser,
  registerUser,
  type AuthUser,
  type CaptchaChallenge,
  type HealthResponse,
} from './lib/api'

type AuthMode = 'login' | 'register'

function App() {
  const [theme, setTheme] = useState<'light' | 'dark'>(() =>
    localStorage.getItem('omni_desk_theme') === 'dark' ? 'dark' : 'light',
  )
  const [health, setHealth] = useState<HealthResponse | null>(null)
  const [isHealthLoading, setIsHealthLoading] = useState(true)
  const [healthError, setHealthError] = useState<string | null>(null)

  const [user, setUser] = useState<AuthUser | null>(null)
  const [authMode, setAuthMode] = useState<AuthMode>('login')
  const [authLoading, setAuthLoading] = useState(false)
  const [authError, setAuthError] = useState<string | null>(null)
  const [captchaChallenge, setCaptchaChallenge] =
    useState<CaptchaChallenge | null>(null)
  const [captchaAnswer, setCaptchaAnswer] = useState('')
  const [captchaResponse, setCaptchaResponse] = useState('')
  const [captchaLoading, setCaptchaLoading] = useState(true)
  const [captchaError, setCaptchaError] = useState<string | null>(null)
  const [captchaNonce, setCaptchaNonce] = useState(0)

  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')

  useEffect(() => {
    document.documentElement.dataset.theme = theme
    localStorage.setItem('omni_desk_theme', theme)
  }, [theme])

  useEffect(() => {
    if (user) {
      return
    }

    let isActive = true

    const loadCaptcha = async () => {
      try {
        setCaptchaLoading(true)
        setCaptchaError(null)
        const challenge = await getCaptchaChallenge()

        if (isActive) {
          setCaptchaChallenge(challenge)
          setCaptchaAnswer('')
        }
      } catch {
        if (isActive) {
          setCaptchaChallenge(null)
          setCaptchaError('Security check could not be loaded.')
        }
      } finally {
        if (isActive) {
          setCaptchaLoading(false)
        }
      }
    }

    void loadCaptcha()

    return () => {
      isActive = false
    }
  }, [captchaNonce, user])

  useEffect(() => {
    const loadApplicationState = async () => {
      try {
        setIsHealthLoading(true)
        setHealthError(null)

        const healthResponse = await getHealth()
        setHealth(healthResponse)

        if (sessionStorage.getItem('omni_desk_auth_token')) {
          try {
            const currentUserResponse = await getCurrentUser()
            setUser(currentUserResponse.user)
          } catch {
            sessionStorage.removeItem('omni_desk_auth_token')
            setUser(null)
          }
        }
      } catch {
        setHealth(null)
        setHealthError(
          'The backend is unavailable. Make sure Laravel is running.',
        )
      } finally {
        setIsHealthLoading(false)
      }
    }

    void loadApplicationState()
  }, [])

  const resetAuthForm = () => {
    setName('')
    setEmail('')
    setPassword('')
    setPasswordConfirmation('')
    setCaptchaAnswer('')
    setCaptchaResponse('')
    setAuthError(null)
  }

  const handleGoogleCaptchaToken = useCallback((token: string) => {
    setCaptchaResponse(token)
  }, [])

  const changeAuthMode = (mode: AuthMode) => {
    setAuthMode(mode)
    resetAuthForm()
  }

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setAuthLoading(true)
    setAuthError(null)

    if (!captchaChallenge) {
      setAuthLoading(false)
      setAuthError('Security check is not ready. Please try again.')
      return
    }

    const captchaFields =
      captchaChallenge.driver === 'internal'
        ? {
            captcha_token: captchaChallenge.token,
            captcha_answer: captchaAnswer,
          }
        : {
            captcha_response: captchaResponse,
          }

    if (
      captchaChallenge.driver === 'google' &&
      captchaResponse === ''
    ) {
      setAuthLoading(false)
      setAuthError('Complete the Google reCAPTCHA check.')
      return
    }

    try {
      const response =
        authMode === 'register'
          ? await registerUser({
              name,
              email,
              password,
              password_confirmation: passwordConfirmation,
              ...captchaFields,
            })
          : await loginUser({
              email,
              password,
              ...captchaFields,
            })

      setUser(response.user)
      resetAuthForm()
    } catch (error) {
      setCaptchaAnswer('')
      setCaptchaResponse('')
      setCaptchaNonce((current) => current + 1)
      setAuthError(
        error instanceof Error
          ? error.message
          : 'Authentication could not be completed.',
      )
    } finally {
      setAuthLoading(false)
    }
  }

  const handleLogout = async () => {
    setAuthLoading(true)
    setAuthError(null)

    try {
      await logoutUser()
      setUser(null)
    } catch (error) {
      setAuthError(
        error instanceof Error ? error.message : 'Logout could not be completed.',
      )
    } finally {
      setAuthLoading(false)
    }
  }

  return (
    <main className="app">
      {!user && (
        <button
          type="button"
          className="app-theme-toggle"
          onClick={() => setTheme((current) => current === 'light' ? 'dark' : 'light')}
          aria-label={theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'}
        >
          {theme === 'light' ? 'Dark mode' : 'Light mode'}
        </button>
      )}

      <section className="hero">
        <p className="eyebrow">OmniDesk AI</p>

        <h1>AI-Powered Unified Communication Workplace</h1>

        <p className="description">
          A secure workspace for unified communication and user-controlled AI
          assistance.
        </p>

        <div className="status-card" aria-live="polite">
          <h2>Backend Connectivity</h2>

          {isHealthLoading && (
            <p className="status loading">Checking backend connection...</p>
          )}

          {!isHealthLoading && health && (
            <p className="status success">
              Backend is connected. Status: {health.status}
            </p>
          )}

          {!isHealthLoading && healthError && (
            <p className="status failure">{healthError}</p>
          )}
        </div>

        <div className={user ? 'workspace-wrapper' : 'auth-card'}>
          {user ? (
            <Workspace
              user={user}
              isLoading={authLoading}
              onLogout={() => void handleLogout()}
            />
          ) : (
            <>
              <div className="auth-tabs" role="tablist">
                <button
                  type="button"
                  role="tab"
                  aria-selected={authMode === 'login'}
                  className={authMode === 'login' ? 'active' : ''}
                  onClick={() => changeAuthMode('login')}
                >
                  Login
                </button>

                <button
                  type="button"
                  role="tab"
                  aria-selected={authMode === 'register'}
                  className={authMode === 'register' ? 'active' : ''}
                  onClick={() => changeAuthMode('register')}
                >
                  Register
                </button>
              </div>

              <div className="auth-heading">
                <h2>
                  {authMode === 'login'
                    ? 'Sign in to your workspace'
                    : 'Create your workspace account'}
                </h2>

                <p>
                  {authMode === 'login'
                    ? 'Use your OmniDesk AI account to continue.'
                    : 'Create a secure account to get started.'}
                </p>
              </div>

              <form className="auth-form" onSubmit={handleSubmit}>
                {authMode === 'register' && (
                  <label>
                    Name
                    <input
                      type="text"
                      value={name}
                      onChange={(event) => setName(event.target.value)}
                      autoComplete="name"
                      required
                    />
                  </label>
                )}

                <label>
                  Email
                  <input
                    type="email"
                    value={email}
                    onChange={(event) => setEmail(event.target.value)}
                    autoComplete="email"
                    required
                  />
                </label>

                <label>
                  Password
                  <input
                    type="password"
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                    autoComplete={
                      authMode === 'login' ? 'current-password' : 'new-password'
                    }
                    minLength={8}
                    required
                  />
                </label>

                {authMode === 'register' && (
                  <label>
                    Confirm password
                    <input
                      type="password"
                      value={passwordConfirmation}
                      onChange={(event) =>
                        setPasswordConfirmation(event.target.value)
                      }
                      autoComplete="new-password"
                      minLength={8}
                      required
                    />
                  </label>
                )}

                {captchaChallenge?.driver === 'internal' && (
                  <div className="captcha-field">
                    <div className="captcha-heading">
                      <label htmlFor="captcha-answer">Security check</label>
                      <button
                        type="button"
                        className="captcha-refresh"
                        onClick={() => setCaptchaNonce((current) => current + 1)}
                        disabled={captchaLoading || authLoading}
                      >
                        New challenge
                      </button>
                    </div>
                    <p className="captcha-question">
                      {captchaChallenge.question}
                    </p>
                    <input
                      id="captcha-answer"
                      type="text"
                      inputMode="numeric"
                      pattern="[0-9]*"
                      value={captchaAnswer}
                      onChange={(event) => setCaptchaAnswer(event.target.value)}
                      autoComplete="off"
                      aria-label="CAPTCHA answer"
                      required
                    />
                  </div>
                )}

                {captchaChallenge?.driver === 'google' && (
                  <div className="captcha-field google-captcha-field">
                    <p className="captcha-label">Security check</p>
                    <GoogleCaptcha
                      siteKey={captchaChallenge.site_key}
                      onToken={handleGoogleCaptchaToken}
                    />
                  </div>
                )}

                {captchaLoading && (
                  <p className="captcha-status">Loading security check...</p>
                )}

                {captchaError && (
                  <p className="captcha-error" role="alert">
                    {captchaError}
                  </p>
                )}

                {authError && (
                  <p className="form-error" role="alert">
                    {authError}
                  </p>
                )}

                <button
                  type="submit"
                  className="primary-button"
                  disabled={authLoading || captchaLoading || !captchaChallenge}
                >
                  {authLoading
                    ? 'Please wait...'
                    : authMode === 'login'
                      ? 'Log in'
                      : 'Create account'}
                </button>
              </form>
            </>
          )}
        </div>
      </section>
    </main>
  )
}

export default App