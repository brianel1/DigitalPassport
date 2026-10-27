export interface HealthResponse {
  status: string
  service: string
}

export async function getApiHealth(): Promise<HealthResponse> {
  const response = await fetch('/api/health')

  if (!response.ok) {
    throw new Error(`API request failed with status ${response.status}`)
  }

  return (await response.json()) as HealthResponse
}
