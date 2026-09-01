<?php

namespace App\Services\RoboNeo;

class MotionGraphBuilder
{
    public function build(string $prompt, array $image, array $video): array
    {
        $textNodeId = RoboNeoIdentity::nodeId();
        $imageNodeId = RoboNeoIdentity::nodeId();
        $videoNodeId = RoboNeoIdentity::nodeId();
        $motionNodeId = RoboNeoIdentity::nodeId();

        $graph = [
            'nodes' => [
                [
                    'id' => $textNodeId,
                    'type' => 'TEXT_NODE',
                    'meta' => ['position' => ['x' => -1166, 'y' => -2858]],
                    'data' => [
                        'size' => ['width' => 260, 'height' => 160],
                        'textList' => [['value' => $prompt]],
                    ],
                ],
                [
                    'id' => $imageNodeId,
                    'type' => 'IMAGE_NODE',
                    'meta' => ['position' => ['x' => -1135, 'y' => -2645]],
                    'data' => [
                        'imageList' => [[
                            'originUrl' => $image['url'],
                            'url' => $image['url'],
                            'uri' => $image['url'],
                            'suffix' => $image['ext'],
                            'name' => $image['name'],
                        ]],
                        'assetId' => $image['asset_id'],
                    ],
                ],
                [
                    'id' => $videoNodeId,
                    'type' => 'VIDEO_NODE',
                    'meta' => ['position' => ['x' => -1142, 'y' => -2252]],
                    'data' => [
                        'videoList' => [[
                            'originUrl' => $video['url'],
                            'url' => $video['url'],
                            'uri' => $video['url'],
                            'suffix' => $video['ext'],
                            'name' => $video['name'],
                            'type' => 'video',
                            'coverUrl' => $video['thumbnail_url'] ?? $video['url'].'&vframe/jpg/offset/0',
                        ]],
                        'assetId' => $video['asset_id'],
                    ],
                ],
                [
                    'id' => $motionNodeId,
                    'type' => 'VIDEO_EDIT_NODE',
                    'meta' => ['position' => ['x' => -686, 'y' => -2538]],
                    'data' => [
                        'mcpCategoriesId' => config('roboneo.motion.tree_id'),
                        'apiName' => config('roboneo.motion.api_name'),
                        'parameters' => (object) [],
                        'unfinishTaskList' => [],
                        'childrenNodeList' => [],
                    ],
                ],
            ],
            'edges' => [
                $this->edge($textNodeId, $motionNodeId, 'TEXT', 0),
                $this->edge($imageNodeId, $motionNodeId, 'IMAGE', 1),
                $this->edge($videoNodeId, $motionNodeId, 'VIDEO', 2),
            ],
        ];

        return ['graph' => $graph, 'motion_node_id' => $motionNodeId];
    }

    private function edge(string $source, string $target, string $type, int $index): array
    {
        return [
            'sourceNodeID' => $source,
            'targetNodeID' => $target,
            'sourcePortID' => "port-output-{$type}-{$source}",
            'targetPortID' => "port-input-{$target}-{$type}-{$index}-0",
        ];
    }
}
