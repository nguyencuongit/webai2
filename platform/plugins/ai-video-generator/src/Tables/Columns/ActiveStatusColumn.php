<?php

namespace Botble\AiVideoGenerator\Tables\Columns;

use Botble\Table\Columns\FormattedColumn;

class ActiveStatusColumn extends FormattedColumn
{
    public static function make(array|string $data = [], string $name = ''): static
    {
        return parent::make($data ?: 'status', $name)
            ->title(trans('core/base::tables.status'))
            ->alignCenter()
            ->width(120)
            ->renderUsing(function (FormattedColumn $column): string {
                $isActive = self::isActive($column->getItem()->status);

                return sprintf(
                    '<span class="badge %s">%s</span>',
                    $isActive ? 'bg-success text-bg-success' : 'bg-secondary text-bg-secondary',
                    $isActive ? 'Hoạt động' : 'Đóng'
                );
            });
    }

    protected static function isActive(mixed $status): bool
    {
        return in_array($status, [true, 1, '1', 'active', 'activated', 'published'], true);
    }
}
