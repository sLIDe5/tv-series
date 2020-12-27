<?php

namespace App\Console\Commands;

use App\Libraries\Helper;
use App\Models\Episode;
use Illuminate\Console\Command;

class FixMissingImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tv-series:fix-missing-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes missing images by creating them from media file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $offset = 0;
        $size = 100;
        do {
            $episodes = Episode::orderBy('id')->offset($offset)->limit($size)->get();
            $count = $episodes->count();
            $offset += $size;
            foreach ($episodes as $episode) {
                if (file_exists($episode->thumbnailPath)) {
                    try {
                        if (exif_imagetype($episode->thumbnailPath)) {
                            continue;
                        }
                    } catch (\Exception $e) {
                        $this->info($e->getMessage());
                    }
                }
                $this->info('Not exists ' . $episode->mediaPath);
                Helper::generateThumbnail($episode->mediaPath, $episode->thumbnailPath);
            }
        } while ($count === $size);
        return 0;
    }
}
