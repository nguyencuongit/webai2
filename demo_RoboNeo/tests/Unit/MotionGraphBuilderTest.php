<?php

namespace Tests\Unit;

use App\Services\RoboNeo\MotionGraphBuilder;
use Tests\TestCase;

class MotionGraphBuilderTest extends TestCase
{
    public function test_it_builds_the_vibeforge_motion_graph_shape(): void
    {
        $result = app(MotionGraphBuilder::class)->build(
            'Preserve identity',
            ['url' => 'https://assets.test/image.jpg', 'asset_id' => 'image-1', 'ext' => 'jpeg', 'name' => 'character'],
            ['url' => 'https://assets.test/motion.mp4', 'asset_id' => 'video-1', 'ext' => 'mp4', 'name' => 'motion'],
        );

        $this->assertCount(4, $result['graph']['nodes']);
        $this->assertCount(3, $result['graph']['edges']);
        $this->assertSame('video_bonbon_motioncontrol_v26', $result['graph']['nodes'][3]['data']['apiName']);
        $this->assertSame('93', $result['graph']['nodes'][3]['data']['mcpCategoriesId']);
        $this->assertSame($result['motion_node_id'], $result['graph']['nodes'][3]['id']);
        $this->assertSame(
            "port-input-{$result['motion_node_id']}-VIDEO-2-0",
            $result['graph']['edges'][2]['targetPortID'],
        );
    }
}
