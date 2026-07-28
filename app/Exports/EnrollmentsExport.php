<?php

namespace App\Exports;

use App\Models\Enrollments;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Enrollments>
 */
class EnrollmentsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @return Collection<int, Enrollments>
     */
    public function collection(): Collection
    {
        return Enrollments::query()->with('images')->latest()->get();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Father Name',
            'Mother Name',
            'NRC',
            'Is Father Alive',
            'Is Mother Alive',
            'Remark',
            'Address',
            'Created At',
        ];
    }

    /**
     * @param  Enrollments  $row
     * @return list<mixed>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->father_name,
            $row->mother_name,
            $row->nrc,
            $row->is_f_alive ? 'Yes' : 'No',
            $row->is_m_alive ? 'Yes' : 'No',
            $row->remark,
            $row->address,
            optional($row->created_at)->toDateTimeString(),
        ];
    }
}
