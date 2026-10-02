import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react'
import { afterEach, beforeEach, expect, test, vi } from 'vitest'
import Workspace from './Workspace'
import { connectTelegram, getTelegramSample, getTelegramStatus, submitTelegramCode, type TelegramChat } from '../lib/api'
import { getConversations, getConversationMessages, getGmailStatus, type Conversation, type Message, type PaginatedResponse } from '../lib/api'

vi.mock('../lib/api', () => ({
  getConversations: vi.fn(), getConversationMessages: vi.fn(), getGmailStatus: vi.fn(),
  getTelegramSample: vi.fn(), syncGmailPage: vi.fn(), disconnectGmail: vi.fn(),
  getTelegramStatus: vi.fn(), connectTelegram: vi.fn(), submitTelegramCode: vi.fn(), submitTelegramPassword: vi.fn(), disconnectTelegram: vi.fn(),
  startGmailAuthorization: vi.fn(), syncGmail: vi.fn(),
}))

function page<T>(data: T[], lastPage = 1): PaginatedResponse<T> {
  return { data, current_page: 1, last_page: lastPage, total: data.length, per_page: 50, from: 1, to: data.length,
    first_page_url: '', last_page_url: '', next_page_url: null, prev_page_url: null, path: '', links: [] }
}

function conversation(id: number): Conversation {
  return { id, user_id: 1, provider: 'gmail', provider_conversation_id: String(id), subject: `Thread ${id}`, status: 'open',
    is_read: true, last_message_at: null, labels: [], created_at: '', updated_at: '' }
}

function message(id: number): Message {
  return { id, conversation_id: id, provider: 'gmail', provider_message_id: String(id), sender_name: `Sender ${id}`,
    sender_address: null, recipient_name: null, recipient_address: null, subject: null, body: `Message ${id}`,
    sent_at: '2026-09-01T12:00:00Z', is_read: true, metadata: null }
}

function deferred<T>() {
  let resolve!: (value: T) => void
  const promise = new Promise<T>((done) => { resolve = done })
  return { promise, resolve }
}

function showWorkspace() {
  return render(<Workspace user={{ id: 1, name: 'Freddie', email: 'f@example.com' }} isLoading={false} onLogout={vi.fn()} />)
}

beforeEach(() => {
  vi.mocked(getTelegramStatus).mockResolvedValue({ connected: true, status: 'connected', phone: '+49123456789' })
  vi.mocked(getGmailStatus).mockResolvedValue({ connected: false, connection: null })
  vi.mocked(getConversations).mockResolvedValue(page([conversation(1), conversation(2)]))
  vi.mocked(getConversationMessages).mockResolvedValue(page([]))
})
afterEach(() => { cleanup(); vi.resetAllMocks() })

test('reports a Telegram history failure without claiming the chat is empty', async () => {
  const chat: TelegramChat = { id: 123, type: 'channel', tdlib_type: 'chatTypeSupergroup',
    title: 'Example channel', unread_count: 1, last_message: 'Preview', last_message_date: 1 }
  vi.mocked(getTelegramSample).mockResolvedValueOnce({ message: 'Loaded', data: {
    provider: 'telegram', chats: [chat], selected_chat: chat, messages: [],
    has_more: false, next_before_message_id: null,
  } }).mockRejectedValueOnce(new Error('Telegram adapter request failed.'))
  showWorkspace()
  fireEvent.click(screen.getByRole('tab', { name: 'Telegram' }))
  expect(await screen.findByText('Messages could not be loaded. Refresh to try again.')).toBeInTheDocument()
  expect(screen.queryByText('No messages available in this chat.')).not.toBeInTheDocument()
})

test('renders a Telegram profile photo when the adapter supplies one', async () => {
  const chat: TelegramChat = { id: 123, type: 'private', tdlib_type: 'chatTypePrivate',
    title: 'Photo chat', unread_count: 0, last_message: 'Hello', last_message_date: 1,
    photo: { mime_type: 'image/jpeg', data: '/9j/AA==' } }
  vi.mocked(getTelegramSample).mockResolvedValue({ message: 'Loaded', data: {
    provider: 'telegram', chats: [chat], selected_chat: chat, messages: [{
      id: 1, chat_id: chat.id, date: 1, sender_id: null, text: 'Hello',
    }], has_more: false, next_before_message_id: null,
  } })
  showWorkspace()
  fireEvent.click(screen.getByRole('tab', { name: 'Telegram' }))
  expect(await screen.findByAltText('Photo chat profile')).toHaveAttribute(
    'src', 'data:image/jpeg;base64,/9j/AA==',
  )
})

test('connects a user Telegram account through the phone and code steps', async () => {
  vi.mocked(getTelegramStatus).mockResolvedValue({ connected: false, status: 'disconnected', phone: null })
  vi.mocked(connectTelegram).mockResolvedValue({ status: 'awaiting_code' })
  showWorkspace()
  fireEvent.click(screen.getByRole('tab', { name: 'Telegram' }))

  fireEvent.change(await screen.findByLabelText('Phone number'), { target: { value: '0049123456789' } })
  fireEvent.click(screen.getByRole('button', { name: 'Send Telegram code' }))

  await waitFor(() => expect(connectTelegram).toHaveBeenCalledWith('+49123456789'))
  expect(await screen.findByLabelText('Telegram code')).toBeInTheDocument()
})

test('shows an authorization error beside the Telegram code form', async () => {
  vi.mocked(getTelegramStatus).mockResolvedValue({ connected: false, status: 'awaiting_code', phone: '+49••••111' })
  vi.mocked(submitTelegramCode).mockRejectedValue(new Error('The Telegram code is invalid or expired.'))
  showWorkspace()
  fireEvent.click(screen.getByRole('tab', { name: 'Telegram' }))

  fireEvent.change(await screen.findByLabelText('Telegram code'), { target: { value: '12345' } })
  fireEvent.click(screen.getByRole('button', { name: 'Continue' }))

  expect(await screen.findByRole('alert')).toHaveTextContent('The Telegram code is invalid or expired.')
  expect(screen.getByRole('button', { name: 'Start over with phone number' })).toBeInTheDocument()
})

test('ignores an older conversation message response after selecting another thread', async () => {
  const first = deferred<PaginatedResponse<Message>>()
  vi.mocked(getConversationMessages).mockImplementation((id) => id === 1 ? first.promise : Promise.resolve(page([message(2)])))
  showWorkspace()
  await waitFor(() => expect(getConversationMessages).toHaveBeenCalledWith(1))
  fireEvent.click(screen.getByRole('button', { name: /Thread 2/ }))
  await screen.findByText('Message 2')
  await act(async () => first.resolve(page([message(1)])))
  expect(screen.getByText('Message 2')).toBeInTheDocument()
  expect(screen.queryByText('Message 1')).not.toBeInTheDocument()
})

test('ignores an old inbox response after changing search', async () => {
  const first = deferred<PaginatedResponse<Conversation>>()
  vi.mocked(getConversations).mockImplementation((params) => params?.search ? Promise.resolve(page([conversation(2)])) : first.promise)
  showWorkspace()
  await waitFor(() => expect(getConversations).toHaveBeenCalledTimes(1))
  fireEvent.change(screen.getByLabelText('Search conversations'), { target: { value: 'Thread 2' } })
  fireEvent.click(screen.getByRole('button', { name: 'Search' }))
  await screen.findByRole('button', { name: /Thread 2/ })
  await act(async () => first.resolve(page([conversation(1)])))
  expect(screen.getByRole('button', { name: /Thread 2/ })).toBeInTheDocument()
  expect(screen.queryByRole('button', { name: /Thread 1/ })).not.toBeInTheDocument()
})

test('renders later messages in a conversation longer than fifty messages', async () => {
  vi.mocked(getConversationMessages).mockImplementation((_id, _size, pageNumber) =>
    Promise.resolve(page(pageNumber === 2 ? [message(51)] : Array.from({ length: 50 }, (_, index) => message(index + 1)), 2)))
  showWorkspace()
  expect(await screen.findByText('Message 51')).toBeInTheDocument()
  expect(screen.getByText('Message 1')).toBeInTheDocument()
  expect(getConversationMessages).toHaveBeenCalledWith(1, 50, 2)
})

test('permits sanitized email links to open while keeping scripts and forms sandboxed', async () => {
  vi.mocked(getConversationMessages).mockResolvedValue(page([{ ...message(1), metadata: {
    html_body: '<script>alert(1)</script><a href="https://example.com" onclick="alert(1)">Open</a><a href="javascript:alert(1)">Unsafe</a>',
  } }]))
  showWorkspace()
  const frame = await screen.findByTitle('Email body from Sender 1')
  expect(frame).toHaveAttribute('sandbox', 'allow-popups allow-popups-to-escape-sandbox')
  const content = new DOMParser().parseFromString(frame.getAttribute('srcdoc')!, 'text/html')
  expect(content.querySelector('script')).toBeNull()
  expect(content.querySelector('a')?.getAttribute('target')).toBe('_blank')
  expect(content.querySelector('a')?.getAttribute('rel')).toBe('noopener noreferrer')
  expect(content.querySelector('a')?.hasAttribute('onclick')).toBe(false)
  expect(content.querySelectorAll('a')[1].hasAttribute('href')).toBe(false)
})
