<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchiveContainerReport extends Model
{
    use HasFactory;

    protected $table = 'archive_container_reports';

    protected $fillable = [
        'skp_stapprovalnamewarehouse',
        'skp_approvalrequestdatetime',
        'skp_stapprovalresponsedatetime',
        'skp_stapprovalresultwarehouse',
        'skp_stapprovalsummarywarehouse',
        'skp_ndapprovalnameqa',
        'skp_ndapprovalrequestdatetime',
        'skp_ndapprovalresponsedatetime',
        'skp_ndapprovalresultqa',
        'skp_ndapprovalsummaryqa',
        'skp_rdapprovalnameshipping',
        'skp_rdapprovalrequestdatetime',
        'skp_rdapprovalresponsedatetime',
        'skp_rdapprovalresultshipping',
        'skp_rdapprovalsummaryshipping',
        'skp_thapprovalnamesecurity',
        'skp_thapprovalrequestdatetime',
        'skp_thapprovalresponsedatetime',
        'skp_thapprovalresultsecurity',
        'skp_thapprovalsummarysecurity',
        'skp_loadingdate',
        'containertrucknumber',
        'photos',
    ];

    protected $casts = [
        'skp_approvalrequestdatetime' => 'datetime',
        'skp_stapprovalresponsedatetime' => 'datetime',
        'skp_ndapprovalrequestdatetime' => 'datetime',
        'skp_ndapprovalresponsedatetime' => 'datetime',
        'skp_rdapprovalrequestdatetime' => 'datetime',
        'skp_rdapprovalresponsedatetime' => 'datetime',
        'skp_thapprovalrequestdatetime' => 'datetime',
        'skp_thapprovalresponsedatetime' => 'datetime',
        'skp_loadingdate' => 'datetime',
        'photos' => 'array',
    ];

    /**
     * Relationship with ArchiveContainerDetail
     */
    public function archiveContainerDetail()
    {
        return $this->belongsTo(ArchiveContainerDetail::class, 'containertrucknumber', 'container_truck_info_rev1');
    }
}
