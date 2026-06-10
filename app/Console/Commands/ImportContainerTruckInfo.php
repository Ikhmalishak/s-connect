<?php

namespace App\Console\Commands;

use App\Models\ArchiveContainerDetail;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportContainerTruckInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-container-truck-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = storage_path('app/import/container_truck.csv');

        $file = fopen($path, 'r');

        $header = fgetcsv($file);

        $header = array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value); // remove BOM
            $value = trim($value, '"');
            return trim($value);
        }, $header);

        while (($row = fgetcsv($file)) !== false) {

            $data = array_combine($header, $row);

            ArchiveContainerDetail::updateOrCreate(
                [
                    'container_truck_info_rev1'
                    => $data['skp_containertruckinfobyshippingrev1id']
                ],
                [
                    'skp_site' => 'S2',

                    'container_truck' =>
                        $data['skp_containertruck'] == '921680001'
                        ? 'Truck'
                        : 'Container',

                    'container_truck_number'
                    => $data['skp_containertrucknumber_r1'],

                    'date'
                    => Carbon::parse($data['skp_date'])->format('Y-m-d'),

                    'country'
                    => $data['skp_country'],

                    'forwarder'
                    => $data['skp_forwarder'],

                    'container_size'
                    => $data['skp_containersize'] ?? null,

                    'hauler'
                    => $data['skp_hauler'],

                    'sku_number'
                    => $data['skp_skunumber'],

                    'model_project'
                    => $data['skp_modelproject'],

                    'work_order'
                    => $data['skp_workorder'],

                    'high_security_seal'
                    => (int) $data['skp_highsecurityseal'],

                    'high_security_seal_sn'
                    => $data['skp_highsecuritysealsn'],

                    'gps'
                    => (int) $data['skp_gps'],

                    'outside_gps_sn'
                    => $data['skp_outsidegpssn'],

                    'inside_gps_sn'
                    => $data['skp_insidegpssn'],

                    'gps_country'
                    => $data['skp_gpscountry'],

                    'fork_seal'
                    => (int) $data['skp_forkseal'],

                    'fork_seal_size'
                    => $data['skp_forksealsize'],

                    'fork_seal_sn'
                    => $data['skp_forksealsn'],

                    'temporary_seal'
                    => (int) $data['skp_temporaryseal'],

                    'temporary_seal_sn'
                    => $data['skp_temporarysealsn'],
                ]
            );
        }

        fclose($file);
    }
}
