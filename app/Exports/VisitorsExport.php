<?php

namespace App\Exports;

use App\Models\Visitor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VisitorsExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get([
            'visitor_name',
            'visitor_type',
            'phone_number',
            'ic_number',
            'passport',
            'visitor_company',
            'purpose',
            'date',
            'time_in',
            'time_out',
        ]);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Type',
            'Phone Number',
            'IC Number',
            'Passport Number',
            'Company',
            'Purpose',
            'Date',
            'Time In',
            'Time Out',
        ];
    }
}
