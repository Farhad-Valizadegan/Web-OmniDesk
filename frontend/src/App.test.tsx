import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import App from './App'
import {
  getCurrentUser,
  getHealth,
  loginUser,
  logoutUser,
  registerUser,
} from './lib/api'

vi.mock('./lib/api', () => ({
  getCurrentUser: vi.fn(),
  getHealth: vi.fn(),
  loginUser: vi.fn(),
  logoutUser: vi.fn(),
  registerUser: vi.fn(),
}))

const getHealthMock = vi.mocked(getHealth)
const getCurrentUserMock = vi.mocked(getCurrentUser)
const loginUserMock = vi.mocked(loginUser)
const logoutUserMock = vi.mocked(logoutUser)
const registerUserMock = vi.mocked(registerUser)

afterEach(() => {
  cleanup()
  vi.clearAllMocks()
  sessionStorage.clear()
})

describe('App', () => {
  beforeEach(() => {
    getHealthMock.mockResolvedValue({
      status: 'ok',
      service: 'backend',
    })

    getCurrentUserMock.mockRejectedValue(new Error('Unauthenticated'))
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

    fireEvent.click(screen.getByRole('button', { name: 'Create account' }))

    await waitFor(() => {
      expect(registerUserMock).toHaveBeenCalledWith({
        name: 'Freddie',
        email: 'freddie@example.com',
        password: 'password123',
        password_confirmation: 'password123',
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

    fireEvent.click(screen.getByRole('button', { name: 'Log in' }))

    await waitFor(() => {
      expect(loginUserMock).toHaveBeenCalledWith({
        email: 'freddie@example.com',
        password: 'password123',
      })
    })

    expect(await screen.findByText('Welcome, Freddie')).toBeInTheDocument()
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

  test('displays the authenticated workspace', async () => {
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
    expect(
      screen.getByText('Your communication workspace is ready'),
    ).toBeInTheDocument()
  })
})