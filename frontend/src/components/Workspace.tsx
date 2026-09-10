import type { AuthUser } from '../lib/api'

type WorkspaceProps = {
  user: AuthUser
  isLoading: boolean
  onLogout: () => void
}

function Workspace({ user, isLoading, onLogout }: WorkspaceProps) {
  return (
    <section className="workspace" aria-label="Authenticated workspace">
      <header className="workspace-header">
        <div>
          <p className="workspace-brand">OmniDesk AI</p>
          <p className="workspace-subtitle">
            AI-Powered Unified Communication Workplace
          </p>
        </div>

        <div className="workspace-user">
          <span>{user.name}</span>

          <button
            type="button"
            className="secondary-button"
            onClick={onLogout}
            disabled={isLoading}
          >
            {isLoading ? 'Logging out...' : 'Log out'}
          </button>
        </div>
      </header>

      <div className="workspace-body">
        <aside className="workspace-sidebar" aria-label="Workspace navigation">
          <p className="sidebar-title">Workspace</p>

          <nav>
            <button type="button" className="navigation-item active">
              Inbox
            </button>

            <button type="button" className="navigation-item">
              Starred
            </button>

            <button type="button" className="navigation-item">
              Labels
            </button>

            <button type="button" className="navigation-item">
              Settings
            </button>
          </nav>

          <div className="sidebar-account">
            <p className="sidebar-title">Account</p>
            <p>{user.email}</p>
          </div>
        </aside>

        <main className="workspace-content">
          <div className="content-heading">
            <div>
              <p className="eyebrow">Unified Inbox</p>
              <h2>Welcome, {user.name}</h2>
            </div>

            <span className="workspace-status">Ready</span>
          </div>

          <section className="empty-state" aria-label="Empty inbox">
            <div className="empty-state-icon" aria-hidden="true">
              ✦
            </div>

            <h3>Your communication workspace is ready</h3>

            <p>
              Connect a communication provider to start organizing your
              conversations in one place.
            </p>

            <button type="button" className="primary-button" disabled>
              Connect a provider
            </button>

            <small>Provider connections will be implemented in Sprint 04.</small>
          </section>
        </main>
      </div>
    </section>
  )
}

export default Workspace