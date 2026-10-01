import {
  act,
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import App from './App'
import {
  getConversationMessages,
  getConversations,
  getGmailStatus,
  getCaptchaChallenge,
  getCurrentUser,
  getHealth,
  getTelegramSample,
  loginUser,
  logoutUser,
  registerUser,
  syncGmailPage,
  verifyLoginCode,
  type Conversation,
  type Message,
  type PaginatedResponse,
  type TelegramChat,
  type TelegramSampleResponse,
} from './lib/api'

vi.mock('./lib/api', () => ({
  getCaptchaChallenge: vi.fn(),
  getConversationMessages: vi.fn(),
  getConversations: vi.fn(),
  getGmailStatus: vi.fn(),
  getCurrentUser: vi.fn(),
  getHealth: vi.fn(),
  getTelegramSample: vi.fn(),
  loginUser: vi.fn(),
  logoutUser: vi.fn(),
  registerUser: vi.fn(),
  resendLoginCode: vi.fn(),
  syncGmailPage: vi.fn(),
  verifyLoginCode: vi.fn(),
}))

const getCaptchaChallengeMock = vi.mocked(getCaptchaChallenge)
const getConversationMessagesMock = vi.mocked(getConversationMessages)
const getConversationsMock = vi.mocked(getConversations)
const getGmailStatusMock = vi.mocked(getGmailStatus)
const getHealthMock = vi.mocked(getHealth)
const getTelegramSampleMock = vi.mocked(getTelegramSample)
const getCurrentUserMock = vi.mocked(getCurrentUser)
const loginUserMock = vi.mocked(loginUser)
const logoutUserMock = vi.mocked(logoutUser)
const registerUserMock = vi.mocked(registerUser)
const verifyLoginCodeMock = vi.mocked(verifyLoginCode)
const syncGmailPageMock = vi.mocked(syncGmailPage)

function emptyPagination<T>(): PaginatedResponse<T> {
  return {
    current_page: 1,
    data: [],
    first_page_url: '/api/test?page=1',
    from: null,
    last_page: 1,
    last_page_url: '/api/test?page=1',
    links: [],
    next_page_url: null,
    path: '/api/test',
    per_page: 50,
    prev_page_url: null,
    to: null,
    total: 0,
  }
}

afterEach(() => {
  cleanup()
  vi.clearAllMocks()
  vi.useRealTimers()
  vi.unstubAllEnvs()
  window.history.replaceState({}, '', '/')
  sessionStorage.clear()
})

describe('App', () => {
  beforeEach(() => {
    vi.stubEnv('VITE_CAPTCHA_ENABLED', 'true')
    getCaptchaChallengeMock.mockResolvedValue({
      driver: 'internal',
      token: 'captcha-token',
      question: '2 + 8 = ?',
      expires_in: 300,
    })

    getHealthMock.mockResolvedValue({
      status: 'ok',
      service: 'backend',
    })

    getCurrentUserMock.mockRejectedValue(new Error('Unauthenticated'))

    getConversationsMock.mockResolvedValue(emptyPagination<Conversation>())

    getGmailStatusMock.mockResolvedValue({
      connected: false,
      connection: null,
    })

    getConversationMessagesMock.mockResolvedValue(
      emptyPagination<Message>(),
    )

    syncGmailPageMock.mockResolvedValue({
      message: 'Gmail page synchronized.',
      sync: {
        threads_processed: 0,
        messages_processed: 0,
        has_more: false,
      },
    })
  })

  test('displays a successful backend connection', async () => {
    render(<App />)

    expect(
      await screen.findByText('Backend is connected. Status: ok'),
    ).toBeInTheDocument()
  })

  test('allows local development login with CAPTCHA disabled', async () => {
    vi.stubEnv('VITE_CAPTCHA_ENABLED', 'false')
    loginUserMock.mockResolvedValue({ message: 'Check email', challenge: 'challenge', expires_in: 600, resend_after: 60 })
    verifyLoginCodeMock.mockResolvedValue({ message: 'OK', token: 'token', user: { id: 1, name: 'Freddie', email: 'freddie@example.com' } })
    render(<App />)
    fireEvent.change(screen.getByLabelText('Email'), { target: { value: 'freddie@example.com' } })
    fireEvent.change(screen.getByLabelText('Password'), { target: { value: 'password123' } })
    fireEvent.click(screen.getByRole('button', { name: 'Log in' }))
    fireEvent.change(await screen.findByLabelText('Email verification code'), { target: { value: '123456' } })
    fireEvent.click(screen.getByRole('button', { name: 'Verify and sign in' }))
    expect(await screen.findByText('Welcome, Freddie')).toBeInTheDocument()
    expect(getCaptchaChallengeMock).not.toHaveBeenCalled()
    expect(loginUserMock).toHaveBeenCalledWith({ email: 'freddie@example.com', password: 'password123' })
  })

  test('keeps CAPTCHA required outside development even when the flag is false', async () => {
    vi.stubEnv('DEV', false)
    vi.stubEnv('VITE_CAPTCHA_ENABLED', 'false')
    render(<App />)
    expect(await screen.findByLabelText('CAPTCHA answer')).toBeInTheDocument()
  })

  test('explains that another Gmail account requires disconnecting first', async () => {
    window.history.replaceState({}, '', '/?gmail=gmail_account_mismatch')
    render(<App />)
    expect(await screen.findByText('Disconnect your current Gmail account before connecting a different account.')).toBeInTheDocument()
  })

  test('clears the visible workspace when logout fails', async () => {
    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')
    getCurrentUserMock.mockResolvedValue({ user: { id: 1, name: 'Freddie', email: 'freddie@example.com' } })
    logoutUserMock.mockRejectedValueOnce(new Error('Server unavailable'))
    render(<App />)
    await screen.findByText('Welcome, Freddie')
    fireEvent.click(screen.getByRole('button', { name: 'Log out' }))
    expect(await screen.findByRole('button', { name: 'Log in' })).toBeInTheDocument()
    expect(screen.queryByText('Welcome, Freddie')).not.toBeInTheDocument()
    expect(screen.getByText('Server unavailable')).toBeInTheDocument()
  })

  test('recreates Google CAPTCHA after failed authentication and switching modes', async () => {
    getCaptchaChallengeMock.mockResolvedValue({ driver: 'google', site_key: 'site-key' })
    const renderCaptcha = vi.fn((_container: HTMLElement, options: { callback: (token: string) => void }) => {
      options.callback('solved-token')
      return 1
    })
    window.grecaptcha = { ready: (callback) => callback(), render: renderCaptcha }
    try {
      loginUserMock.mockRejectedValueOnce(new Error('Wrong password'))
      render(<App />)
      await waitFor(() => expect(renderCaptcha).toHaveBeenCalledTimes(1))
      fireEvent.change(screen.getByLabelText('Email'), { target: { value: 'freddie@example.com' } })
      fireEvent.change(screen.getByLabelText('Password'), { target: { value: 'password123' } })
      fireEvent.click(screen.getByRole('button', { name: 'Log in' }))
      await screen.findByText('Wrong password')
      await waitFor(() => expect(renderCaptcha).toHaveBeenCalledTimes(2))
      fireEvent.click(screen.getByRole('tab', { name: 'Register' }))
      await waitFor(() => expect(renderCaptcha).toHaveBeenCalledTimes(3))
    } finally {
      delete window.grecaptcha
    }
  })

  test('displays the backend connection loading state', () => {
    render(<App />)

    expect(
      screen.getByText('Checking backend connection...'),
    ).toBeInTheDocument()
  })

  test('displays the backend connection failure state', async () => {
    getHealthMock.mockRejectedValueOnce(
      new Error('Backend unavailable'),
    )

    render(<App />)

    expect(
      await screen.findByText(
        'The backend is unavailable. Make sure Laravel is running.',
      ),
    ).toBeInTheDocument()
  })
  test('allows a user to register', async () => {
    registerUserMock.mockResolvedValue({
      message: 'Registration successful.',
      token: 'registration-token',
      user: {
        id: 1,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

    render(<App />)

    await screen.findByText('Backend is connected. Status: ok')

    fireEvent.click(screen.getByRole('tab', { name: 'Register' }))

    fireEvent.change(screen.getByLabelText('Name'), {
      target: { value: 'Freddie' },
    })

    fireEvent.change(screen.getByLabelText('Email'), {
      target: { value: 'freddie@example.com' },
    })

    fireEvent.change(screen.getByLabelText('Password'), {
      target: { value: 'password123' },
    })

    fireEvent.change(screen.getByLabelText('Confirm password'), {
      target: { value: 'password123' },
    })

    fireEvent.change(screen.getByLabelText('CAPTCHA answer'), {
      target: { value: '10' },
    })

    fireEvent.click(screen.getByRole('button', { name: 'Create account' }))

    await waitFor(() => {
      expect(registerUserMock).toHaveBeenCalledWith({
        name: 'Freddie',
        email: 'freddie@example.com',
        password: 'password123',
        password_confirmation: 'password123',
        captcha_token: 'captcha-token',
        captcha_answer: '10',
      })
    })

    expect(await screen.findByText('Welcome, Freddie')).toBeInTheDocument()
  })

  test('allows a user to log in', async () => {
    loginUserMock.mockResolvedValue({
      message: 'Check email', challenge: 'challenge', expires_in: 600, resend_after: 60,
    })
    verifyLoginCodeMock.mockResolvedValue({ message: 'Login successful.', token: 'login-token', user: {
      id: 2, name: 'Freddie', email: 'freddie@example.com',
    } })

    render(<App />)

    await screen.findByText('Backend is connected. Status: ok')

    fireEvent.change(screen.getByLabelText('Email'), {
      target: { value: 'freddie@example.com' },
    })

    fireEvent.change(screen.getByLabelText('Password'), {
      target: { value: 'password123' },
    })

    fireEvent.change(screen.getByLabelText('CAPTCHA answer'), {
      target: { value: '10' },
    })

    fireEvent.click(screen.getByRole('button', { name: 'Log in' }))

    fireEvent.change(await screen.findByLabelText('Email verification code'), {
      target: { value: '123456' },
    })
    fireEvent.click(screen.getByRole('button', { name: 'Verify and sign in' }))

    await waitFor(() => {
      expect(loginUserMock).toHaveBeenCalledWith({
        email: 'freddie@example.com',
        password: 'password123',
        captcha_token: 'captcha-token',
        captcha_answer: '10',
      })
    })

    expect(await screen.findByText('Welcome, Freddie')).toBeInTheDocument()
  })

  test('refreshes the CAPTCHA after an authentication error', async () => {
    getCaptchaChallengeMock
      .mockResolvedValueOnce({
        driver: 'internal',
        token: 'first-captcha-token',
        question: '2 + 8 = ?',
        expires_in: 300,
      })
      .mockResolvedValueOnce({
        driver: 'internal',
        token: 'second-captcha-token',
        question: '3 + 4 = ?',
        expires_in: 300,
      })

    loginUserMock.mockRejectedValue(
      new Error('The CAPTCHA answer is invalid or expired.'),
    )

    render(<App />)

    await screen.findByText('Backend is connected. Status: ok')

    fireEvent.change(screen.getByLabelText('Email'), {
      target: { value: 'freddie@example.com' },
    })

    fireEvent.change(screen.getByLabelText('Password'), {
      target: { value: 'password123' },
    })

    fireEvent.change(screen.getByLabelText('CAPTCHA answer'), {
      target: { value: '11' },
    })

    fireEvent.click(screen.getByRole('button', { name: 'Log in' }))

    expect(
      await screen.findByText('The CAPTCHA answer is invalid or expired.'),
    ).toBeInTheDocument()

    expect(await screen.findByText('3 + 4 = ?')).toBeInTheDocument()
    expect(getCaptchaChallengeMock).toHaveBeenCalledTimes(2)
  })

  test('displays authentication errors', async () => {
    loginUserMock.mockRejectedValue(
      new Error('The provided credentials are incorrect.'),
    )

    render(<App />)

    await screen.findByText('Backend is connected. Status: ok')

    fireEvent.change(screen.getByLabelText('Email'), {
      target: { value: 'freddie@example.com' },
    })

    fireEvent.change(screen.getByLabelText('Password'), {
      target: { value: 'wrong-password' },
    })

    fireEvent.change(screen.getByLabelText('CAPTCHA answer'), {
      target: { value: '10' },
    })

    fireEvent.click(screen.getByRole('button', { name: 'Log in' }))

    expect(
      await screen.findByText('The provided credentials are incorrect.'),
    ).toBeInTheDocument()
  })

  test('loads Telegram chats before selected chat history', async () => {
    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')

    const user = {
      id: 1,
      name: 'Freddie',
      email: 'freddie@example.com',
    }

    getCurrentUserMock.mockResolvedValue({ user })

    const channel: TelegramChat = {
      id: 100,
      type: 'channel',
      tdlib_type: 'chatTypeSupergroup',
      title: 'News channel',
      unread_count: 2,
      last_message: 'Latest news',
      last_message_date: 200,
      messages_count: 0,
    }

    const historyResponse: TelegramSampleResponse = {
      message: 'Telegram content loaded.',
      data: {
        provider: 'telegram',
        chats: [channel],
        selected_chat: {
          ...channel,
          last_message: 'Latest news',
          messages_count: 1,
        },
        messages: [
          {
            id: 200,
            chat_id: 100,
            date: 200,
            sender_id: null,
            text: 'Latest news',
          },
        ],
        has_more: false,
        next_before_message_id: null,
      },
    }

    getTelegramSampleMock
      .mockResolvedValueOnce({
        message: 'Telegram content loaded.',
        data: {
          provider: 'telegram',
          chats: [channel],
          selected_chat: channel,
          messages: [],
          has_more: false,
          next_before_message_id: null,
        },
      })
      .mockResolvedValueOnce(historyResponse)

    render(<App />)

    expect(await screen.findByText('Welcome, Freddie')).toBeInTheDocument()

    fireEvent.click(screen.getByRole('tab', { name: 'Telegram' }))

    expect(
      await screen.findByRole('button', { name: /News channel/ }),
    ).toBeInTheDocument()
    expect((await screen.findAllByText('Latest news')).length).toBeGreaterThanOrEqual(1)

    expect(getTelegramSampleMock).toHaveBeenNthCalledWith(
      1,
      undefined,
      {},
      expect.any(AbortSignal),
    )
    expect(getTelegramSampleMock).toHaveBeenNthCalledWith(
      2,
      100,
      {},
      expect.any(AbortSignal),
    )
  })

  test('allows an authenticated user to log out', async () => {
    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')

    getCurrentUserMock.mockResolvedValue({
      user: {
        id: 1,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

    logoutUserMock.mockResolvedValue()

    render(<App />)

    expect(await screen.findByText('Welcome, Freddie')).toBeInTheDocument()

    fireEvent.click(screen.getByRole('button', { name: 'Log out' }))

    await waitFor(() => {
      expect(logoutUserMock).toHaveBeenCalledTimes(1)
    })

    expect(
      await screen.findByRole('button', { name: 'Log in' }),
    ).toBeInTheDocument()
  })

  test('displays an empty authenticated workspace', async () => {
    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')

    getCurrentUserMock.mockResolvedValue({
      user: {
        id: 1,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

    render(<App />)

    expect(await screen.findByText('Welcome, Freddie')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Inbox' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Labels' })).not.toBeInTheDocument()
    expect(await screen.findByRole('region', { name: 'Empty inbox' })).toBeInTheDocument()
    expect(screen.getByText('No conversations found')).toBeInTheDocument()
    expect(
      screen.getByText(
        'Connect or synchronize a provider, or adjust the search, status, label, and read-state filters.',
      ),
    ).toBeInTheDocument()
  })

  test('displays conversations and messages in the workspace', async () => {
    const conversation: Conversation = {
      id: 1,
      user_id: 1,
      provider: 'fixture',
      provider_conversation_id: 'conversation-1',
      subject: 'Project Alpha planning',
      status: 'open',
      is_read: false,
      last_message_at: '2026-09-12T10:00:00.000Z',
      labels: ['work', 'important'],
      created_at: '2026-09-12T09:00:00.000Z',
      updated_at: '2026-09-12T10:00:00.000Z',
      latest_message: {
        id: 2,
        conversation_id: 1,
        provider: 'fixture',
        provider_message_id: 'message-2',
        sender_name: 'Project Manager',
        sender_address: 'manager@example.com',
        recipient_name: 'Freddie',
        recipient_address: 'freddie@example.com',
        subject: 'Project Alpha planning',
        body: 'The updated plan is ready for review.',
        sent_at: '2026-09-12T10:00:00.000Z',
        is_read: false,
        metadata: {},
      },
    }

    const message: Message = {
      id: 2,
      conversation_id: 1,
      provider: 'fixture',
      provider_message_id: 'message-2',
      sender_name: 'Project Manager',
      sender_address: 'manager@example.com',
      recipient_name: 'Freddie',
      recipient_address: 'freddie@example.com',
      subject: 'Project Alpha planning',
      body: 'The updated plan is ready for review.',
      sent_at: '2026-09-12T10:00:00.000Z',
      is_read: false,
      metadata: {},
    }

    getCurrentUserMock.mockResolvedValue({
      user: {
        id: 1,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

    getConversationsMock.mockResolvedValue({
      ...emptyPagination<Conversation>(),
      data: [conversation],
      from: 1,
      to: 1,
      total: 1,
    })

    getConversationMessagesMock.mockResolvedValue({
      ...emptyPagination<Message>(),
      data: [message],
      from: 1,
      to: 1,
      total: 1,
    })

    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')

    render(<App />)

    expect(
      (await screen.findAllByText('Project Alpha planning')).length,
    ).toBeGreaterThanOrEqual(1)

    expect(
      (await screen.findAllByText('The updated plan is ready for review.')).length,
    ).toBeGreaterThanOrEqual(1)

    expect(
      await screen.findByText('Project Manager'),
    ).toBeInTheDocument()

    expect(screen.getByText('open')).toBeInTheDocument()

    expect(
      getConversationsMock,
    ).toHaveBeenCalledWith({
      search: undefined,
      is_read: undefined,
      status: undefined,
      label: undefined,
      per_page: 15,
      page: 1,
    })

    await waitFor(() => {
      expect(getConversationMessagesMock).toHaveBeenCalledWith(1)
    })
  })

  test('performs an initial lazy Gmail sync when Gmail is connected', async () => {
    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')

    getCurrentUserMock.mockResolvedValue({
      user: {
        id: 1,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

    getGmailStatusMock.mockResolvedValue({
      connected: true,
      connection: {
        id: 1,
        provider: 'gmail',
        provider_account_id: 'freddie@example.com',
        account_email: 'freddie@example.com',
        status: 'connected',
        scopes: [],
        token_expires_at: null,
      },
    })

    syncGmailPageMock.mockResolvedValue({
      message: 'Gmail page synchronized.',
      sync: {
        threads_processed: 15,
        messages_processed: 20,
        has_more: true,
      },
    })

    render(<App />)

    await screen.findByText('Welcome, Freddie')

    await waitFor(() => {
      expect(syncGmailPageMock).toHaveBeenCalledTimes(1)
    })

    expect(getGmailStatusMock).toHaveBeenCalled()
  })
  test('loads the next Gmail page when local pagination is exhausted but Gmail has more', async () => {
    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')

    getCurrentUserMock.mockResolvedValue({
      user: {
        id: 1,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

    getGmailStatusMock.mockResolvedValue({
      connected: true,
      connection: {
        id: 1,
        provider: 'gmail',
        provider_account_id: 'freddie@example.com',
        account_email: 'freddie@example.com',
        status: 'connected',
        scopes: [],
        token_expires_at: null,
      },
    })

    syncGmailPageMock
      .mockResolvedValueOnce({
        message: 'Initial Gmail page synchronized.',
        sync: {
          threads_processed: 15,
          messages_processed: 20,
          has_more: true,
        },
      })
      .mockResolvedValueOnce({
        message: 'Next Gmail page synchronized.',
        sync: {
          threads_processed: 15,
          messages_processed: 18,
          has_more: false,
        },
      })

    getConversationsMock
      .mockResolvedValueOnce({
        ...emptyPagination<Conversation>(),
        current_page: 1,
        last_page: 1,
        from: 1,
        to: 15,
        total: 15,
      })
      .mockResolvedValueOnce({
        ...emptyPagination<Conversation>(),
        current_page: 2,
        last_page: 2,
        from: 16,
        to: 30,
        total: 30,
      })

    render(<App />)

    await screen.findByText('Welcome, Freddie')

    await waitFor(() => {
      expect(syncGmailPageMock).toHaveBeenCalledTimes(1)
    })

    fireEvent.click(await screen.findByRole('button', { name: 'Next' }))

    await waitFor(() => {
      expect(syncGmailPageMock).toHaveBeenCalledTimes(2)
    })

    await waitFor(() => {
      expect(getConversationsMock).toHaveBeenLastCalledWith({
        search: undefined,
        is_read: undefined,
        status: undefined,
        label: undefined,
        per_page: 15,
        page: 2,
      })
    })
  })
  test('displays a classified Gmail callback error', () => {
    window.history.pushState({}, '', '/?gmail=gmail_rate_limit')

    render(<App />)

    expect(
      screen.getByText(
        'Gmail request limit was reached. Try again later.',
      ),
    ).toBeInTheDocument()
  })
  test('fades Gmail feedback after five seconds', () => {
    vi.useFakeTimers()
    window.history.pushState({}, '', '/?gmail=connected')

    render(<App />)

    expect(
      screen.getByText('Gmail connected successfully.'),
    ).toBeInTheDocument()

    act(() => {
      vi.advanceTimersByTime(5000)
    })

    expect(
      screen.queryByText('Gmail connected successfully.'),
    ).not.toBeInTheDocument()
  })

  test('filters conversations by status', async () => {
    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')

    getCurrentUserMock.mockResolvedValue({
      user: {
        id: 1,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

    render(<App />)

    await screen.findByText('Welcome, Freddie')

    fireEvent.change(screen.getByLabelText('Status'), {
      target: { value: 'archived' },
    })

    await waitFor(() => {
      expect(getConversationsMock).toHaveBeenLastCalledWith({
        search: undefined,
        is_read: undefined,
        status: 'archived',
        label: undefined,
        per_page: 15,
        page: 1,
      })
    })
  })

  test('filters conversations by label', async () => {
    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')

    getCurrentUserMock.mockResolvedValue({
      user: {
        id: 1,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

    render(<App />)

    await screen.findByText('Welcome, Freddie')

    fireEvent.change(screen.getByLabelText('Label'), {
      target: { value: 'INBOX' },
    })

    await waitFor(() => {
      expect(getConversationsMock).toHaveBeenLastCalledWith({
        search: undefined,
        is_read: undefined,
        status: undefined,
        label: 'INBOX',
        per_page: 15,
        page: 1,
      })
    })
  })
  test('searches conversations from the workspace', async () => {
    sessionStorage.setItem('omni_desk_auth_token', 'existing-token')

    getCurrentUserMock.mockResolvedValue({
      user: {
        id: 1,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

    render(<App />)

    await screen.findByText('Welcome, Freddie')

    fireEvent.change(screen.getByLabelText('Search conversations'), {
      target: { value: 'Project' },
    })

    fireEvent.click(screen.getByRole('button', { name: 'Search' }))

    await waitFor(() => {
      expect(getConversationsMock).toHaveBeenLastCalledWith({
        search: 'Project',
        is_read: undefined,
        status: undefined,
        label: undefined,
        per_page: 15,
        page: 1,
      })
    })
  })
})
