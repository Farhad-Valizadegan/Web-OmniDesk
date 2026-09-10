import { useEffect, useState, type FormEvent } from 'react'
import './App.css'
import Workspace from './components/Workspace'
import {
  getCurrentUser,
  getHealth,
  loginUser,
  logoutUser,
  registerUser,
  type AuthUser,
  type HealthResponse,
} from './lib/api'

type AuthMode = 'login' | 'register'

function App() {
  const [health, setHealth] = useState<HealthResponse | null>(null)
  const [isHealthLoading, setIsHealthLoading] = useState(true)
  const [healthError, setHealthError] = useState<string | null>(null)

  const [user, setUser] = useState<AuthUser | null>(null)
  const [authMode, setAuthMode] = useState<AuthMode>('login')
  const [authLoading, setAuthLoading] = useState(false)
  const [authError, setAuthError] = useState<string | null>(null)

  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')

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
    setAuthError(null)
  }

  const changeAuthMode = (mode: AuthMode) => {
    setAuthMode(mode)
    resetAuthForm()
  }

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setAuthLoading(true)
    setAuthError(null)

    try {
      const response =
        authMode === 'register'
          ? await registerUser({
              name,
              email,
              password,
              password_confirmation: passwordConfirmation,
            })
          : await loginUser({
              email,
              password,
            })

      setUser(response.user)
      resetAuthForm()
    } catch (error) {
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

                {authError && (
                  <p className="form-error" role="alert">
                    {authError}
                  </p>
                )}

                <button
                  type="submit"
                  className="primary-button"
                  disabled={authLoading}
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