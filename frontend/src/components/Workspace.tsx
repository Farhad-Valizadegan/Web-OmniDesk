import { type ReactNode, useCallback, useEffect, useRef, useState } from 'react'
import GmailConnectionPanel from './GmailConnectionPanel'
import {
  getConversationMessages,
  getConversations,
  getGmailStatus,
  syncGmailPage,
  type AuthUser,
  type Conversation,
  type Message,
} from '../lib/api'

type WorkspaceProps = {
  user: AuthUser
  isLoading: boolean
  onLogout: () => void
  onGmailFeedback?: (feedback: {
    type: 'success' | 'error'
    message: string
  }) => void
}

type ReadFilter = 'all' | 'read' | 'unread'
type ConversationPagination = {
  current_page: number
  last_page: number
  from: number | null
  to: number | null
  total: number
}
type Theme = 'light' | 'dark'

function formatDate(value: string | null): string {
  if (!value) {
    return 'No date'
  }

  return new Intl.DateTimeFormat('en', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

function initials(value: string | null): string {
  const words = (value ?? '').trim().split(/\s+/).filter(Boolean)
  const letters: string[] = words.length > 1
    ? words.slice(0, 2).map((word) => word[0] ?? '')
    : [(words[0] ?? '?').slice(0, 2)]

  return letters.join('').toUpperCase()
}

function formatAttachmentSize(size: number): string {
  if (size < 1024) return `${size} B`
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`
  return `${(size / (1024 * 1024)).toFixed(1)} MB`
}

function formatGmailLabel(label: string): string {
  const knownLabels: Record<string, string> = {
    CATEGORY_FORUMS: 'Forums',
    CATEGORY_PERSONAL: 'Personal',
    CATEGORY_PROMOTIONS: 'Promotions',
    CATEGORY_SOCIAL: 'Social',
    CATEGORY_UPDATES: 'Updates',
    IMPORTANT: 'Important',
    INBOX: 'Inbox',
    SPAM: 'Spam',
    STARRED: 'Starred',
    TRASH: 'Trash',
    UNREAD: 'Unread',
  }

  if (knownLabels[label]) {
    return knownLabels[label]
  }

  return label
    .replace(/^CATEGORY_/, '')
    .toLowerCase()
    .replace(/(^|_)\w/g, (character) => character.toUpperCase())
    .replace(/_/g, ' ')
}

function renderMessageBody(body: string): ReactNode {
  const normalizedBody = body.replace(/\r\n/g, '\n').trim()
  const parts = normalizedBody.split(/(https?:\/\/[^\s<]+)/g)

  return parts.map((part, index) => {
    if (!/^https?:\/\//i.test(part)) {
      return part
    }

    const displayUrl =
      part.length > 72 ? `${part.slice(0, 72)}…` : part

    return (
      <a
        href={part}
        key={`${part}-${index}`}
        target="_blank"
        rel="noreferrer"
        title={part}
      >
        {displayUrl}
      </a>
    )
  })
}
function sanitizeEmailHtml(html: string): string {
  const document = new DOMParser().parseFromString(html, 'text/html')

  document
    .querySelectorAll(
      'script, iframe, object, embed, form, input, button, textarea, select, video, audio, svg, math, link, base',
    )
    .forEach((element) => element.remove())

  document.querySelectorAll('style').forEach((style) => {
    style.textContent = (style.textContent ?? '')
      .replace(/@import[^;]+;?/gi, '')
      .replace(/url\s*\([^)]*\)/gi, '')
      .replace(/expression\s*\([^)]*\)/gi, '')
  })

  document.querySelectorAll('*').forEach((element) => {
    Array.from(element.attributes).forEach((attribute) => {
      const name = attribute.name.toLowerCase()
      const value = attribute.value.trim()

      if (
        name.startsWith('on') ||
        [
          'srcdoc',
          'action',
          'formaction',
          'xlink:href',
          'srcset',
        ].includes(name)
      ) {
        element.removeAttribute(attribute.name)
        return
      }

      if (name === 'href' || name === 'src') {
        if (!/^https?:\/\//i.test(value) && !/^data:image\//i.test(value)) {
          element.removeAttribute(attribute.name)
        }
        return
      }

      if (name === 'style') {
        element.setAttribute(
          attribute.name,
          value
            .replace(/url\s*\([^)]*\)/gi, '')
            .replace(/expression\s*\([^)]*\)/gi, '')
            .replace(/javascript\s*:/gi, ''),
        )
      }
    })

    if (element.tagName.toLowerCase() === 'a') {
      element.setAttribute('target', '_blank')
      element.setAttribute('rel', 'noopener noreferrer')
    }
  })

  const policy = document.createElement('meta')
  policy.httpEquiv = 'Content-Security-Policy'
  policy.content =
    "default-src 'none'; style-src 'unsafe-inline'; img-src https: data:; font-src 'none'; connect-src 'none'; frame-src 'none'; object-src 'none'; base-uri 'none'; form-action 'none'"

  document.head.prepend(policy)

  return document.documentElement.outerHTML
}
function Workspace({ user, isLoading, onLogout, onGmailFeedback }: WorkspaceProps) {
  const [theme, setTheme] = useState<Theme>(() =>
    localStorage.getItem('omni_desk_theme') === 'dark' ? 'dark' : 'light',
  )
  const [conversations, setConversations] = useState<Conversation[]>([])
  const [selectedConversation, setSelectedConversation] =
    useState<Conversation | null>(null)
  const [messages, setMessages] = useState<Message[]>([])
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [readFilter, setReadFilter] = useState<ReadFilter>('all')
  const [page, setPage] = useState(1)
  const [pagination, setPagination] = useState<ConversationPagination>({
    current_page: 1,
    last_page: 1,
    from: null,
    to: null,
    total: 0,
  })
  const [isInboxLoading, setIsInboxLoading] = useState(true)
  const [isMessagesLoading, setIsMessagesLoading] = useState(false)
  const [inboxError, setInboxError] = useState<string | null>(null)
  const [messagesError, setMessagesError] = useState<string | null>(null)
  const [isLazySyncLoading, setIsLazySyncLoading] = useState(false)
  const [gmailHasMore, setGmailHasMore] = useState(false)
  const hasInitializedLazySync = useRef(false)

  useEffect(() => {
    document.documentElement.dataset.theme = theme
    localStorage.setItem('omni_desk_theme', theme)
  }, [theme])

  const loadConversations = useCallback(async () => {
    try {
      setIsInboxLoading(true)
      setInboxError(null)

      if (!hasInitializedLazySync.current) {
        hasInitializedLazySync.current = true

        try {
          const gmailStatus = await getGmailStatus()

          if (gmailStatus.connected) {
            const syncResponse = await syncGmailPage()
            setGmailHasMore(syncResponse.sync.has_more)
          }
        } catch {
          // Existing local conversations remain available when lazy sync is unavailable.
        }
      }

      const response = await getConversations({
        search: search || undefined,
        is_read:
          readFilter === 'all' ? undefined : readFilter === 'read',
        per_page: 15,
        page,
      })

      setConversations(response.data)
      setPagination({
        current_page: response.current_page,
        last_page: response.last_page,
        from: response.from,
        to: response.to,
        total: response.total,
      })

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
  }, [page, readFilter, search])

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
    setPage(1)
    setSearch(searchInput.trim())
  }

  const handleConversationSelect = (conversation: Conversation) => {
    setSelectedConversation(conversation)
  }

  const handleNextPage = async () => {
    if (pagination.current_page >= pagination.last_page) {
      if (!gmailHasMore) {
        return
      }

      try {
        setIsLazySyncLoading(true)
        setInboxError(null)
        const syncResponse = await syncGmailPage()
        setGmailHasMore(syncResponse.sync.has_more)

        if (syncResponse.sync.threads_processed === 0) {
          return
        }
      } catch {
        setInboxError('The next Gmail page could not be loaded.')
        return
      } finally {
        setIsLazySyncLoading(false)
      }
    }

    setPage((current) => current + 1)
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
            className="theme-toggle"
            onClick={() => setTheme((current) => current === 'light' ? 'dark' : 'light')}
            aria-label={theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'}
          >
            {theme === 'light' ? 'Dark mode' : 'Light mode'}
          </button>

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

          <GmailConnectionPanel
            onSynchronized={loadConversations}
            onDisconnected={async () => {
              await loadConversations()
              onGmailFeedback?.({
                type: 'success',
                message: 'Gmail disconnected successfully.',
              })
            }}
          />

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
                onChange={(event) => {
                  setPage(1)
                  setReadFilter(event.target.value as ReadFilter)
                }}
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
                                {formatGmailLabel(label)}
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
                                <div className="message-sender">
                                  <span className="sender-avatar" aria-hidden="true">
                                    {initials(message.sender_name)}
                                  </span>
                                  <div>
                                    <strong>{message.sender_name}</strong>
                                    <p className="message-address">
                                      {message.sender_address ?? 'Address unavailable'}
                                    </p>
                                  </div>
                                </div>

                                <time dateTime={message.sent_at}>
                                  {formatDate(message.sent_at)}
                                </time>
                              </div>

                              {message.metadata?.html_body?.trim() ? (
                                <iframe
                                  className="message-html"
                                  title={`Email body from ${message.sender_name}`}
                                  srcDoc={sanitizeEmailHtml(message.metadata.html_body)}
                                  sandbox=""
                                  referrerPolicy="no-referrer"
                                />
                              ) : (
                                <p className="message-body">
                                  {renderMessageBody(message.body)}
                                </p>
                              )}

                              {(message.metadata?.attachments?.length ?? 0) > 0 && (
                                <div className="message-attachments" aria-label="Attachments">
                                  <p className="attachments-title">Attachments</p>
                                  <div className="attachment-list">
                                    {message.metadata?.attachments?.map((attachment) => (
                                      <div className="attachment-item" key={attachment.attachment_id ?? attachment.filename}>
                                        <span className="attachment-icon" aria-hidden="true">FILE</span>
                                        <span className="attachment-details">
                                          <strong>{attachment.filename}</strong>
                                          <span>{attachment.mime_type} · {formatAttachmentSize(attachment.size)}</span>
                                        </span>
                                      </div>
                                    ))}
                                  </div>
                                </div>
                              )}
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

            {pagination.last_page > 1 && (
              <nav className="conversation-pagination" aria-label="Conversation pages">
                <button
                  type="button"
                  className="secondary-button"
                  onClick={() => setPage((current) => Math.max(1, current - 1))}
                  disabled={isInboxLoading || pagination.current_page === 1}
                >
                  Previous
                </button>

                <span>
                  Page {pagination.current_page} of {pagination.last_page}
                  {' · '}
                  Showing {pagination.from ?? 0}-{pagination.to ?? 0} of {pagination.total}
                </span>

                <button
                  type="button"
                  className="secondary-button"
                  onClick={() => void handleNextPage()}
                  disabled={
                    isInboxLoading ||
                    isLazySyncLoading
                  }
                >
                  Next
                </button>
              </nav>
            )}
        </main>
      </div>
    </section>
  )
}

export default Workspace
