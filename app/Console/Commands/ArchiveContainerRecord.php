<?php

namespace App\Console\Commands;

use App\Models\ShipmentTransport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ArchiveContainerRecord extends Command
{
    protected $signature = 'app:archive-container-record';
    protected $description = 'Archive container photos to NAS';

    public function handle()
    {
        $cutoffDate = Carbon::now()->subMonths(1);

        $containers = ShipmentTransport::with('photo')
            ->whereDate('date', '<=', $cutoffDate)
            ->where('is_archived', false)
            ->get();

        DB::transaction(function () use ($containers) {

            foreach ($containers as $container) {

                foreach ($container->photo as $photo) {

                    $oldPath = $photo->photo_path;

                    // skip missing files safely
                    if (!Storage::disk('public')->exists($oldPath)) {
                        continue;
                    }

                    $content = Storage::disk('public')->get($oldPath);

                    $newPath = 'archive/container_photo/' 
                        . $container->id . '/' 
                        . basename($oldPath);

                    Storage::disk('nas')->put($newPath, $content);

                    $photo->photo_path = $newPath;
                    $photo->save();
                }

                $container->is_archived = true;
                $container->save();
            }
        });

        $this->info('Archive completed');
    }
}