<?php

namespace App\Repositories;

use App\Models\EnrollmentImages;
use App\Models\Enrollments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EnrollmentRepository
{
    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        return Enrollments::query()
            ->with('images')
            ->latest()
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Enrollments
    {
        return Enrollments::query()
            ->with('images')
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Enrollments
    {
        return Enrollments::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Enrollments $enrollment, array $data): Enrollments
    {
        $enrollment->update($data);

        return $enrollment->refresh();
    }

    public function delete(Enrollments $enrollment): void
    {
        $enrollment->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $images
     * @return Collection<int, EnrollmentImages>
     */
    public function attachImages(Enrollments $enrollment, array $images): Collection
    {
        return collect($images)->map(
            fn (array $image): EnrollmentImages => $enrollment->images()->create($image),
        );
    }

    public function findImageOrFail(Enrollments $enrollment, int $imageId): EnrollmentImages
    {
        return $enrollment->images()->whereKey($imageId)->firstOrFail();
    }

    public function deleteImage(EnrollmentImages $image): void
    {
        $image->delete();
    }
}
