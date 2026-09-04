# RoboNeo 6003 Admission-Control Design

## Objective

Prevent RoboNeo protocol error `6003` from becoming an immediate customer-facing task failure. Remove client-side causes that can trigger it, preserve task and webhook correctness, and keep retry behavior bounded by the existing 50-minute processing deadline.

The upstream service is external and undocumented, so this design cannot guarantee that RoboNeo never emits `6003`. It guarantees that our application treats the response as admission backpressure, does not repeatedly submit the same rejected context, and only exposes a generic provider-unavailable failure after the deadline.

## Confirmed Findings

- RoboNeo returns `6003` during `nodeexecute`, before a provider task ID exists.
- The same submissions fail over both IPv4 and IPv6, ruling out IP family as the sole cause.
- Concurrent external requests currently select the same latest active token and may call `nodeexecute` at the same time.
- One configured static `ROBONEO_GID` is shared across all token accounts.
- A token whose session is expired can remain marked active.
- Existing retries reuse the rejected room, session, trace ID, seed, and uploaded asset context.
- Media preparation and the initial submit run synchronously inside the API request, so the queue worker does not serialize initial submissions.
- Two observed tasks each quoted 72 provider coins while sharing one usable token. A successful quote does not reserve capacity for a later `nodeexecute` call.

## Chosen Architecture

### Asynchronous admission

The API controller creates an `external_video_tasks` record and returns its public task UUID immediately. It dispatches a dedicated submission job instead of downloading, trimming, uploading, quoting, and executing inside PHP-FPM.

The external API contract remains compatible: callers receive a task ID and continue waiting for the existing webhook. The task stays `PROCESSING` while waiting for provider admission.

### Token eligibility and leases

A token is eligible only when it is active, not leased by another submission, and not in cooldown. Submission jobs acquire a per-token distributed lock before preparation and hold it through `nodeexecute`. No two tasks may execute against the same token concurrently.

Credential-validation failures deactivate the affected token. Busy responses place the token in a configurable cooldown instead of marking it permanently invalid. Selection prefers the least recently used eligible token rather than always selecting the greatest database ID.

Locks and cooldowns use the shared Laravel cache so no schema migration is required. Lock ownership has a TTL and is always released in `finally` blocks.

### Fresh context on every admission attempt

Every retry after `6003` performs a fresh preparation cycle with:

- a new generated GID rather than the globally configured static GID;
- a new RoboNeo room and session;
- new trace ID and seed;
- fresh provider uploads and quote;
- an eligible token, rotating away from the last busy token when another is available.

The submitted task retains the exact token and session used for polling. A successful provider task is never resubmitted.

### Global throttling and retry deadline

All `nodeexecute` calls also pass through a short global submit lock to remove burst submissions from separate accounts. A `6003` response creates a global admission cooldown with randomized jitter and requeues the task.

Retry continues only while the task is younger than 50 minutes. Delays increase from short to longer waits and include jitter to avoid synchronized retries. `6003` is recorded internally but never sent as the webhook failure code.

At the deadline, the existing failure webhook is delivered exactly once using the normalized code `ROBONEO_PROVIDER_UNAVAILABLE`. Z-test then follows its normal refund path. Non-retryable credential, media, protocol, or validation failures keep their specific normalized outcomes.

### Retry-safe source media

The submission job downloads input media before the caller's signed URLs expire and retains local source copies until the task is accepted or terminal. A retry reuses these local sources for trimming and creates fresh RoboNeo uploads. Temporary files are removed after provider acceptance or terminal failure.

If initial input download fails, the job follows the existing retry/failure rules for media errors; this is distinct from `6003` admission retry.

### Telemetry and redaction

The task payload records an append-only submission history containing:

- attempt number and timestamps;
- token database ID and one-way token/UID/GID fingerprints;
- lock wait, cooldown, IP family, room/trace fingerprints;
- provider code and normalized outcome;
- next retry time and final deadline.

Raw tokens, cookies, UIDs, provider payloads containing credentials, and input media contents are not written to logs.

## Components

- `SubmitExternalRoboNeoTask`: owns asynchronous preparation and first/retry admission.
- `ExternalVideoTaskService`: creates records, performs one idempotent admission attempt, schedules retries, polls accepted tasks, and delivers webhooks.
- `AiVideoApiTokenRepository`: selects eligible tokens and deactivates invalid credentials.
- `RoboNeoMotionApi`: accepts explicit per-attempt identity and creates fresh quote/submit state.
- Cache-backed admission coordinator: manages global submit lock, per-token lease, token cooldown, and global cooldown.
- Existing polling job: unchanged in responsibility, but begins only after a provider task ID is accepted.

## Idempotency and Recovery

- Submission jobs lock the external task before state transitions.
- A task with a provider task ID or submitted state exits without calling `nodeexecute` again.
- Duplicate queue deliveries are safe.
- Webhook delivery remains governed by the existing callback-pending/idempotency mechanism.
- Expired locks recover automatically through TTL.
- Worker restart leaves the database retry state intact; a scheduled recovery command can redispatch processing tasks whose next retry time has elapsed and which have no live job lock.

## Configuration Defaults

- Admission deadline: 50 minutes.
- Per-token lease TTL: long enough for preparation and submit, with automatic expiry.
- Busy token cooldown: randomized between 5 and 10 minutes.
- Global busy cooldown: shorter randomized delay to stop bursts across tokens.
- Submit concurrency: one per token and one `nodeexecute` call at a time globally.
- Raw `ROBONEO_GID`: retained for backwards compatibility elsewhere but ignored for this external admission flow.

## Test Strategy

Tests are written before implementation and cover:

1. API creation returns immediately and dispatches a submission job.
2. Duplicate delivery never submits an already accepted task.
3. Two tasks cannot lease the same token concurrently.
4. Token selection skips cooldown, leased, inactive, and expired tokens.
5. `6003` creates cooldown and a retry with a fresh GID, room, trace, seed, and provider upload context.
6. An alternative token is preferred after a busy response.
7. A task remains processing and emits no failure webhook before its deadline.
8. Deadline exhaustion emits one `ROBONEO_PROVIDER_UNAVAILABLE` webhook.
9. Credential errors deactivate only the invalid token.
10. Accepted tasks keep their assigned token/session for polling and are never resubmitted.
11. Source files survive retry and are removed on terminal completion.
12. Logs and task telemetry contain fingerprints but no raw credentials.

After automated tests, deploy to Mimix, rebuild Laravel caches, restart the queue worker and PHP-FPM, submit controlled tasks from z-test, and monitor the entire lifecycle through provider acceptance or the configured deadline.

## Rollout and Success Criteria

1. Deploy with conservative one-at-a-time global admission.
2. Confirm workers and health endpoints.
3. Submit one controlled RoboNeo task, then two closely spaced tasks.
4. Confirm no raw `6003` failure reaches z-test.
5. Confirm successful tasks complete and deliver one callback.
6. If RoboNeo remains unavailable for 50 minutes, confirm a single generic failure callback and credit refund.

The rollout is successful when application-caused concurrent or stale-context submissions are eliminated, tasks wait and rotate safely on `6003`, provider-accepted tasks complete normally, and all terminal callbacks/refunds remain exactly-once.
