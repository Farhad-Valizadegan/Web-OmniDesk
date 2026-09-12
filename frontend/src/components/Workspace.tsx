import { useCallback, useEffect, useState } from 'react'
import GmailConnectionPanel from './GmailConnectionPanel'
import {
  getConversationMessages,
  getConversations,
  type AuthUser,
  type Conversation,
  type Message,
} from '../lib/api'

type WorkspaceProps = {
  user: AuthUser
  isLoading: boolean
  onLogout: () => void
}

type ReadFilter = 'all' | 'read' | 'unread'

function formatDate(value: string | null): string {
  if (!value) {
    return 'No date'
  }

  return new Intl.DateTimeFormat('en', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

function Workspace({ user, isLoading, onLogout }: WorkspaceProps) {
  const [conversations, setConversations] = useState<Conversation[]>([])
  const [selectedConversation, setSelectedConversation] =
    useState<Conversation | null>(null)
  const [messages, setMessages] = useState<Message[]>([])
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [readFilter, setReadFilter] = useState<ReadFilter>('all')
  const [isInboxLoading, setIsInboxLoading] = useState(true)
  const [isMessagesLoading, setIsMessagesLoading] = useState(false)
  const [inboxError, setInboxError] = useState<string | null>(null)
  const [messagesError, setMessagesError] = useState<string | null>(null)

  const loadConversations = useCallback(async () => {
    try {
      setIsInboxLoading(true)
      setInboxError(null)

      const response = await getConversations({
        search: search || undefined,
        is_read:
          readFilter === 'all' ? undefined : readFilter === 'read',
        per_page: 50,
      })

      setConversations(response.data)

      setSelectedConversation((current) => {
        if (!current) {
          return response.data[0] ?? null
        }

        return (
          response.data.find(
            (conversation) => conversation.id === current.id,
          ) ?? response.data[0] ?? null
        )
      })
    } catch {
      setConversations([])
      setSelectedConversation(null)
      setInboxError('Conversations could not be loaded.')
    } finally {
      setIsInboxLoading(false)
    }
  }, [readFilter, search])

  const loadMessages = useCallback(async (conversationId: number) => {
    try {
      setIsMessagesLoading(true)
      setMessagesError(null)

      const response = await getConversationMessages(conversationId)
      setMessages(response.data)
    } catch {
      setMessages([])
      setMessagesError('Messages could not be loaded.')
    } finally {
      setIsMessagesLoading(false)
    }
  }, [])

  useEffect(() => {
    // Synchronize component state with the external conversations API.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    void loadConversations()
  }, [loadConversations])

  useEffect(() => {
    if (selectedConversation) {
      // Synchronize component state with the external messages API.
      // eslint-disable-next-line react-hooks/set-state-in-effect
      void loadMessages(selectedConversation.id)
    } else {
      setMessages([])
    }
  }, [loadMessages, selectedConversation])

  const handleSearchSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setSearch(searchInput.trim())
  }

  const handleConversationSelect = (conversation: Conversation) => {
    setSelectedConversation(conversation)
  }

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

          <GmailConnectionPanel />

          <div className="inbox-toolbar">
            <form className="inbox-search" onSubmit={handleSearchSubmit}>
              <label htmlFor="conversation-search">Search conversations</label>

              <div className="search-row">
                <input
                  id="conversation-search"
                  type="search"
                  value={searchInput}
                  onChange={(event) => setSearchInput(event.target.value)}
                  placeholder="Search by subject or provider"
                />

                <button type="submit" className="primary-button">
                  Search
                </button>
              </div>
            </form>

            <label className="read-filter" htmlFor="read-filter">
              Read state
              <select
                id="read-filter"
                value={readFilter}
                onChange={(event) =>
                  setReadFilter(event.target.value as ReadFilter)
                }
              >
                <option value="all">All messages</option>
                <option value="read">Read only</option>
                <option value="unread">Unread only</option>
              </select>
            </label>
          </div>

          {inboxError && (
            <p className="inbox-error" role="alert">
              {inboxError}
            </p>
          )}

          {isInboxLoading && (
            <p className="inbox-status" role="status">
              Loading conversations...
            </p>
          )}

          {!isInboxLoading && !inboxError && conversations.length === 0 && (
            <section className="empty-state" aria-label="Empty inbox">
              <div className="empty-state-icon" aria-hidden="true">
                +
              </div>

              <h3>No conversations found</h3>

              <p>
                Try changing the search or read-state filter. Provider
                connections will be implemented in Sprint 04.
              </p>
            </section>
          )}

          {!isInboxLoading && conversations.length > 0 && (
            <div className="inbox-layout">
              <section
                className="conversation-list"
                aria-label="Conversation list"
              >
                <div className="section-heading">
                  <h3>Conversations</h3>
                  <span>{conversations.length}</span>
                </div>

                <div className="conversation-items">
                  {conversations.map((conversation) => (
                    <button
                      type="button"
                      key={conversation.id}
                      className={`conversation-item ${
                        selectedConversation?.id === conversation.id
                          ? 'selected'
                          : ''
                      }`}
                      onClick={() => handleConversationSelect(conversation)}
                    >
                      <span className="conversation-item-heading">
                        <strong>
                          {conversation.subject ?? 'Untitled conversation'}
                        </strong>

                        {!conversation.is_read && (
                          <span className="unread-indicator">Unread</span>
                        )}
                      </span>

                      <span className="conversation-preview">
                        {conversation.latest_message?.body ??
                          'No message preview available.'}
                      </span>

                      <span className="conversation-meta">
                        <span>{conversation.provider}</span>
                        <span>
                          {formatDate(conversation.last_message_at)}
                        </span>
                      </span>

                      {conversation.labels &&
                        conversation.labels.length > 0 && (
                          <span className="conversation-labels">
                            {conversation.labels.map((label) => (
                              <span className="label" key={label}>
                                {label}
                              </span>
                            ))}
                          </span>
                        )}
                    </button>
                  ))}
                </div>
              </section>

              <section
                className="conversation-detail"
                aria-label="Conversation detail"
              >
                {selectedConversation ? (
                  <>
                    <div className="detail-heading">
                      <div>
                        <p className="eyebrow">
                          {selectedConversation.provider}
                        </p>
                        <h3>
                          {selectedConversation.subject ??
                            'Untitled conversation'}
                        </h3>
                      </div>

                      <span
                        className={
                          selectedConversation.is_read
                            ? 'read-state'
                            : 'read-state unread'
                        }
                      >
                        {selectedConversation.is_read ? 'Read' : 'Unread'}
                      </span>
                    </div>

                    {messagesError && (
                      <p className="inbox-error" role="alert">
                        {messagesError}
                      </p>
                    )}

                    {isMessagesLoading && (
                      <p className="inbox-status" role="status">
                        Loading messages...
                      </p>
                    )}

                    {!isMessagesLoading && !messagesError && (
                      <div className="message-list">
                        {messages.length === 0 ? (
                          <p className="inbox-status">
                            This conversation has no messages.
                          </p>
                        ) : (
                          messages.map((message) => (
                            <article className="message-card" key={message.id}>
                              <div className="message-heading">
                                <strong>{message.sender_name}</strong>
                                <time dateTime={message.sent_at}>
                                  {formatDate(message.sent_at)}
                                </time>
                              </div>

                              <p className="message-address">
                                {message.sender_address ?? 'Address unavailable'}
                              </p>

                              <p className="message-body">{message.body}</p>
                            </article>
                          ))
                        )}
                      </div>
                    )}
                  </>
                ) : (
                  <p className="inbox-status">
                    Select a conversation to view its messages.
                  </p>
                )}
              </section>
            </div>
          )}
        </main>
      </div>
    </section>
  )
}

export default Workspace
