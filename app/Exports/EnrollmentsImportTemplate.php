<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EnrollmentsImportTemplate implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Name',
            'Father Name',
            'Mother Name',
            'NRC',
            'Is Father Alive',
            'Is Mother Alive',
            'Remark',
            'Address',
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [
            [
                'Aung Aung',
                'U Maung',
                'Daw Mya',
                '12/ABC(N)123456',
                'Yes',
                'Yes',
                'Optional note',
                'Yangon',
            ],
        ];
    }
}
