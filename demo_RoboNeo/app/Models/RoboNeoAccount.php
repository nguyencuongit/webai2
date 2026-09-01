<?php

namespace App\Models;

use Database\Factories\RoboNeoAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'label',
    'access_token',
    'uid',
    'is_default',
    'is_active',
    'last_verified_at',
    'last_error',
])]
class RoboNeoAccount extends Model
{
    /** @use HasFactory<RoboNeoAccountFactory> */
    use HasFactory, HasUlids;

    protected $hidden = [
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_verified_at' => 'datetime',
        ];
    }

    public function motionJobs(): HasMany
    {
        return $this->hasMany(MotionJob::class, 'roboneo_account_id');
    }

    public function makeDefault(): void
    {
        DB::transaction(function (): void {
            self::query()->whereKeyNot($this->getKey())->update(['is_default' => false]);
            $this->update(['is_default' => true, 'is_active' => true]);
        });
    }
}
