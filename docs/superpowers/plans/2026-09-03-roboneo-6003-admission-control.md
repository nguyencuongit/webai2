# RoboNeo 6003 Admission Control Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make RoboNeo code `6003` internal admission backpressure so customer tasks wait, retry with fresh provider context, and only fail generically after 50 minutes.

**Architecture:** External task creation becomes asynchronous. A cache-backed coordinator leases eligible tokens, serializes `nodeexecute`, applies busy cooldowns, and schedules a fresh provider room/session/upload on every retry while the existing polling and webhook path remains idempotent.

**Tech Stack:** PHP 8.3, Laravel queues/cache locks/HTTP client, Botble repositories, PHPUnit, systemd.

**Spec:** `docs/superpowers/specs/2026-09-03-roboneo-6003-admission-control-design.md`

## Global Constraints

- Keep the z-test-to-Mimix create API response compatible: `success`, `message`, and `task_id` remain unchanged.
- Never log or persist raw access tokens outside their existing encrypted/database field, cookies, or unredacted provider credential payloads.
- The RoboNeo admission deadline is exactly 50 minutes by default.
- A provider task ID is an idempotency boundary: after it exists, never call `nodeexecute` again for that external task.
- Code `6003` must not be delivered to z-test as a terminal webhook error.
- No database migration is required; locks, leases, cooldowns, and last-used timestamps use Laravel cache.
- Preserve the existing callback-pending and credit-refund behavior.

---

### Task 1: Asynchronous External Task Creation

**Files:**
- Create: `platform/plugins/ai-video-generator/src/Jobs/SubmitExternalRoboNeoTask.php`
- Modify: `platform/plugins/ai-video-generator/src/Services/Api/ExternalVideoTaskService.php`
- Test: `tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php`

**Interfaces:**
- Produces: `ExternalVideoTaskService::create(array $payload): string`, which only persists queued state and dispatches `SubmitExternalRoboNeoTask`.
- Produces: `ExternalVideoTaskService::submitPendingRoboNeoTask(Model $task): void`, called by the queue job.
- Consumes: `ExternalVideoTaskInterface::findByTaskId(string $taskId): ?Model`.

- [ ] **Step 1: Write the failing asynchronous-creation test**

```php
public function test_create_returns_immediately_and_dispatches_submission_job(): void
{
    Queue::fake();
    $service = $this->serviceWithTaskRepository($task = new InMemoryExternalVideoTask);

    $taskId = $service->create([
        'url_image' => 'https://z-test.test/image.jpg',
        'url_video' => 'https://z-test.test/video.mp4',
    ]);

    $this->assertSame($task->task_id, $taskId);
    $this->assertSame('queued', data_get($task->payload, 'roboneo.submission.state'));
    $this->assertNotEmpty(data_get($task->payload, 'roboneo.submission.deadline_at'));
    Queue::assertPushed(SubmitExternalRoboNeoTask::class);
}
```

- [ ] **Step 2: Run the focused test and verify it fails**

Run: `php artisan test tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php --filter=create_returns_immediately`

Expected: FAIL because `SubmitExternalRoboNeoTask` and queued-only creation do not exist.

- [ ] **Step 3: Add the queue job and make creation queued-only**

```php
class SubmitExternalRoboNeoTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly string $taskId) {}

    public function handle(ExternalVideoTaskService $service, ExternalVideoTaskInterface $tasks): void
    {
        $task = $tasks->findByTaskId($this->taskId);

        if ($task && ! $service->isTerminal($task)) {
            $service->submitPendingRoboNeoTask($task);
        }
    }
}
```

Set `submitted_at`, `deadline_at = now()->addMinutes(50)`, `attempt = 0`, `state = queued`, and `history = []` in the new task payload, then dispatch the job after persistence.

- [ ] **Step 4: Run the focused test and verify it passes**

Run: `php artisan test tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php --filter=create_returns_immediately`

Expected: PASS.

- [ ] **Step 5: Commit the asynchronous boundary**

```bash
git add platform/plugins/ai-video-generator/src/Jobs/SubmitExternalRoboNeoTask.php platform/plugins/ai-video-generator/src/Services/Api/ExternalVideoTaskService.php tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php
git commit -m "refactor: queue RoboNeo external submissions"
```

### Task 2: Token Eligibility, Leases, and Global Submit Gate

**Files:**
- Create: `platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoAdmissionCoordinator.php`
- Modify: `platform/plugins/ai-video-generator/src/Repositories/Interfaces/AiVideoApiTokenInterface.php`
- Modify: `platform/plugins/ai-video-generator/src/Repositories/Eloquent/AiVideoApiTokenRepository.php`
- Modify: `platform/plugins/ai-video-generator/src/Services/Api/ExternalVideoTaskService.php`
- Modify: `platform/plugins/ai-video-generator/config/general.php`
- Modify: `.env.example`
- Test: `tests/Feature/AiVideoGenerator/RoboNeoAdmissionCoordinatorTest.php`

**Interfaces:**
- Produces: `AiVideoApiTokenInterface::getActiveTokens(): array` returning `list<array{id:int,token_api:string}>`.
- Produces: `RoboNeoAdmissionCoordinator::leaseToken(array $tokens, array $excludedIds = []): ?RoboNeoTokenLease`.
- Produces: `RoboNeoAdmissionCoordinator::acquireSubmitGate(): ?Lock`.
- Produces: `RoboNeoAdmissionCoordinator::cooldownToken(int $tokenId, DateTimeInterface $until): void` and `cooldownGlobal(DateTimeInterface $until): void`.
- Consumes: Laravel cache atomic locks and cache timestamps.

- [ ] **Step 1: Write failing coordinator tests**

```php
public function test_it_does_not_lease_the_same_token_twice(): void
{
    $first = $this->coordinator->leaseToken($this->tokens());
    $second = $this->coordinator->leaseToken($this->tokens());

    $this->assertSame(10, $first?->tokenId);
    $this->assertNotSame($first?->tokenId, $second?->tokenId);
}

public function test_it_skips_tokens_in_cooldown_and_prefers_least_recently_used(): void
{
    $this->coordinator->cooldownToken(9, now()->addMinutes(8));
    $this->coordinator->markTokenUsed(10, now());

    $lease = $this->coordinator->leaseToken($this->tokens());

    $this->assertSame(11, $lease?->tokenId);
}
```

- [ ] **Step 2: Run coordinator tests and verify they fail**

Run: `php artisan test tests/Feature/AiVideoGenerator/RoboNeoAdmissionCoordinatorTest.php`

Expected: FAIL because the coordinator, lease value object, and repository list method do not exist.

- [ ] **Step 3: Implement the cache-backed coordinator**

Use cache keys scoped to the application:

```php
private const TOKEN_LOCK = 'roboneo:admission:token:%d';
private const TOKEN_COOLDOWN = 'roboneo:admission:token:%d:cooldown_until';
private const TOKEN_LAST_USED = 'roboneo:admission:token:%d:last_used';
private const GLOBAL_LOCK = 'roboneo:admission:global-submit';
private const GLOBAL_COOLDOWN = 'roboneo:admission:global-cooldown-until';
```

Sort active tokens by cached last-used timestamp, put excluded token IDs last, skip tokens whose cooldown is in the future, and acquire a non-blocking token lock. Return a lease object that always releases the lock through `release()` and its destructor fallback.

- [ ] **Step 4: Add explicit configuration defaults**

```php
'admission_deadline_minutes' => (int) env('ROBONEO_ADMISSION_DEADLINE_MINUTES', 50),
'token_lease_seconds' => (int) env('ROBONEO_TOKEN_LEASE_SECONDS', 600),
'token_cooldown_min_seconds' => (int) env('ROBONEO_TOKEN_COOLDOWN_MIN_SECONDS', 300),
'token_cooldown_max_seconds' => (int) env('ROBONEO_TOKEN_COOLDOWN_MAX_SECONDS', 600),
'global_cooldown_min_seconds' => (int) env('ROBONEO_GLOBAL_COOLDOWN_MIN_SECONDS', 45),
'global_cooldown_max_seconds' => (int) env('ROBONEO_GLOBAL_COOLDOWN_MAX_SECONDS', 90),
```

- [ ] **Step 5: Run coordinator tests and verify they pass**

Run: `php artisan test tests/Feature/AiVideoGenerator/RoboNeoAdmissionCoordinatorTest.php`

Expected: PASS.

- [ ] **Step 6: Commit admission coordination**

```bash
git add .env.example platform/plugins/ai-video-generator/config/general.php platform/plugins/ai-video-generator/src/Repositories platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoAdmissionCoordinator.php tests/Feature/AiVideoGenerator/RoboNeoAdmissionCoordinatorTest.php
git commit -m "feat: coordinate RoboNeo admission tokens"
```

### Task 3: Fresh Context Retry and Provider-Unavailable Deadline

**Files:**
- Modify: `platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoMotionApi.php`
- Modify: `platform/plugins/ai-video-generator/src/Services/Api/ExternalVideoTaskService.php`
- Modify: `platform/plugins/ai-video-generator/src/Jobs/RetryExternalRoboNeoSubmission.php`
- Test: `tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php`
- Test: `tests/Feature/AiVideoGenerator/ExternalVideoTaskBusyRetryTest.php`

**Interfaces:**
- Produces: `ExternalVideoTaskService::submitPendingRoboNeoTask(Model $task): void` as an idempotent, one-attempt state machine.
- Consumes: `RoboNeoMotionApi::quote(..., settings: ['credentials' => ['gid' => RoboNeoIdentity::gid()]])` to guarantee a new provider context.
- Produces: normalized terminal error `ROBONEO_PROVIDER_UNAVAILABLE` only after `deadline_at`.

- [ ] **Step 1: Write failing state-machine tests**

```php
public function test_6003_keeps_task_processing_and_requeues_with_fresh_context(): void
{
    Queue::fake();
    $first = $this->runBusyAttempt($task);
    $second = $this->runBusyAttempt($task);

    $this->assertSame('PROCESSING', $task->status);
    $this->assertSame('retry_scheduled', data_get($task->payload, 'roboneo.submission.state'));
    $this->assertNotSame($first['gid_hash'], $second['gid_hash']);
    $this->assertNotSame($first['room_hash'], $second['room_hash']);
    $this->assertNotSame($first['trace_hash'], $second['trace_hash']);
    $this->assertArrayNotHasKey('result', $task->payload);
    Queue::assertPushed(SubmitExternalRoboNeoTask::class);
}

public function test_6003_after_deadline_emits_one_normalized_failure(): void
{
    $task = $this->expiredQueuedTask();

    $this->service->submitPendingRoboNeoTask($task);
    $this->service->submitPendingRoboNeoTask($task);

    $this->assertSame('FAILED', $task->status);
    $this->assertSame('ROBONEO_PROVIDER_UNAVAILABLE', data_get($task->payload, 'result.error.code'));
    $this->assertSame(1, $this->callbackCount($task));
}
```

- [ ] **Step 2: Run state-machine tests and verify they fail**

Run: `php artisan test tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php tests/Feature/AiVideoGenerator/ExternalVideoTaskBusyRetryTest.php`

Expected: FAIL because current retries reuse quote state and exhaust after four attempts.

- [ ] **Step 3: Implement one fresh admission attempt**

Within the task lock:

1. Exit when the task is terminal or has `roboneo.task_id`.
2. Fail generically when `deadline_at <= now()`.
3. Honor global cooldown before selecting a token.
4. Lease an eligible token, excluding the last busy token when possible.
5. Ensure local image and trimmed video inputs exist.
6. Quote with a new explicit generated GID, then acquire the global submit lock.
7. Submit once and persist provider task ID/session before dispatching polling.
8. Always release global and token locks in `finally`.

- [ ] **Step 4: Implement the `6003` branch**

On `RoboNeoProtocolException` code `6003`:

```php
$coordinator->cooldownToken($tokenId, $tokenCooldownUntil);
$coordinator->cooldownGlobal($globalCooldownUntil);
$this->appendRedactedSubmissionHistory($task, 'busy', $exception, $attemptContext);
$this->scheduleAdmission($task, $this->nextEligibleAdmissionAt());
```

Do not copy `quotedTask` into the next attempt, do not create a result payload, and do not invoke the webhook before the deadline.

- [ ] **Step 5: Handle invalid credentials and alternate tokens**

Treat `missing_uid`, `missing_access_token`, `http_401_*`, and `http_403_*` as credential errors. Deactivate that token, append a redacted history entry, and queue an immediate attempt with a different active token.

- [ ] **Step 6: Preserve legacy scheduled jobs**

Change `RetryExternalRoboNeoSubmission::handle()` to call the same idempotent `submitPendingRoboNeoTask()` state machine. Keep constructor properties so already serialized jobs can still deserialize after deployment.

- [ ] **Step 7: Run state-machine tests and verify they pass**

Run: `php artisan test tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php tests/Feature/AiVideoGenerator/ExternalVideoTaskBusyRetryTest.php`

Expected: PASS, including no raw `6003` terminal callback.

- [ ] **Step 8: Commit fresh retry behavior**

```bash
git add platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoMotionApi.php platform/plugins/ai-video-generator/src/Jobs platform/plugins/ai-video-generator/src/Services/Api/ExternalVideoTaskService.php tests/Feature/AiVideoGenerator
git commit -m "fix: rebuild RoboNeo context after busy responses"
```

### Task 4: Retry-Safe Inputs, Telemetry, and Idempotency

**Files:**
- Modify: `platform/plugins/ai-video-generator/src/Services/Api/ExternalVideoTaskService.php`
- Modify: `platform/plugins/ai-video-generator/src/Jobs/PollExternalRoboNeoTask.php`
- Test: `tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php`

**Interfaces:**
- Produces: persistent local input paths under `storage/app/ai-video-generator/external-inputs/{task_id}/` while awaiting admission.
- Produces: `cleanupAdmissionInputs(Model $task): void` called after provider acceptance or terminal failure.
- Produces: append-only redacted `payload.roboneo.submission.history` records.

- [ ] **Step 1: Write failing input-lifecycle and idempotency tests**

```php
public function test_local_inputs_survive_busy_retry_and_are_deleted_after_acceptance(): void
{
    $this->runBusyAttempt($task);
    $paths = data_get($task->payload, 'roboneo.local_inputs');
    $this->assertFileExists($paths['image']);
    $this->assertFileExists($paths['video']);

    $this->runAcceptedAttempt($task);
    $this->assertFileDoesNotExist($paths['image']);
    $this->assertFileDoesNotExist($paths['video']);
}

public function test_duplicate_job_does_not_submit_after_provider_task_id_exists(): void
{
    $this->runAcceptedAttempt($task);
    $this->service->submitPendingRoboNeoTask($task);

    $this->roboNeo->expects($this->never())->method('submit');
}
```

- [ ] **Step 2: Run the lifecycle tests and verify they fail**

Run: `php artisan test tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php --filter='local_inputs|duplicate_job'`

Expected: FAIL because source files are currently deleted after every create attempt.

- [ ] **Step 3: Implement deterministic local input retention and cleanup**

Download once into the task-specific directory, reuse local source files for all retries, and create fresh provider uploads through `quote()` on every attempt. Delete the directory only after provider acceptance or a terminal failure.

- [ ] **Step 4: Add redacted history fingerprints**

Persist only SHA-256 prefixes for access token, UID, GID, room, and trace values. Record attempt, status, provider code, selected token ID, cooldown timestamps, and IP family. Remove exception messages when they contain credential-like fields.

- [ ] **Step 5: Run the full RoboNeo test set**

Run: `php artisan test tests/Feature/AiVideoGenerator/RoboNeoApiClientTest.php tests/Feature/AiVideoGenerator/ExternalVideoTaskBusyRetryTest.php tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php tests/Feature/AiVideoGenerator/RoboNeoAdmissionCoordinatorTest.php tests/Unit/AiVideoGenerator/ExternalVideoTaskRepositoryTest.php`

Expected: PASS.

- [ ] **Step 6: Run style checks**

Run: `vendor/bin/pint --test platform/plugins/ai-video-generator/src tests/Feature/AiVideoGenerator tests/Unit/AiVideoGenerator`

Expected: PASS with no changed files.

- [ ] **Step 7: Commit lifecycle hardening**

```bash
git add platform/plugins/ai-video-generator/src tests/Feature/AiVideoGenerator
git commit -m "fix: harden RoboNeo admission lifecycle"
```

### Task 5: Deploy Mimix and Verify Live Behavior

**Files:**
- Deploy source: `/Users/apple/Desktop/src_duong/webai2`
- Deploy destination: `root@76.13.180.224:/var/www/mimix`

**Interfaces:**
- Consumes: existing z-test external create request and callback endpoint.
- Produces: live queue-driven provider admission and monitored outcome.

- [ ] **Step 1: Run final local verification**

Run: `php artisan test tests/Feature/AiVideoGenerator tests/Unit/AiVideoGenerator`

Expected: all tests PASS.

- [ ] **Step 2: Deploy only reviewed application files**

Use `rsync` over `/Users/apple/.ssh/duong_ssh` to `/var/www/mimix`, excluding `.env`, `.git`, `storage`, dependency directories, and unrelated user files. Compare SHA-256 hashes for every deployed file.

- [ ] **Step 3: Rebuild application state and restart services**

Run remotely with `/usr/bin/php8.3`:

```bash
/usr/bin/php8.3 artisan optimize:clear
/usr/bin/php8.3 artisan config:cache
/usr/bin/php8.3 artisan route:cache
/usr/bin/php8.3 artisan view:cache
systemctl restart mimix-default-worker.service php8.3-fpm.service
```

Expected: both services are active and Laravel commands exit zero.

- [ ] **Step 4: Verify API compatibility and worker health**

Send an unauthenticated/invalid-body health probe that cannot consume provider credits and confirm the expected validation/auth response. Inspect service status and recent logs for boot exceptions.

- [ ] **Step 5: Submit controlled live tasks from z-test**

Create one RoboNeo task through the normal z-test application flow, then create two closely spaced tasks. Record only z-test task IDs, Mimix external task UUIDs, provider task IDs, status timestamps, and redacted fingerprints.

- [ ] **Step 6: Monitor through terminal outcome**

Confirm one of these valid outcomes for each task:

- provider acceptance followed by completed video and exactly one success callback; or
- internal waiting/cooldown without raw `6003`, followed at the 50-minute deadline by exactly one `ROBONEO_PROVIDER_UNAVAILABLE` callback and normal z-test credit refund.

- [ ] **Step 7: Report evidence**

Report deployed commit/file hashes, test counts, service state, task timelines, whether token/context rotation occurred, webhook count, and credit outcome. Explicitly separate client-side issues eliminated from any remaining upstream provider refusal.
