import { useEffect, useState } from 'react'
import './App.css'

type HealthResponse = {
  status: string
  service: string
}

function App() {
  const [health, setHealth] = useState<HealthResponse | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const checkBackendHealth = async () => {
      try {
        setIsLoading(true)
        setError(null)

        const response = await fetch('/api/health')

        if (!response.ok) {
          throw new Error('The backend returned an unsuccessful response.')
        }

        const data = (await response.json()) as HealthResponse
        setHealth(data)
      } catch {
        setHealth(null)
        setError('The backend is unavailable. Make sure Laravel is running.')
      } finally {
        setIsLoading(false)
      }
    }

    void checkBackendHealth()
  }, [])

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

          {isLoading && (
            <p className="status loading">Checking backend connection...</p>
          )}

          {!isLoading && health && (
            <p className="status success">
              Backend is connected. Status: {health.status}
            </p>
          )}

          {!isLoading && error && (
            <p className="status failure">{error}</p>
          )}
        </div>
      </section>
    </main>
  )
}

export default App