import { useCallback, useEffect, useState } from 'react'
import {
  disconnectGmail,
  getGmailStatus,
  startGmailAuthorization,
  syncGmail,
  type GmailStatusResponse,
} from '../lib/api'

type GmailConnectionPanelProps = {
  onSynchronized?: () => void | Promise<void>
  onDisconnected?: () => void | Promise<void>
}

function GmailConnectionPanel({
  onSynchronized,
  onDisconnected,
}: GmailConnectionPanelProps) {
  const [status, setStatus] = useState<GmailStatusResponse | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [isActionLoading, setIsActionLoading] = useState(false)
  const [isSyncLoading, setIsSyncLoading] = useState(false)
  const [syncMessage, setSyncMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  const loadStatus = useCallback(async () => {
    try {
      setIsLoading(true)
      setError(null)

      const response = await getGmailStatus()
      setStatus(response)
    } catch {
      setError('Gmail connection status could not be loaded.')
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => {
    // Synchronize component state with the Gmail integration API.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    void loadStatus()
  }, [loadStatus])

  const handleConnect = async () => {
    try {
      setIsActionLoading(true)
      setError(null)

      const response = await startGmailAuthorization()
      window.location.assign(response.authorization_url)
    } catch {
      setError('Gmail authorization could not be started.')
      setIsActionLoading(false)
    }
  }

  const handleSync = async () => {
    try {
      setIsSyncLoading(true)
      setSyncMessage(null)
      setError(null)

      const response = await syncGmail()
      setSyncMessage(
        `${response.sync.messages_processed} messages synchronized.`,
      )

      await onSynchronized?.()
    } catch {
      setError('Gmail messages could not be synchronized.')
    } finally {
      setIsSyncLoading(false)
    }
  }

  const handleDisconnect = async () => {
    try {
      setIsActionLoading(true)
      setError(null)

      await disconnectGmail()

      setStatus({
        connected: false,
        connection: null,
      })

      await onDisconnected?.()
    } catch {
      setError('Gmail could not be disconnected.')
    } finally {
      setIsActionLoading(false)
    }
  }

  return (
    <section className="gmail-panel" aria-label="Gmail connection">
      <div className="gmail-panel-heading">
        <div>
          <p className="eyebrow">Provider connection</p>
          <h3>Gmail</h3>
        </div>

        {status?.connected && (
          <span className="connection-badge">Connected</span>
        )}
      </div>

      {isLoading && (
        <p className="gmail-status" role="status">
          Checking Gmail connection...
        </p>
      )}

      {!isLoading && error && (
        <p className="gmail-error" role="alert">
          {error}
        </p>
      )}

      {!isLoading && !error && !status?.connected && (
        <div className="gmail-disconnected">
          <p>Gmail is not connected.</p>

          <button
            type="button"
            className="primary-button"
            onClick={() => void handleConnect()}
            disabled={isActionLoading}
          >
            {isActionLoading ? 'Connecting...' : 'Connect Gmail'}
          </button>
        </div>
      )}

      {!isLoading && !error && status?.connected && (
        <div className="gmail-connected">
          <div className="gmail-account">
            {status.connection?.account_avatar_url ? (
              <img
                className="gmail-account-avatar"
                src={status.connection.account_avatar_url}
                alt=""
              />
            ) : (
              <span className="gmail-account-avatar gmail-account-avatar-fallback" aria-hidden="true">
                {(status.connection?.account_email ?? 'G').slice(0, 1).toUpperCase()}
              </span>
            )}
            <p>
              Connected account:{' '}
              <strong>
                {status.connection?.account_email ?? 'Account identity unavailable'}
              </strong>
            </p>
          </div>

          <div className="gmail-actions">
            <button
              type="button"
              className="primary-button"
              onClick={() => void handleSync()}
              disabled={isActionLoading || isSyncLoading}
            >
              {isSyncLoading ? 'Synchronizing...' : 'Synchronize Gmail'}
            </button>

            <button
              type="button"
              className="secondary-button"
              onClick={() => void handleDisconnect()}
              disabled={isActionLoading || isSyncLoading}
            >
              {isActionLoading ? 'Disconnecting...' : 'Disconnect Gmail'}
            </button>
          </div>

          {syncMessage && (
            <p className="gmail-status" role="status">
              {syncMessage}
            </p>
          )}
        </div>
      )}

      {!isLoading && error && (
        <button
          type="button"
          className="secondary-button"
          onClick={() => void loadStatus()}
          disabled={isActionLoading}
        >
          Retry
        </button>
      )}
    </section>
  )
}

export default GmailConnectionPanel
