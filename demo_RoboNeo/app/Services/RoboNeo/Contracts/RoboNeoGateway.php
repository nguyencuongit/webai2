<?php

namespace App\Services\RoboNeo\Contracts;

use App\Models\MotionJob;

interface RoboNeoGateway
{
    public function quote(MotionJob $job): array;

    public function submit(MotionJob $job): array;

    public function poll(MotionJob $job): array;
}
