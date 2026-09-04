# Unified RoboNeo Task Pipeline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Route both external API tasks and Mimix `/video-lab` customer tasks through one resilient RoboNeo admission, polling, storage, and token lifecycle.

**Architecture:** Extract provider orchestration from `ExternalVideoTaskService` into a source-neutral `RoboNeoTaskPipelineService`. External and customer source adapters retain persistence, callbacks, and Mimix-only billing while legacy job classes become compatibility entry points into the same pipeline.

**Tech Stack:** PHP 8.3, Laravel queues/cache locks/Eloquent, Botble CMS repositories, PHPUnit, Cloudflare R2.

**Spec:** `docs/superpowers/specs/2026-09-04-unified-roboneo-task-pipeline-design.md`

## Global Constraints

- Mimix `/video-lab` debits and refunds only the Mimix customer wallet.
- External tasks never debit or refund Mimix credits; z-test owns their billing.
- Admission retries last up to 50 minutes and hide raw `6003` as a terminal customer error.
- Polling uses the exact token recorded at provider acceptance.
- Success and provider-terminal failure deactivate the exact submitting token.
- Existing task rows and queued job payloads remain compatible without a migration.
- Deployment targets Mimix only and Laravel cache commands run as `www-data`.

---

### Task 1: Define source-neutral task lifecycle boundaries

**Files:**
- Create: `platform/plugins/ai-video-generator/src/Services/RoboNeo/Contracts/RoboNeoTaskSource.php`
- Create: `platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoTaskPipelineService.php`
- Create: `tests/Feature/AiVideoGenerator/RoboNeoTaskPipelineTest.php`

**Interfaces:**
- Produces: source methods `key`, `find`, `prepareInputs`, `cleanupInputs`, `dispatchSubmission`, `dispatchPolling`, `complete`, `fail`, `resumePendingCompletion`, and `isTerminal`.
- Produces: pipeline methods `submit(RoboNeoTaskSource, string): void`, `poll(RoboNeoTaskSource, string): void`, and `markPollingTimeout(RoboNeoTaskSource, string): void`.

- [ ] **Step 1: Write the failing pipeline tests**

```php
$pipeline->submit($source, 'task-1');
$this->assertSame('submitted', data_get($task->payload, 'roboneo.submission.state'));
$pipeline->poll($source, 'task-1');
$source->assertCompletedOnce();
$tokens->assertDeactivated(10);
```

- [ ] **Step 2: Run the focused test and verify missing contract/pipeline failures**

Run: `/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/AiVideoGenerator/RoboNeoTaskPipelineTest.php`

- [ ] **Step 3: Add the source contract**

```php
interface RoboNeoTaskSource
{
    public function key(): string;
    public function find(string $taskId): ?Model;
    public function prepareInputs(Model $task): array;
    public function cleanupInputs(Model $task): void;
    public function dispatchSubmission(string $taskId, ?Carbon $at = null): void;
    public function dispatchPolling(string $taskId, int $delaySeconds): void;
    public function complete(Model $task, array $storedVideo): void;
    public function fail(Model $task, string $code, string $message): void;
    public function resumePendingCompletion(Model $task): bool;
    public function isTerminal(Model $task): bool;
}
```

- [ ] **Step 4: Move locking, admission, retry classification, polling, R2 storage, and exact-token deactivation into the pipeline**

The lock key includes source and task ID. Delayed work goes through source dispatch methods. Provider completion persists/deactivates the exact token before source completion.

- [ ] **Step 5: Run the focused test and commit**

```bash
/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/AiVideoGenerator/RoboNeoTaskPipelineTest.php
git add platform/plugins/ai-video-generator/src/Services/RoboNeo tests/Feature/AiVideoGenerator/RoboNeoTaskPipelineTest.php
git commit -m "refactor: centralize RoboNeo task lifecycle"
```

### Task 2: Adapt external API tasks to the common pipeline

**Files:**
- Create: `platform/plugins/ai-video-generator/src/Services/RoboNeo/Sources/ExternalRoboNeoTaskSource.php`
- Modify: `platform/plugins/ai-video-generator/src/Services/Api/ExternalVideoTaskService.php`
- Modify: `platform/plugins/ai-video-generator/src/Jobs/SubmitExternalRoboNeoTask.php`
- Modify: `platform/plugins/ai-video-generator/src/Jobs/PollExternalRoboNeoTask.php`
- Modify: `platform/plugins/ai-video-generator/src/Jobs/RetryExternalRoboNeoSubmission.php`
- Modify: `tests/Feature/AiVideoGenerator/ExternalVideoTaskAdmissionTest.php`
- Modify: `tests/Feature/AiVideoGenerator/ExternalVideoTaskPollingTest.php`

**Interfaces:**
- Consumes: the source contract and pipeline from Task 1.
- Produces: the unchanged external API response and callback contracts.

- [ ] **Step 1: Change external tests to require pipeline delegation and token deactivation after success**
- [ ] **Step 2: Run external tests and verify the new assertions fail**
- [ ] **Step 3: Implement the external adapter for remote inputs, retained retry files, and normalized callbacks without customer billing**
- [ ] **Step 4: Convert external jobs and `ExternalVideoTaskService` provider methods to pipeline delegates**

```php
public function handle(RoboNeoTaskPipelineService $pipeline, ExternalRoboNeoTaskSource $source): void
{
    $pipeline->submit($source, $this->taskId);
}
```

- [ ] **Step 5: Run pipeline/external tests and commit `refactor: route external tasks through RoboNeo pipeline`**

### Task 3: Queue Mimix customer tasks through the common pipeline

**Files:**
- Create: `platform/plugins/ai-video-generator/src/Services/RoboNeo/Sources/CustomerRoboNeoTaskSource.php`
- Create: `platform/plugins/ai-video-generator/src/Jobs/SubmitCustomerRoboNeoTask.php`
- Modify: `platform/plugins/ai-video-generator/src/Services/AiGenerationService.php`
- Modify: `platform/plugins/ai-video-generator/src/Services/AiGenerationTaskStatusService.php`
- Modify: `platform/plugins/ai-video-generator/src/Jobs/PollRoboNeoTask.php`
- Modify: `platform/plugins/ai-video-generator/src/Providers/AiVideoGeneratorServiceProvider.php`
- Create: `tests/Feature/AiVideoGenerator/MimixRoboNeoPipelineTest.php`
- Modify: `tests/Feature/AiVideoGenerator/RunningHubVideoLabUiTest.php`

**Interfaces:**
- Consumes: the common pipeline.
- Produces: immediate customer tasks containing `roboneo.source=customer`, queued submission state, and an idempotent Mimix billing snapshot.

- [ ] **Step 1: Write failing tests for immediate enqueue, Mimix-only debit, retry retention, completion, exact-token deactivation, and refund-once behavior**

```php
$response = $service->create('roboneo', $payload);
$this->assertSame('PROCESSING', data_get($response, 'data.status'));
Queue::assertPushed(SubmitCustomerRoboNeoTask::class);
$this->assertSame($before - $price, $customer->fresh()->credits_balance);
```

- [ ] **Step 2: Run the new customer tests and verify submit is still synchronous**
- [ ] **Step 3: Persist the customer task and billing snapshot before dispatching `SubmitCustomerRoboNeoTask`**
- [ ] **Step 4: Implement the customer adapter using local public uploads, structured R2 output, and idempotent Mimix refunds**
- [ ] **Step 5: Make new and legacy customer jobs call the common pipeline**
- [ ] **Step 6: Run customer/UI/pipeline/external tests and commit `feat: unify Mimix video tasks with RoboNeo pipeline`**

### Task 4: Verify compatibility and deploy Mimix

**Files:**
- Modify only files already listed if verification exposes a regression.

**Interfaces:**
- Consumes: the unified pipeline and both source adapters.
- Produces: deployed Mimix code and a verified live customer task.

- [ ] **Step 1: Run all affected RoboNeo and video-lab tests plus Pint**

```bash
/usr/local/opt/php@8.3/bin/php artisan test tests/Feature/AiVideoGenerator
vendor/bin/pint --test platform/plugins/ai-video-generator/src tests/Feature/AiVideoGenerator
```

- [ ] **Step 2: Deploy committed plugin changes to `/var/www/mimix` without replacing `.env`, storage, or database**
- [ ] **Step 3: Rebuild caches as `www-data` and restart `mimix-default-worker.service` plus `php8.3-fpm.service`**

```bash
runuser -u www-data -- /usr/bin/php8.3 /var/www/mimix/artisan optimize:clear
runuser -u www-data -- /usr/bin/php8.3 /var/www/mimix/artisan config:cache
runuser -u www-data -- /usr/bin/php8.3 /var/www/mimix/artisan route:cache
runuser -u www-data -- /usr/bin/php8.3 /var/www/mimix/artisan view:cache
```

- [ ] **Step 4: Create one real Mimix customer task through the application service used by `/video-lab`**
- [ ] **Step 5: Verify completion, generated R2 media, one credit debit, submitting token inactive, service health, and zero rsync content differences**
- [ ] **Step 6: Commit any verification-driven correction and report the evidence**
