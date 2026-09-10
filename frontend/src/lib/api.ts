export type HealthResponse = {
  status: string
  service: string
}

export async function getHealth(): Promise<HealthResponse> {
  const response = await fetch('/api/health')

  if (!response.ok) {
    throw new Error('The backend returned an unsuccessful response.')
  }

  return (await response.json()) as HealthResponse
}