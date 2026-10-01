import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import {
  authenticationInvalidatedEvent,
  getCurrentUser,
  logoutUser,
  getConversationMessages,
  getTelegramMedia,
  loginUser,
  verifyLoginCode,
} from './api'

const tokenStorageKey = 'omni_desk_auth_token'

describe('API authentication handling', () => {
  test('stores no session after password step and stores it after code verification', async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce(new Response(JSON.stringify({
        message: 'Check your email for a sign-in code.',
        challenge: 'challenge-id',
        expires_in: 600,
        resend_after: 60,
      }), { status: 200, headers: { 'Content-Type': 'application/json' } }))
      .mockResolvedValueOnce(new Response(JSON.stringify({
        message: 'Login successful.',
        user: { id: 1, name: 'Freddie', email: 'freddie@example.com' },
        token: 'verified-token',
      }), { status: 200, headers: { 'Content-Type': 'application/json' } }))
    vi.stubGlobal('fetch', fetchMock)

    const challenge = await loginUser({ email: 'freddie@example.com', password: 'password123' })
    expect(challenge.challenge).toBe('challenge-id')
    expect(sessionStorage.getItem(tokenStorageKey)).toBeNull()

    await verifyLoginCode(challenge.challenge, '123456')
    expect(sessionStorage.getItem(tokenStorageKey)).toBe('verified-token')
  })

  test('downloads media with bearer headers without putting credentials in its URL', async () => {
    sessionStorage.setItem(tokenStorageKey, 'media-token')
    const fetchMock = vi.fn().mockResolvedValue(new Response('image-bytes', { headers: { 'Content-Type': 'image/jpeg' } }))
    vi.stubGlobal('fetch', fetchMock)
    const result = await getTelegramMedia(10, 20, new AbortController().signal)
    expect(result.type).toBe('image/jpeg')
    const [url, options] = fetchMock.mock.calls[0]
    expect(url).toBe('/api/integrations/telegram/media?chat_id=10&message_id=20')
    expect(options.headers.get('Authorization')).toBe('Bearer media-token')
    expect(options.headers.get('Accept')).toBe('application/json')
  })

  test('invalidates authentication when a media request returns 401', async () => {
    sessionStorage.setItem(tokenStorageKey, 'expired-media-token')
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({ message: 'Unauthenticated.' }), { status: 401 })))
    await expect(getTelegramMedia(10, 20, new AbortController().signal)).rejects.toThrow('Unauthenticated.')
    expect(sessionStorage.getItem(tokenStorageKey)).toBeNull()
  })

  beforeEach(() => {
    sessionStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.restoreAllMocks()
    sessionStorage.clear()
  })

  test('clears the stored bearer token and invalidates UI auth state on 401', async () => {
    sessionStorage.setItem(tokenStorageKey, 'expired-token')

    const invalidated = vi.fn()
    window.addEventListener(authenticationInvalidatedEvent, invalidated)

    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(
        new Response(JSON.stringify({ message: 'Unauthenticated.' }), {
          status: 401,
          headers: { 'Content-Type': 'application/json' },
        }),
      ),
    )

    await expect(getCurrentUser()).rejects.toThrow('Unauthenticated.')

    expect(sessionStorage.getItem(tokenStorageKey)).toBeNull()
    expect(invalidated).toHaveBeenCalledTimes(1)

    window.removeEventListener(authenticationInvalidatedEvent, invalidated)
  })

  test('does not clear the bearer token for a non-authentication server error', async () => {
    sessionStorage.setItem(tokenStorageKey, 'active-token')

    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(
        new Response(JSON.stringify({ message: 'Server error.' }), {
          status: 500,
          headers: { 'Content-Type': 'application/json' },
        }),
      ),
    )

    await expect(getCurrentUser()).rejects.toThrow('Server error.')

    expect(sessionStorage.getItem(tokenStorageKey)).toBe('active-token')
  })

  test('clears the local token even when the logout request fails', async () => {
    sessionStorage.setItem(tokenStorageKey, 'active-token')
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('Offline')))
    await expect(logoutUser()).rejects.toThrow('Offline')
    expect(sessionStorage.getItem(tokenStorageKey)).toBeNull()
  })

  test('requests a later message page through the owned conversation endpoint', async () => {
    const fetchMock = vi.fn().mockResolvedValue(new Response(JSON.stringify({ data: [] }), { status: 200 }))
    vi.stubGlobal('fetch', fetchMock)
    await getConversationMessages(17, 50, 2)
    expect(fetchMock).toHaveBeenCalledWith('/api/conversations/17/messages?per_page=50&page=2', expect.any(Object))
  })
})
