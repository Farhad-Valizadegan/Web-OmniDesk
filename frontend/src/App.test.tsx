import { render, screen } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from './App'
import { getHealth } from './lib/api'

vi.mock('./lib/api', () => ({
  getHealth: vi.fn(),
}))

const mockedGetHealth = vi.mocked(getHealth)

describe('App', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('displays a successful backend connection', async () => {
    mockedGetHealth.mockResolvedValue({
      status: 'ok',
      service: 'backend',
    })

    render(<App />)

    expect(
      await screen.findByText('Backend is connected. Status: ok'),
    ).toBeInTheDocument()
  })
})