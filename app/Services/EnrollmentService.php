<?php

namespace App\Services;

use App\Models\EnrollmentImages;
use App\Models\Enrollments;
use App\Repositories\EnrollmentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class EnrollmentService
{
    public function __construct(
        private EnrollmentRepository $repository,
        private FaceRecognitionClient $faceClient,
    ) {}

    public function list(int $perPage = 12): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function find(int $id): Enrollments
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $images
     *
     * @throws Throwable
     */
    public function create(array $data, array $images = []): Enrollments
    {
        return DB::transaction(function () use ($data, $images): Enrollments {
            $enrollment = $this->repository->create($data);
            $this->storeImages($enrollment, $images);

            return $enrollment->load('images');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $images
     *
     * @throws Throwable
     */
    public function update(Enrollments $enrollment, array $data, array $images = []): Enrollments
    {
        return DB::transaction(function () use ($enrollment, $data, $images): Enrollments {
            $this->repository->update($enrollment, $data);
            $this->storeImages($enrollment, $images);

            return $enrollment->load('images');
        });
    }

    /**
     * @throws Throwable
     */
    public function delete(Enrollments $enrollment): void
    {
        DB::transaction(function () use ($enrollment): void {
            $enrollment->loadMissing('images');

            foreach ($enrollment->images as $image) {
                $this->deleteImageFile($image);
            }

            $this->repository->delete($enrollment);
        });
    }

    /**
     * @throws Throwable
     */
    public function deleteImage(Enrollments $enrollment, int $imageId): void
    {
        DB::transaction(function () use ($enrollment, $imageId): void {
            $image = $this->repository->findImageOrFail($enrollment, $imageId);
            $this->deleteImageFile($image);
            $this->repository->deleteImage($image);
        });
    }

    /**
     * @param  list<UploadedFile>  $images
     */
    private function storeImages(Enrollments $enrollment, array $images): void
    {
        if ($images === []) {
            return;
        }

        $payloads = array_map(
            fn (UploadedFile $image): array => $this->buildImagePayload($image, $enrollment),
            $images,
        );

        $this->repository->attachImages($enrollment, $payloads);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildImagePayload(UploadedFile $image, Enrollments $enrollment): array
    {
        $path = $image->store('enrollments/'.$enrollment->id, 'public');

        if (! is_string($path)) {
            throw new RuntimeException('Failed to store enrollment image.');
        }

        $embedding = $this->faceClient->embed(Storage::disk('public')->path($path));

        return [
            'file_path' => $path,
            'embedding' => $embedding['embedding'] ?? null,
            'embedding_dim' => $embedding['embedding_dim'] ?? null,
            'bbox' => $embedding['bbox'] ?? null,
            'det_score' => $embedding['det_score'] ?? null,
            'face_count' => $embedding['face_count'] ?? null,
        ];
    }

    private function deleteImageFile(EnrollmentImages $image): void
    {
        if (Storage::disk('public')->exists($image->file_path)) {
            Storage::disk('public')->delete($image->file_path);
        }
    }
}
