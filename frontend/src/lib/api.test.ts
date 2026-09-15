import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import {
  authenticationInvalidatedEvent,
  getCurrentUser,
} from './api'

const tokenStorageKey = 'omni_desk_auth_token'

describe('API authentication handling', () => {
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
})
