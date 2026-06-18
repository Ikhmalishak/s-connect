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
    protected $description = 'Archive container photos from public disk to NAS';

    public function handle()
    {
        $cutoffDate = Carbon::now()->subMonths(3);

        $containers = ShipmentTransport::with('photo')
            ->whereDate('date', '<=', $cutoffDate)
            ->where('is_archived', false)
            ->get();

        if ($containers->isEmpty()) {
            $this->info('Nothing to archive');
            return 0;
        }

        $successCount = 0;
        $failedCount = 0;

        DB::transaction(function () use ($containers, &$successCount, &$failedCount) {

            foreach ($containers as $container) {

                foreach ($container->photo as $photo) {

                    $oldPath = $photo->photo_path;

                    // skip if file already missing from source
                    if (!Storage::disk('public')->exists($oldPath)) {
                        $this->warn("  [SKIP] Missing source: {$oldPath}");
                        continue;
                    }

                    $content = Storage::disk('public')->get($oldPath);
                    $sourceChecksum = md5($content);

                    $newPath = 'archive/container_photo/'
                        . $container->id . '/'
                        . basename($oldPath);

                    // Write to NAS
                    $written = Storage::disk('nas')->put($newPath, $content);

                    if (!$written) {
                        $this->error("  [FAIL] NAS write failed: {$newPath}");
                        $failedCount++;
                        continue;
                    }

                    // Verify: read back from NAS and compare checksum
                    $nasContent = Storage::disk('nas')->get($newPath);

                    if ($nasContent === false || md5($nasContent) !== $sourceChecksum) {
                        $this->error("  [FAIL] Checksum mismatch for: {$oldPath}");
                        // Remove the corrupted/partial NAS file
                        Storage::disk('nas')->delete($newPath);
                        $failedCount++;
                        continue;
                    }

                    // ✅ Integrity confirmed — safe to delete source and update DB
                    Storage::disk('public')->delete($oldPath);

                    $photo->photo_path = $newPath;
                    $photo->archived_at = now();
                    $photo->save();

                    $successCount++;
                }

                // Mark container archived only if at least one photo was processed
                // (No need to mark if all photos were skipped/missing/failed,
                //  so next run can retry remaining)
                if ($container->photo->whereNull('archived_at')->count() === 0) {
                    $container->is_archived = true;
                    $container->save();
                }
            }
        });

        $this->info("Archive completed – success: {$successCount}, failed: {$failedCount}");

        return $failedCount > 0 ? 1 : 0;
    }
}