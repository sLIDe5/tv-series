<?php

namespace App\Libraries;

class Helper
{
    public static function clearName(string $name): string
    {
        return str_replace([':'], '', $name);
    }
}
