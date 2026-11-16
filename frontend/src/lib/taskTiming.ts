// Keep a completed result readable; this is not simulated analysis.
// Call AFTER the request succeeds and the status explicitly switches to "ready".
export async function presentTaskResult(started: number, minimum = 2800) {
  const remaining = Math.max(450, minimum - (performance.now() - started))
  await new Promise<void>(resolve => window.setTimeout(resolve, remaining))
}
