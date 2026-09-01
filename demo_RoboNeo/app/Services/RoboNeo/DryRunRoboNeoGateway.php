<?php

namespace App\Services\RoboNeo;

use App\Models\MotionJob;
use App\Services\RoboNeo\Contracts\RoboNeoGateway;
use Illuminate\Support\Str;

class DryRunRoboNeoGateway implements RoboNeoGateway
{
    public function quote(MotionJob $job): array
    {
        $seconds = max(5, round($job->duration_seconds));
        $cost = (int) ceil($seconds * 50 / 7);

        return [
            'room_id' => 'dry_room_'.Str::lower(Str::random(12)),
            'motion_node_id' => RoboNeoIdentity::nodeId(),
            'quoted_cost' => $cost,
            'image_asset' => [
                'url' => 'dry://image/'.$job->id,
                'asset_id' => 'dry_image_'.$job->id,
                'name' => pathinfo($job->image_original_name, PATHINFO_FILENAME),
                'ext' => strtolower(pathinfo($job->image_original_name, PATHINFO_EXTENSION)),
            ],
            'video_asset' => [
                'url' => 'dry://video/'.$job->id,
                'asset_id' => 'dry_video_'.$job->id,
                'name' => pathinfo($job->video_original_name, PATHINFO_FILENAME),
                'ext' => strtolower(pathinfo($job->video_original_name, PATHINFO_EXTENSION)),
            ],
            'session_data' => ['dry_run' => true],
            'raw_status' => ['mode' => 'dry-run', 'phase' => 'quoted'],
        ];
    }

    public function submit(MotionJob $job): array
    {
        return [
            'task_id' => 'dry_task_'.Str::lower(Str::random(12)),
            'session_data' => $job->session_data,
            'raw_status' => ['mode' => 'dry-run', 'phase' => 'submitted'],
        ];
    }

    public function poll(MotionJob $job): array
    {
        return [
            'state' => 'completed',
            'result_url' => null,
            'cover_url' => null,
            'session_data' => $job->session_data,
            'raw' => [
                'mode' => 'dry-run',
                'status' => 'SUCCESS',
                'message' => 'No paid RoboNeo request was sent.',
            ],
        ];
    }
}
