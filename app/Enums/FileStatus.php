<?php

namespace App\Enums;

enum FileStatus: string
{
    case Uploaded = 'uploaded';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Uploaded',
            self::Processing => 'Processing',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Uploaded => 'blue',
            self::Processing => 'yellow',
            self::Ready => 'green',
            self::Failed => 'red',
        };
    }
}
