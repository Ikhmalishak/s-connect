<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('archive_container_reports', function (Blueprint $table) {
            $table->id();
            $table->string('skp_stapprovalnamewarehouse', 255)->nullable();
            $table->datetime('skp_approvalrequestdatetime')->nullable();
            $table->datetime('skp_stapprovalresponsedatetime')->nullable();
            $table->string('skp_stapprovalresultwarehouse', 50)->nullable();
            $table->text('skp_stapprovalsummarywarehouse')->nullable();
            $table->string('skp_ndapprovalnameqa', 255)->nullable();
            $table->datetime('skp_ndapprovalrequestdatetime')->nullable();
            $table->datetime('skp_ndapprovalresponsedatetime')->nullable();
            $table->string('skp_ndapprovalresultqa', 50)->nullable();
            $table->text('skp_ndapprovalsummaryqa')->nullable();
            $table->string('skp_rdapprovalnameshipping', 255)->nullable();
            $table->datetime('skp_rdapprovalrequestdatetime')->nullable();
            $table->datetime('skp_rdapprovalresponsedatetime')->nullable();
            $table->string('skp_rdapprovalresultshipping', 50)->nullable();
            $table->text('skp_rdapprovalsummaryshipping')->nullable();
            $table->string('skp_thapprovalnamesecurity', 255)->nullable();
            $table->datetime('skp_thapprovalrequestdatetime')->nullable();
            $table->datetime('skp_thapprovalresponsedatetime')->nullable();
            $table->string('skp_thapprovalresultsecurity', 50)->nullable();
            $table->text('skp_thapprovalsummarysecurity')->nullable();
            $table->datetime('skp_loadingdate')->nullable();
            $table->string('containertrucknumber', 255)->nullable();
            $table->longText('photos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_container_reports');
    }
};
