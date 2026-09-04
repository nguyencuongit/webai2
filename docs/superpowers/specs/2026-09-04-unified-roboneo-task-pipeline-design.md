# Unified RoboNeo Task Pipeline Design

## Goal

Make RoboNeo tasks created from the Mimix `/video-lab` UI use the same resilient provider lifecycle as tasks received from z-test through the external API. Provider behavior must have one implementation so admission, retries, polling, output storage, and token lifecycle cannot drift again.

## Scope

- Unify RoboNeo provider orchestration for external API tasks and Mimix customer tasks.
- Keep each source's persistence, billing, and completion responsibilities separate.
- Return a Mimix customer task immediately after it is queued instead of waiting for provider admission.
- Deactivate the exact API token that submitted a task after every terminal provider result, including success.
- Preserve compatibility with existing records and queued job classes without a database migration.

## Architecture

Introduce a source-neutral `RoboNeoTaskPipelineService` as the only implementation of the provider lifecycle. It owns:

- per-task locks;
- least-recently-used token selection and token leases;
- global submit serialization;
- fresh GID, room, trace, seed, session, and uploads for each admission attempt;
- IPv4/IPv6 alternation;
- retry and cooldown handling for `6003`, HTTP 408/425/429/5xx, and transient network failures;
- the 50-minute admission deadline;
- polling with the exact token that submitted the provider task;
- provider result download and R2 storage;
- redacted admission telemetry; and
- exact-token deactivation after terminal provider completion or failure.

The pipeline accepts a source adapter identified by source type and source task ID. The adapter exposes the task's provider state and input media, then receives accepted, successful, or failed lifecycle events.

Two adapters retain source-specific behavior:

1. `ExternalRoboNeoTaskAdapter`
   - Persists state in `ai_video_external_tasks`.
   - Resolves remote input URLs and retains local copies during admission retries.
   - Sends a success or error webhook to z-test.
   - Never debits or refunds Mimix customer credits.

2. `CustomerRoboNeoTaskAdapter`
   - Persists state in `ai_video_tasks`.
   - Resolves Mimix media uploads from the public disk.
   - Writes the generated R2 media into the existing customer task format.
   - Refunds the Mimix customer exactly once when admission reaches its deadline or the provider ends unsuccessfully.

`ExternalVideoTaskService` and `AiGenerationService` become source entry-point facades. They create the appropriate record and dispatch the shared pipeline job; neither implements provider submit or polling logic.

## Data Flow

### External API task

1. Validate `url_image` and `url_video`.
2. Create an external task in `PROCESSING/queued` state.
3. Return the external task ID immediately.
4. Run the shared pipeline asynchronously.
5. Store a successful output on R2 and send the webhook to z-test.
6. On terminal failure, send one normalized failure webhook without exposing a raw `6003` as the final reason.

### Mimix `/video-lab` task

1. Validate the active RoboNeo endpoint and local media uploads.
2. Debit the configured credits from the authenticated Mimix customer.
3. Create an `AiGenerationTask` in `PROCESSING/queued` state with an idempotent billing snapshot.
4. Return its task ID and current Mimix credit balance immediately.
5. Run the shared pipeline asynchronously.
6. On success, save the generated R2 media and thumbnail in the existing task response shape.
7. On terminal failure, mark the task failed and refund the recorded Mimix debit exactly once.

## Token Lifecycle

- A token is leased exclusively during admission and recorded on the task after provider acceptance.
- Polling always loads that recorded token; it never substitutes the latest active token.
- Credential-invalid tokens are deactivated during admission and another token is attempted.
- When a provider task reaches either success or failure, its recorded token is deactivated.
- Deactivation happens before source-specific completion work so a failed callback cannot leave a consumed token eligible.

## Compatibility

- Existing external task jobs delegate to the unified pipeline.
- Existing customer polling jobs delegate to the unified pipeline for previously submitted customer tasks.
- Existing task payloads are normalized lazily when first handled.
- No database migration is required; source, pipeline, billing, and result metadata remain in existing JSON payload columns.

## Error Handling and Idempotency

- Duplicate queue jobs are blocked by per-task locks and terminal-state checks.
- Provider acceptance is persisted before polling is scheduled.
- Completion, token deactivation, callbacks, and refunds are idempotent.
- Local admission inputs are retained while retrying and removed after provider acceptance or terminal failure.
- External callbacks retry independently without re-submitting the provider task.
- A customer output-processing failure keeps enough state to retry finalization without creating a second provider task.

## Testing

Tests must prove:

- both task sources dispatch the same pipeline service;
- Mimix `/video-lab` returns immediately after enqueue and debits only the Mimix customer wallet;
- external tasks never change Mimix credits;
- `6003` and transient gateway failures retain both source types until the deadline;
- retries use fresh identity/session values and can rotate tokens;
- polling uses the exact submitting token;
- success deactivates that token for both task sources;
- customer failure refunds once and external failure invokes one webhook;
- duplicate jobs cannot submit, complete, deactivate, callback, or refund twice; and
- existing legacy job payloads remain processable.

## Deployment

Deploy only to Mimix after the focused and affected plugin tests pass. Rebuild Laravel caches as `www-data`, restart the Mimix queue worker and PHP-FPM, verify file parity and service health, then create one real Mimix `/video-lab` task and follow it through admission, provider completion, R2 storage, customer task completion, credit debit, and token deactivation.
