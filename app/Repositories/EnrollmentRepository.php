<?php

namespace App\Repositories;

use App\Models\EnrollmentImages;
use App\Models\Enrollments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EnrollmentRepository
{
    public function paginate(int $perPage = 12, ?string $search = null): LengthAwarePaginator
    {
        return Enrollments::query()
            ->with('images')
            ->when(
                $search !== null && $search !== '',
                fn (Builder $query) => $this->applySearch($query, $search),
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    private function applySearch(Builder $query, string $search): Builder
    {
        $term = '%'.$search.'%';

        return $query->where(function (Builder $query) use ($term): void {
            $query
                ->where('name', 'like', $term)
                ->orWhere('nrc', 'like', $term)
                ->orWhere('address', 'like', $term)
                ->orWhere('father_name', 'like', $term)
                ->orWhere('mother_name', 'like', $term);
        });
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

    /**
     * Images that have a usable face embedding for 1:N identify.
     *
     * @return Collection<int, EnrollmentImages>
     */
    public function imagesWithEmbeddings(): Collection
    {
        return EnrollmentImages::query()
            ->whereNotNull('embedding')
            ->get(['id', 'enrollment_id', 'embedding']);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Enrollments>
     */
    public function findManyByIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Enrollments::query()
            ->with('images')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }
}
