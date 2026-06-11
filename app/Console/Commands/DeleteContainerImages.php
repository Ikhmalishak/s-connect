<?php

namespace App\Console\Commands;

use App\Models\ShipmentTransportPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteContainerImages extends Command
{
    protected $signature = 'app:delete-archived-local-photos';

    protected $description = 'Delete local photos that have been successfully archived to NAS';

    public function handle()
    {
        $deleted = 0;
        $skipped = 0;
        $missingNas = 0;

        $photos = ShipmentTransportPhoto::whereNotNull('archived_at')
            ->get();

        foreach ($photos as $photo) {

            // Skip if local file already gone
            if (!Storage::disk('public')->exists($photo->photo_path)) {
                $skipped++;
                continue;
            }

            // Verify NAS file exists
            if (!Storage::disk('nas')->exists($photo->photo_path)) {
                $missingNas++;

                $this->error(
                    "NAS file missing: {$photo->photo_path}"
                );

                continue;
            }

            // Verify file size matches
            $localSize = Storage::disk('public')->size($photo->photo_path);
            $nasSize = Storage::disk('nas')->size($photo->photo_path);

            if ($localSize !== $nasSize) {
                $this->error(
                    "Size mismatch: {$photo->photo_path} (Local: {$localSize}, NAS: {$nasSize})"
                );

                continue;
            }

            // Delete local file
            Storage::disk('public')->delete($photo->photo_path);

            $deleted++;
        }

        $this->info("Deleted: {$deleted}");
        $this->info("Skipped: {$skipped}");
        $this->info("Missing NAS: {$missingNas}");

        return Command::SUCCESS;
    }
}