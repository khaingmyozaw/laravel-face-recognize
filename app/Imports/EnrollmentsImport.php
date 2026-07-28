<?php

namespace App\Imports;

use App\Repositories\EnrollmentRepository;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class EnrollmentsImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    private int $importedCount = 0;

    public function __construct(
        private EnrollmentRepository $repository,
    ) {}

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $this->normalizeRow($row);

            if ($data === null) {
                continue;
            }

            $this->repository->create($data);
            $this->importedCount++;
        }
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'mother_name' => ['required', 'string', 'max:255'],
            'nrc' => ['nullable', 'string', 'max:255'],
            'is_father_alive' => ['nullable', 'string', 'max:10'],
            'is_mother_alive' => ['nullable', 'string', 'max:10'],
            'remark' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, array{row: int, attribute: string, errors: list<string>, values: array<string, mixed>}>
     */
    public function failureReport(): array
    {
        return array_map(
            fn (Failure $failure): array => [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => array_values($failure->errors()),
                'values' => $failure->values(),
            ],
            $this->failures()->all(),
        );
    }

    /**
     * @param  Collection<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function normalizeRow(Collection $row): ?array
    {
        $name = $this->stringOrNull($row->get('name'));

        if ($name === null) {
            return null;
        }

        return [
            'name' => $name,
            'father_name' => (string) ($this->stringOrNull($row->get('father_name')) ?? ''),
            'mother_name' => (string) ($this->stringOrNull($row->get('mother_name')) ?? ''),
            'nrc' => $this->stringOrNull($row->get('nrc')),
            'is_f_alive' => $this->boolFromCell($row->get('is_father_alive')),
            'is_m_alive' => $this->boolFromCell($row->get('is_mother_alive')),
            'remark' => $this->stringOrNull($row->get('remark')),
            'address' => (string) ($this->stringOrNull($row->get('address')) ?? ''),
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function boolFromCell(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y'], true);
    }
}
