import { cleanup, fireEvent, render, screen } from '@testing-library/react'
import { afterEach, expect, test, vi } from 'vitest'
import { InboxTelemetry, MotionToggle } from './HudGraphics'

afterEach(() => { cleanup(); vi.unstubAllGlobals(); localStorage.removeItem('omni_desk_motion'); delete document.documentElement.dataset.motion })

test('summarizes only the loaded Gmail page and updates when its data changes', () => {
  const { rerender } = render(<InboxTelemetry provider="gmail" total={5} unread={2} loading={false} failed={false} />)
  expect(screen.getByRole('img', { name: '2 unread and 3 read conversations in the loaded view' })).toBeInTheDocument()
  expect(screen.getByText('conversations on this page')).toBeInTheDocument()
  rerender(<InboxTelemetry provider="gmail" total={1} unread={0} loading={false} failed={false} />)
  expect(screen.getByRole('img', { name: '0 unread and 1 read conversations in the loaded view' })).toBeInTheDocument()
})

test('does not present stale counts as available data after an error', () => {
  render(<InboxTelemetry provider="telegram" total={5} unread={2} loading={false} failed />)
  expect(screen.getByText('Data unavailable')).toBeInTheDocument()
  expect(screen.getByRole('img', { name: 'Overview unavailable until data loads successfully' })).toBeInTheDocument()
  expect(screen.queryByText('5')).not.toBeInTheDocument()
})

test('distinguishes an empty loaded view from a pending request', () => {
  const { rerender } = render(<InboxTelemetry provider="telegram" total={0} unread={0} loading failed={false} />)
  expect(screen.getByText('Loading view')).toBeInTheDocument()
  rerender(<InboxTelemetry provider="telegram" total={0} unread={0} loading={false} failed={false} />)
  expect(screen.getByRole('img', { name: '0 unread and 0 read chats in the loaded view' })).toBeInTheDocument()
})

test('persists the motion control and applies the reduced-motion state', () => {
  localStorage.setItem('omni_desk_motion', 'full')
  render(<MotionToggle />)
  fireEvent.click(screen.getByRole('button', { name: 'Reduce interface motion' }))
  expect(document.documentElement.dataset.motion).toBe('reduced')
  expect(localStorage.getItem('omni_desk_motion')).toBe('reduced')
  expect(screen.getByRole('button', { name: 'Enable interface motion' })).toHaveAttribute('aria-pressed', 'false')
})

test('honors a system reduced-motion preference even when full motion was previously saved', () => {
  vi.stubGlobal('matchMedia', vi.fn(() => ({ matches: true, addEventListener: vi.fn(), removeEventListener: vi.fn() })))
  localStorage.setItem('omni_desk_motion', 'full')
  render(<MotionToggle />)
  expect(document.documentElement.dataset.motion).toBe('reduced')
  expect(screen.getByRole('button', { name: 'Interface motion reduced by system preference' })).toBeDisabled()
  expect(screen.queryByText('Motion on')).not.toBeInTheDocument()
})
