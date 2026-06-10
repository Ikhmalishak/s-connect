<?php

namespace App\Console\Commands;

use App\Models\ArchiveContainerReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportContainerReports extends Command
{
    protected $signature = 'app:import-container-reports';

    protected $description = 'Import Dataverse Container Reports';

    public function handle()
    {
        $path = storage_path('app/import/skp_skpcontainerreports.csv');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $file = fopen($path, 'r');

        $header = fgetcsv($file);

        $header = array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            $value = trim($value);
            $value = trim($value, '"');

            return $value;
        }, $header);

        $count = 0;

        while (($row = fgetcsv($file)) !== false) {

            $data = array_combine($header, $row);
            $photos = [];

            foreach ($data as $column => $value) {

                if (! str_ends_with(strtolower($column), 'photo')) {
                    continue;
                }

                if (blank($value)) {
                    continue;
                }

                try {

                    $binary = base64_decode($value, true);

                    if ($binary === false) {
                        continue;
                    }

                    $filename = Str::uuid() . '.jpg';

                    Storage::disk('public')->put(
                        "uploads/archive/{$filename}",
                        $binary
                    );

                    $photos[$column] = "uploads/archive/{$filename}";

                } catch (\Throwable $e) {

                    Log::error(
                        "Photo import failed",
                        [
                            'column' => $column,
                            'message' => $e->getMessage(),
                        ]
                    );
                }
            }

            ArchiveContainerReport::Create(
                [
                    'containertrucknumber' => $data['skp_containertrucknumber_r1'],
                    'skp_stapprovalnamewarehouse'
                        => $data['skp_stapprovalnamewarehouse'] ?? null,

                    'skp_approvalrequestdatetime'
                        => $this->parseDate($data['skp_approvalrequestdatetime'] ?? null),

                    'skp_stapprovalresponsedatetime'
                        => $this->parseDate($data['skp_stapprovalresponsedatetime'] ?? null),

                    'skp_stapprovalresultwarehouse'
                        => $data['skp_stapprovalresultwarehouse'] ?? null,

                    'skp_stapprovalsummarywarehouse'
                        => $data['skp_stapprovalsummarywarehouse'] ?? null,

                    'skp_ndapprovalnameqa'
                        => $data['skp_ndapprovalnameqa'] ?? null,

                    'skp_ndapprovalrequestdatetime'
                        => $this->parseDate($data['skp_ndapprovalrequestdatetime'] ?? null),

                    'skp_ndapprovalresponsedatetime'
                        => $this->parseDate($data['skp_ndapprovalresponsedatetime'] ?? null),

                    'skp_ndapprovalresultqa'
                        => $data['skp_ndapprovalresultqa'] ?? null,

                    'skp_ndapprovalsummaryqa'
                        => $data['skp_ndapprovalsummaryqa'] ?? null,

                    'skp_rdapprovalnameshipping'
                        => $data['skp_rdapprovalnameshipping'] ?? null,

                    'skp_rdapprovalrequestdatetime'
                        => $this->parseDate($data['skp_rdapprovalrequestdatetime'] ?? null),

                    'skp_rdapprovalresponsedatetime'
                        => $this->parseDate($data['skp_rdapprovalresponsedatetime'] ?? null),

                    'skp_rdapprovalresultshipping'
                        => $data['skp_rdapprovalresultshipping'] ?? null,

                    'skp_rdapprovalsummaryshipping'
                        => $data['skp_rdapprovalsummaryshipping'] ?? null,

                    'skp_thapprovalnamesecurity'
                        => $data['skp_thapprovalnamesecurity'] ?? null,

                    'skp_thapprovalrequestdatetime'
                        => $this->parseDate($data['skp_thapprovalrequestdatetime'] ?? null),

                    'skp_thapprovalresponsedatetime'
                        => $this->parseDate($data['skp_thapprovalresponsedatetime'] ?? null),

                    'skp_thapprovalresultsecurity'
                        => $data['skp_thapprovalresultsecurity'] ?? null,

                    'skp_thapprovalsummarysecurity'
                        => $data['skp_thapprovalsummarysecurity'] ?? null,

                    'skp_loadingdate'
                        => $this->parseDate($data['skp_loadingdate'] ?? null),

                    'photos' => $photos,
                ]
            );

            $count++;

            if ($count % 100 === 0) {
                $this->info("Imported {$count} records");
            }
        }

        fclose($file);

        $this->info("Import completed. Total: {$count}");

        return self::SUCCESS;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}