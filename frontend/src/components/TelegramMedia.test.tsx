import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react'
import { afterEach, beforeEach, expect, test, vi } from 'vitest'
import TelegramMedia from './TelegramMedia'
import { getTelegramMedia, type TelegramMessage } from '../lib/api'

vi.mock('../lib/api', () => ({ getTelegramMedia: vi.fn() }))
const message: TelegramMessage = { id: 2, chat_id: 1, date: 0, sender_id: null, text: 'Photo', media: { kind: 'photo' } }

beforeEach(() => {
  vi.stubGlobal('URL', { createObjectURL: vi.fn(() => 'blob:photo'), revokeObjectURL: vi.fn() })
  vi.mocked(getTelegramMedia).mockResolvedValue(new Blob(['image'], { type: 'image/png' }))
})
afterEach(() => { cleanup(); vi.unstubAllGlobals(); vi.resetAllMocks() })

test('loads authenticated photo bytes and releases the object URL on unmount', async () => {
  const view = render(<TelegramMedia message={message} />)
  fireEvent.click(screen.getByRole('button', { name: 'Show photo' }))
  expect(await screen.findByAltText('Telegram photo')).toHaveAttribute('src', 'blob:photo')
  expect(getTelegramMedia).toHaveBeenCalledWith(1, 2, expect.any(AbortSignal))
  view.unmount()
  expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:photo')
})

test('loads video only on request and provides playback controls', async () => {
  const view = render(<TelegramMedia message={{ ...message, media: { kind: 'video' } }} />)
  expect(getTelegramMedia).not.toHaveBeenCalled()
  fireEvent.click(screen.getByRole('button', { name: 'Load video' }))
  await waitFor(() => expect(view.container.querySelector('video')).toHaveAttribute('controls'))
})

test('offers retry after a media failure', async () => {
  vi.mocked(getTelegramMedia).mockRejectedValueOnce(new Error('Unavailable'))
  render(<TelegramMedia message={message} />)
  fireEvent.click(screen.getByRole('button', { name: 'Show photo' }))
  expect(await screen.findByRole('alert')).toHaveTextContent('Unavailable')
  fireEvent.click(screen.getByRole('button', { name: 'Retry' }))
  expect(await screen.findByAltText('Telegram photo')).toBeInTheDocument()
})

test('loads voice recordings on request with audio controls and no autoplay', async () => {
  render(<TelegramMedia message={{ ...message, media: { kind: 'voice' } }} />)
  expect(getTelegramMedia).not.toHaveBeenCalled()
  fireEvent.click(screen.getByRole('button', { name: 'Load voice message' }))
  const player = await screen.findByLabelText('Telegram voice message')
  expect(player.tagName).toBe('AUDIO')
  expect(player).toHaveAttribute('controls')
  expect(player).not.toHaveAttribute('autoplay')
})
