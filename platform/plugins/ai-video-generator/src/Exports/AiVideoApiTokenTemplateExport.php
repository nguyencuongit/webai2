<?php

namespace Botble\AiVideoGenerator\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AiVideoApiTokenTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['name', 'token_api'];
    }

    public function array(): array
    {
        return [
            ['Tài khoản RoboNeo 1', 'DAN_TOKEN_API_VAO_DAY'],
        ];
    }
}
