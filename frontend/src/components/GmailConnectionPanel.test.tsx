import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import GmailConnectionPanel from './GmailConnectionPanel'
import {
  disconnectGmail,
  getGmailStatus,
  type GmailStatusResponse,
} from '../lib/api'

vi.mock('../lib/api', () => ({
  disconnectGmail: vi.fn(),
  getGmailStatus: vi.fn(),
  startGmailAuthorization: vi.fn(),
}))

const getGmailStatusMock = vi.mocked(getGmailStatus)
const disconnectGmailMock = vi.mocked(disconnectGmail)

afterEach(() => {
  cleanup()
  vi.clearAllMocks()
})

describe('GmailConnectionPanel', () => {
  beforeEach(() => {
    getGmailStatusMock.mockResolvedValue({
      connected: false,
      connection: null,
    })
  })

  test('displays the disconnected state', async () => {
    render(<GmailConnectionPanel />)

    expect(
      await screen.findByText('Gmail is not connected.'),
    ).toBeInTheDocument()

    expect(
      screen.getByRole('button', { name: 'Connect Gmail' }),
    ).toBeInTheDocument()
  })

  test('displays the connected account', async () => {
    const connectedStatus: GmailStatusResponse = {
      connected: true,
      connection: {
        id: 1,
        provider: 'gmail',
        provider_account_id: 'google-account-123',
        account_email: 'person@example.com',
        status: 'connected',
        scopes: ['openid', 'email'],
        token_expires_at: '2026-09-12T18:00:00Z',
      },
    }

    getGmailStatusMock.mockResolvedValue(connectedStatus)

    render(<GmailConnectionPanel />)

    expect(
      await screen.findByText('person@example.com'),
    ).toBeInTheDocument()

    expect(
      screen.getByText('Connected'),
    ).toBeInTheDocument()

    expect(
      screen.getByRole('button', { name: 'Disconnect Gmail' }),
    ).toBeInTheDocument()
  })

  test('disconnects Gmail and displays the disconnected state', async () => {
    getGmailStatusMock.mockResolvedValue({
      connected: true,
      connection: {
        id: 1,
        provider: 'gmail',
        provider_account_id: 'google-account-123',
        account_email: 'person@example.com',
        status: 'connected',
        scopes: ['openid', 'email'],
        token_expires_at: null,
      },
    })

    disconnectGmailMock.mockResolvedValue(undefined)

    render(<GmailConnectionPanel />)

    fireEvent.click(
      await screen.findByRole('button', {
        name: 'Disconnect Gmail',
      }),
    )

    await waitFor(() => {
      expect(disconnectGmailMock).toHaveBeenCalledTimes(1)
    })

    expect(
      await screen.findByText('Gmail is not connected.'),
    ).toBeInTheDocument()
  })

  test('displays a status error', async () => {
    getGmailStatusMock.mockRejectedValue(
      new Error('Status request failed'),
    )

    render(<GmailConnectionPanel />)

    expect(
      await screen.findByRole('alert'),
    ).toHaveTextContent(
      'Gmail connection status could not be loaded.',
    )

    expect(
      screen.getByRole('button', { name: 'Retry' }),
    ).toBeInTheDocument()
  })
})
