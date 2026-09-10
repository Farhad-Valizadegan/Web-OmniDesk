export type HealthResponse = {
  status: string
  service: string
}

export type AuthUser = {
  id: number
  name: string
  email: string
  email_verified_at?: string | null
}

export type AuthResponse = {
  message: string
  user: AuthUser
  token: string
}

export type CurrentUserResponse = {
  user: AuthUser
}

const tokenStorageKey = 'omni_desk_auth_token'

export function getStoredToken(): string | null {
  return sessionStorage.getItem(tokenStorageKey)
}

function storeToken(token: string): void {
  sessionStorage.setItem(tokenStorageKey, token)
}

function clearStoredToken(): void {
  sessionStorage.removeItem(tokenStorageKey)
}

async function getErrorMessage(response: Response): Promise<string> {
  try {
    const data = (await response.json()) as {
      message?: string
      errors?: Record<string, string[]>
    }

    const firstValidationError = data.errors
      ? Object.values(data.errors).flat()[0]
      : undefined

    return firstValidationError ?? data.message ?? 'The request failed.'
  } catch {
    return 'The request failed.'
  }
}

async function request<T>(
  input: RequestInfo | URL,
  init?: RequestInit,
): Promise<T> {
  const token = getStoredToken()

  const headers = new Headers(init?.headers)
  headers.set('Accept', 'application/json')
  headers.set('Content-Type', 'application/json')

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(input, {
    ...init,
    headers,
    credentials: 'same-origin',
  })

  if (!response.ok) {
    throw new Error(await getErrorMessage(response))
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}

export async function getHealth(): Promise<HealthResponse> {
  return request<HealthResponse>('/api/health')
}

export async function registerUser(data: {
  name: string
  email: string
  password: string
  password_confirmation: string
}): Promise<AuthResponse> {
  const response = await request<AuthResponse>('/api/register', {
    method: 'POST',
    body: JSON.stringify(data),
  })

  storeToken(response.token)

  return response
}

export async function loginUser(data: {
  email: string
  password: string
}): Promise<AuthResponse> {
  const response = await request<AuthResponse>('/api/login', {
    method: 'POST',
    body: JSON.stringify(data),
  })

  storeToken(response.token)

  return response
}

export async function getCurrentUser(): Promise<CurrentUserResponse> {
  return request<CurrentUserResponse>('/api/user')
}

export async function logoutUser(): Promise<void> {
  try {
    await request<void>('/api/logout', {
      method: 'POST',
    })
  } finally {
    clearStoredToken()
  }
}