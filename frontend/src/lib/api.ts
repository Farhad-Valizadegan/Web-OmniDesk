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

export type LoginChallenge = {
  message: string
  challenge: string
  expires_in: number
  resend_after: number
}

export type CaptchaChallenge =
  | {
      driver: 'internal'
      token: string
      question: string
      expires_in: number
    }
  | {
      driver: 'google'
      site_key: string
    }

export type CurrentUserResponse = {
  user: AuthUser
}

export type GmailConnection = {
  id: number
  provider: string
  provider_account_id: string | null
  account_email: string | null
  account_avatar_url?: string | null
  status: string
  scopes: string[] | null
  token_expires_at: string | null
}

export type GmailStatusResponse = {
  connected: boolean
  connection: GmailConnection | null
}

export type GmailAuthorizationResponse = {
  authorization_url: string
  expires_in: number
}

export type MessageAttachment = {
  filename: string
  mime_type: string
  size: number
  attachment_id: string | null
  inline: boolean
}

export type Message = {
  id: number
  conversation_id: number
  provider: string
  provider_message_id: string | null
  sender_name: string
  sender_address: string | null
  recipient_name: string | null
  recipient_address: string | null
  subject: string | null
  body: string
  sent_at: string
  is_read: boolean
  metadata: {
    label_ids?: string[]
    html_body?: string | null
    attachments?: MessageAttachment[]
  } | null
}

export type Conversation = {
  id: number
  user_id: number
  provider: string
  provider_conversation_id: string | null
  subject: string | null
  status: string
  is_read: boolean
  last_message_at: string | null
  labels: string[] | null
  latest_message?: Message | null
  created_at: string
  updated_at: string
}

export type PaginatedResponse<T> = {
  current_page: number
  data: T[]
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  links: Array<{
    url: string | null
    label: string
    active: boolean
  }>
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

export type TelegramMessage = {
  media?: { kind: 'photo' | 'video' | 'voice'; spoiler?: boolean } | null
  id: number
  chat_id: number
  date: number
  sender_id: Record<string, unknown> | null
  text: string
}

export type TelegramChat = {
  id: number
  type: 'private' | 'group' | 'channel'
  tdlib_type: string
  title: string
  photo?: {
    mime_type: 'image/jpeg' | 'image/png' | 'image/webp'
    data: string
  } | null
  unread_count: number
  last_message: string
  last_message_date: number | null
  messages_count?: number
}

export type TelegramSampleOptions = {
  limit?: number
  beforeMessageId?: number
}

export type TelegramSampleResponse = {
  message: string
  data: {
    provider: 'telegram'
    warnings?: string[]
    chats: TelegramChat[]
    selected_chat: TelegramChat
    messages: TelegramMessage[]
    has_more: boolean
    next_before_message_id: number | null
  }
}

export type TelegramConnectionStatus = {
  connected: boolean
  status: 'disconnected' | 'pending' | 'awaiting_phone' | 'awaiting_code' | 'awaiting_password' | 'connected' | 'error'
  phone: string | null
}

const tokenStorageKey = 'omni_desk_auth_token'

export const authenticationInvalidatedEvent =
  'omni-desk:authentication-invalidated'

export function getStoredToken(): string | null {
  return sessionStorage.getItem(tokenStorageKey)
}

function storeToken(token: string): void {
  sessionStorage.setItem(tokenStorageKey, token)
}

function clearStoredToken(): void {
  sessionStorage.removeItem(tokenStorageKey)
}

function invalidateAuthentication(): void {
  clearStoredToken()
  window.dispatchEvent(new Event(authenticationInvalidatedEvent))
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
  timeoutMs?: number,
): Promise<T> {
  const token = getStoredToken()
  const headers = new Headers(init?.headers)
  headers.set('Accept', 'application/json')
  headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)

  // Bound authentication requests; provider operations retain their own cancellation.
  const controller = timeoutMs ? new AbortController() : null
  const timer = controller ? window.setTimeout(() => controller.abort(), timeoutMs) : null
  try {
    const response = await fetch(input, {
      ...init,
      headers,
      credentials: 'same-origin',
      signal: controller?.signal ?? init?.signal,
    })
    if (response.status === 401) invalidateAuthentication()
    if (!response.ok) throw new Error(await getErrorMessage(response))
    if (response.status === 204) return undefined as T
    return await response.json() as T
  } catch (error) {
    if (controller?.signal.aborted) {
      throw new Error('The server did not respond in time. Please try again shortly.', { cause: error })
    }
    throw error
  } finally {
    if (timer !== null) window.clearTimeout(timer)
  }
}

export async function getHealth(): Promise<HealthResponse> {
  return request<HealthResponse>('/api/health', undefined, 10000)
}

export async function getCaptchaChallenge(): Promise<CaptchaChallenge> {
  return request<CaptchaChallenge>('/api/captcha', undefined, 10000)
}

export async function registerUser(data: {
  name: string
  email: string
  password: string
  password_confirmation: string
  captcha_token?: string
  captcha_answer?: string
  captcha_response?: string
}): Promise<AuthResponse> {
  const response = await request<AuthResponse>('/api/register', {
    method: 'POST',
    body: JSON.stringify(data),
  }, 30000)

  storeToken(response.token)

  return response
}

export async function loginUser(data: {
  email: string
  password: string
  captcha_token?: string
  captcha_answer?: string
  captcha_response?: string
}): Promise<LoginChallenge> {
  return request<LoginChallenge>('/api/login', {
    method: 'POST',
    body: JSON.stringify(data),
  }, 30000)
}

export async function verifyLoginCode(challenge: string, code: string): Promise<AuthResponse> {
  const response = await request<AuthResponse>('/api/login/verify', {
    method: 'POST', body: JSON.stringify({ challenge, code }),
  }, 30000)
  storeToken(response.token)
  return response
}

export function resendLoginCode(challenge: string): Promise<LoginChallenge> {
  return request<LoginChallenge>('/api/login/resend', {
    method: 'POST', body: JSON.stringify({ challenge }),
  }, 30000)
}

export async function getCurrentUser(): Promise<CurrentUserResponse> {
  return request<CurrentUserResponse>('/api/user', undefined, 10000)
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

export async function getConversations(params: {
  search?: string
  status?: string
  is_read?: boolean
  label?: string
  per_page?: number
  page?: number
} = {}): Promise<PaginatedResponse<Conversation>> {
  const searchParams = new URLSearchParams()

  if (params.search) {
    searchParams.set('search', params.search)
  }

  if (params.status) {
    searchParams.set('status', params.status)
  }

  if (params.is_read !== undefined) {
    searchParams.set('is_read', params.is_read ? '1' : '0')
  }

  if (params.label) {
    searchParams.set('label', params.label)
  }

  if (params.per_page !== undefined) {
    searchParams.set('per_page', String(params.per_page))
  }

  if (params.page !== undefined) {
    searchParams.set('page', String(params.page))
  }

  const query = searchParams.toString()

  return request<PaginatedResponse<Conversation>>(
    `/api/conversations${query ? `?${query}` : ''}`,
  )
}

export async function getConversation(
  conversationId: number,
): Promise<{ conversation: Conversation }> {
  return request<{ conversation: Conversation }>(
    `/api/conversations/${conversationId}`,
  )
}

export async function getConversationMessages(
  conversationId: number,
  perPage = 50,
  page = 1,
): Promise<PaginatedResponse<Message>> {
  return request<PaginatedResponse<Message>>(
    `/api/conversations/${conversationId}/messages?per_page=${perPage}&page=${page}`,
  )
}

export async function getGmailStatus(): Promise<GmailStatusResponse> {
  return request<GmailStatusResponse>('/api/integrations/gmail')
}

export async function startGmailAuthorization(): Promise<GmailAuthorizationResponse> {
  return request<GmailAuthorizationResponse>(
    '/api/integrations/gmail/authorize',
  )
}

export type GmailSyncResponse = {
  message: string
  sync: {
    threads_processed: number
    messages_processed: number
    next_page_token: string | null
  }
}

export async function syncGmail(params: {
  max_results?: number
  page_token?: string
} = {}): Promise<GmailSyncResponse> {
  return request<GmailSyncResponse>('/api/integrations/gmail/sync', {
    method: 'POST',
    body: JSON.stringify(params),
  })
}

export type GmailLazySyncResponse = {
  message: string
  sync: {
    threads_processed: number
    messages_processed: number
    has_more: boolean
  }
}

export async function syncGmailPage(): Promise<GmailLazySyncResponse> {
  return request<GmailLazySyncResponse>(
    '/api/integrations/gmail/sync-page',
    {
      method: 'POST',
      body: JSON.stringify({ max_results: 15 }),
    },
  )
}

export type GmailDisconnectResponse = {
  message: string
  error: 'gmail_revocation_failed'
}

export async function disconnectGmail(): Promise<GmailDisconnectResponse | null> {
  const response = await request<GmailDisconnectResponse | undefined>(
    '/api/integrations/gmail',
    {
      method: 'DELETE',
    },
  )

  return response ?? null
}

// The native adapter shares a session lock: queue media downloads rather than
// starting a process per visible photo at once.
let telegramMediaQueue: Promise<unknown> = Promise.resolve()

export function getTelegramMedia(chatId: number, messageId: number, signal: AbortSignal): Promise<Blob> {
  const operation = telegramMediaQueue.then(async () => {
    signal.throwIfAborted()
    const headers = new Headers({ Accept: 'application/json' })
    const token = getStoredToken()
    if (token) headers.set('Authorization', `Bearer ${token}`)
    const params = new URLSearchParams({ chat_id: String(chatId), message_id: String(messageId) })
    // Keep the queue occupied until the server finishes even if the component
    // is removed, because cancelling fetch does not stop its TDLib process.
    const response = await fetch(`/api/integrations/telegram/media?${params}`, { headers, credentials: 'same-origin' })
    if (response.status === 401) invalidateAuthentication()
    if (!response.ok) throw new Error(await getErrorMessage(response))
    const blob = await response.blob()
    signal.throwIfAborted()
    return blob
  })
  telegramMediaQueue = operation.catch(() => undefined)
  return operation
}

export async function getTelegramSample(
  chatId?: number,
  options: TelegramSampleOptions = {},
  signal?: AbortSignal,
): Promise<TelegramSampleResponse> {
  const params = new URLSearchParams()
  params.set('limit', String(options.limit ?? 20))

  if (chatId !== undefined) {
    params.set('chat_id', String(chatId))
  }

  if (options.beforeMessageId !== undefined) {
    params.set('before_message_id', String(options.beforeMessageId))
  }

  return request<TelegramSampleResponse>(
    `/api/integrations/telegram/sample?${params.toString()}`,
    { signal },
  )
}

export function getTelegramStatus(): Promise<TelegramConnectionStatus> {
  return request<TelegramConnectionStatus>('/api/integrations/telegram')
}

export function connectTelegram(phone: string): Promise<{ status: TelegramConnectionStatus['status'] }> {
  return request('/api/integrations/telegram/connect', { method: 'POST', body: JSON.stringify({ phone }) })
}

export function submitTelegramCode(code: string): Promise<{ status: TelegramConnectionStatus['status'] }> {
  return request('/api/integrations/telegram/code', { method: 'POST', body: JSON.stringify({ code }) })
}

export function submitTelegramPassword(password: string): Promise<{ status: TelegramConnectionStatus['status'] }> {
  return request('/api/integrations/telegram/password', { method: 'POST', body: JSON.stringify({ password }) })
}

export function disconnectTelegram(): Promise<{ message: string }> {
  return request('/api/integrations/telegram', { method: 'DELETE' })
}
