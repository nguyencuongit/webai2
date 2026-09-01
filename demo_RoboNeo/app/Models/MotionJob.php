<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'roboneo_account_id',
    'status',
    'prompt',
    'quality',
    'duration_seconds',
    'quoted_cost',
    'image_path',
    'image_original_name',
    'video_path',
    'video_original_name',
    'room_id',
    'task_id',
    'motion_node_id',
    'image_asset',
    'video_asset',
    'session_data',
    'result_url',
    'result_cover_url',
    'error_code',
    'error_message',
    'raw_status',
    'poll_attempts',
    'dry_run',
    'quoted_at',
    'confirmed_at',
    'submitted_at',
    'completed_at',
])]
class MotionJob extends Model
{
    use HasUlids;

    public const STATUS_UPLOADING = 'uploading';

    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'image_asset' => 'array',
            'video_asset' => 'array',
            'session_data' => 'encrypted:array',
            'raw_status' => 'array',
            'dry_run' => 'boolean',
            'quoted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function canConfirm(): bool
    {
        return $this->status === self::STATUS_AWAITING_CONFIRMATION && $this->task_id === null;
    }

    public function roboneoAccount(): BelongsTo
    {
        return $this->belongsTo(RoboNeoAccount::class, 'roboneo_account_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ], true);
    }
}
