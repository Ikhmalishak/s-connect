<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchiveContainerDetail extends Model
{
    use HasFactory;

    protected $table = 'archive_container_details';

    protected $fillable = [
        'container_truck_info_rev1',
        'row_checksum',
        'modified_on',
        'skp_site',
        'container_truck',
        'container_truck_number',
        'date',
        'country',
        'forwarder',
        'container_size',
        'hauler',
        'sku_number',
        'model_project',
        'work_order',
        'high_security_seal',
        'high_security_seal_sn',
        'gps',
        'outside_gps_sn',
        'inside_gps_sn',
        'gps_country',
        'fork_seal',
        'fork_seal_size',
        'fork_seal_sn',
        'temporary_seal',
        'temporary_seal_sn',
        'created_on',
        'created_by',
    ];

    protected $casts = [
        'modified_on' => 'datetime',
        'date' => 'date',
        'high_security_seal' => 'boolean',
        'gps' => 'boolean',
        'fork_seal' => 'boolean',
        'temporary_seal' => 'boolean',
        'created_on' => 'datetime',
    ];

    /**
     * Relationship with ArchiveContainerReport
     */
    public function archiveContainerReport()
    {
        return $this->hasOne(ArchiveContainerReport::class, 'containertrucknumber', 'container_truck_info_rev1');
    }
}
