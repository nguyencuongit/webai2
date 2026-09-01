<?php

namespace Botble\AiVideoGenerator\Enums;

enum AiVideoModelEndpointTag: string
{
    case Popular = 'popular';
    case Economy = 'economy';
    case HighQuality = 'high_quality';
    case New = 'new';

    public function label(): string
    {
        return match ($this) {
            self::Popular => 'Phổ biến',
            self::Economy => 'Tiết kiệm',
            self::HighQuality => 'Chất lượng cao',
            self::New => 'Mới',
        };
    }

    public static function choices(): array
    {
        return array_column(
            array_map(fn (self $tag) => ['value' => $tag->value, 'label' => $tag->label()], self::cases()),
            'label',
            'value'
        );
    }
}
