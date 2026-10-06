<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkspaceRole: string implements HasLabel
{
    case Owner = 'owner';
    case Member = 'member';
    case Viewer = 'viewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'เจ้าของ',
            self::Member => 'สมาชิก',
            self::Viewer => 'ดูอย่างเดียว',
        };
    }
}
