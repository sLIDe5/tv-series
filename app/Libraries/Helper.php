<?php

namespace App\Libraries;

class Helper
{
    public static function clearName(string $name): string
    {
        return str_replace([':'], '', $name);
    }

    public static function generateThumbnail(string $mediaPath, string $thumbnailPath)
    {
        $directory = dirname($thumbnailPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $args = [
            '-ss' => '00:05:00',
            '-i' => $mediaPath,
            '-filter:v' => 'scale=400:-1',
            '-vframes' => 1,
            '-q:v' => 1,
            '-y' => '',
            $thumbnailPath => ''
        ];
        passthru(escapeshellcmd(env('FFMPEG')) . ' ' . self::args($args));
    }

    public static function args($par)
    {
        $re = array();
        foreach ($par as $k => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }
            foreach ($value as $v) {
                $re[] = escapeshellarg(trim($k));
                if ($v !== '') {
                    if (is_numeric($v)) {
                        $re[] = $v;
                    } else {
                        $re[] = escapeshellarg($v);
                    }
                }
            }
        }
        return implode(' ', $re);
    }
}
