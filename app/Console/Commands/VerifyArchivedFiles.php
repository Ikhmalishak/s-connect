<?php

namespace App\Console\Commands;

use App\Models\ShipmentTransport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VerifyArchivedFiles extends Command
{
    protected $signature = 'app:verify-archived-files
                            {--fix : After verification, delete source files from public disk if checksums match}';
    protected $description = 'Verify integrity of archived container photos by comparing checksums';

    public function handle()
    {
        $containers = ShipmentTransport::with('photo')
            ->where('is_archived', true)
            ->get();

        if ($containers->isEmpty()) {
            $this->info('No archived containers found');
            return 0;
        }

        $matched = 0;
        $mismatched = 0;
        $missingSource = 0;
        $missingNas = 0;
        $cleanedUp = 0;

        foreach ($containers as $container) {
            foreach ($container->photo as $photo) {
                $nasPath = $photo->photo_path;

                // Check NAS file exists
                if (!Storage::disk('nas')->exists($nasPath)) {
                    $this->error("  [MISSING NAS] Container {$container->id} / Photo {$photo->id}: {$nasPath}");
                    $missingNas++;
                    continue;
                }

                // Reconstruct original public path
                // Original stored as: container_photo/{filename}
                // NAS path format:    archive/container_photo/{container_id}/{filename}
                $publicPath = 'container_photo/' . basename($nasPath);

                // Check if public source still exists
                if (!Storage::disk('public')->exists($publicPath)) {
                    $this->comment("  [SOURCE ALREADY GONE] Container {$container->id} / Photo {$photo->id} — only NAS copy exists (OK)");
                    $missingSource++;
                    continue;
                }

                // Checksum comparison
                $publicChecksum = md5(Storage::disk('public')->get($publicPath));
                $nasChecksum    = md5(Storage::disk('nas')->get($nasPath));

                if ($publicChecksum === $nasChecksum) {
                    $this->line("  [✓ MATCH] Container {$container->id} / Photo {$photo->id} — checksum OK");

                    if ($this->option('fix')) {
                        Storage::disk('public')->delete($publicPath);
                        $this->line("             → Deleted source: {$publicPath}");
                        $cleanedUp++;
                    }

                    $matched++;
                } else {
                    $this->error("  [✗ MISMATCH] Container {$container->id} / Photo {$photo->id}");
                    $this->error("       public md5: {$publicChecksum}");
                    $this->error("       nas    md5: {$nasChecksum}");
                    $mismatched++;
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Matched (integrity OK)',                $matched],
                ['Mismatched (data corruption risk)',      $mismatched],
                ['Source already deleted (OK)',            $missingSource],
                ['NAS file missing (needs re-archive)',    $missingNas],
            ]
        );

        if ($this->option('fix') && $cleanedUp > 0) {
            $this->info("Cleaned up {$cleanedUp} source files from public disk");
        }

        $this->newLine();

        if ($mismatched > 0) {
            $this->warn('⚠  Mismatches found. DO NOT delete source files until resolved.');
        }

        if ($missingNas > 0) {
            $this->warn('⚠  Some NAS files are missing. Re-run the archive command to recopy.');
        }

        if ($matched + $missingSource > 0 && $mismatched === 0 && $missingNas === 0) {
            $this->info('✅ All archived files verified successfully. Safe to clean up remaining sources with --fix');
        }

        return ($mismatched > 0 || $missingNas > 0) ? 1 : 0;
    }
}