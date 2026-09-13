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
  loginUser,
  logoutUser,
  registerUser,
  type Conversation,
  type Message,
  type PaginatedResponse,
} from './lib/api'

vi.mock('./lib/api', () => ({
  getCaptchaChallenge: vi.fn(),
  getConversationMessages: vi.fn(),
  getConversations: vi.fn(),
  getGmailStatus: vi.fn(),
  getCurrentUser: vi.fn(),
  getHealth: vi.fn(),
  loginUser: vi.fn(),
  logoutUser: vi.fn(),
  registerUser: vi.fn(),
}))

const getCaptchaChallengeMock = vi.mocked(getCaptchaChallenge)
const getConversationMessagesMock = vi.mocked(getConversationMessages)
const getConversationsMock = vi.mocked(getConversations)
const getGmailStatusMock = vi.mocked(getGmailStatus)
const getHealthMock = vi.mocked(getHealth)
const getCurrentUserMock = vi.mocked(getCurrentUser)
const loginUserMock = vi.mocked(loginUser)
const logoutUserMock = vi.mocked(logoutUser)
const registerUserMock = vi.mocked(registerUser)

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
  window.history.replaceState({}, '', '/')
  sessionStorage.clear()
})

describe('App', () => {
  beforeEach(() => {
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
  })

  test('displays a successful backend connection', async () => {
    render(<App />)

    expect(
      await screen.findByText('Backend is connected. Status: ok'),
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
      message: 'Login successful.',
      token: 'login-token',
      user: {
        id: 2,
        name: 'Freddie',
        email: 'freddie@example.com',
      },
    })

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
    expect(screen.getByRole('button', { name: 'Inbox' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Labels' })).toBeInTheDocument()
    expect(await screen.findByRole('region', { name: 'Empty inbox' })).toBeInTheDocument()
    expect(screen.getByText('No conversations found')).toBeInTheDocument()
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

    expect(
      getConversationsMock,
    ).toHaveBeenCalledWith({
      search: undefined,
      is_read: undefined,
      per_page: 15,
      page: 1,
    })

    await waitFor(() => {
      expect(getConversationMessagesMock).toHaveBeenCalledWith(1)
    })
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
        per_page: 15,
        page: 1,
      })
    })
  })
})
