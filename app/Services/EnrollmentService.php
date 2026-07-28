<?php

namespace App\Services;

use App\Exports\EnrollmentsExport;
use App\Exports\EnrollmentsImportTemplate;
use App\Imports\EnrollmentsImport;
use App\Models\EnrollmentImages;
use App\Models\Enrollments;
use App\Repositories\EnrollmentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EnrollmentService
{
    public function __construct(
        private EnrollmentRepository $repository,
        private FaceRecognitionClient $faceClient,
    ) {}

    public function exportToExcel(string $fileName = 'enrollments.xlsx'): BinaryFileResponse
    {
        return ExcelFacade::download(new EnrollmentsExport, $fileName, Excel::XLSX);
    }

    public function downloadImportTemplate(string $fileName = 'enrollments_import_template.xlsx'): BinaryFileResponse
    {
        return ExcelFacade::download(new EnrollmentsImportTemplate, $fileName, Excel::XLSX);
    }

    /**
     * @return array{imported: int, failures: array<int, array{row: int, attribute: string, errors: list<string>, values: array<string, mixed>}>}
     *
     * @throws Throwable
     */
    public function importFromExcel(UploadedFile $file): array
    {
        $import = new EnrollmentsImport($this->repository);

        DB::transaction(function () use ($import, $file): void {
            ExcelFacade::import($import, $file);
        });

        return [
            'imported' => $import->importedCount(),
            'failures' => $import->failureReport(),
        ];
    }

    public function list(int $perPage = 12, ?string $search = null): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $search);
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
     * Search enrollments by face photo via the face identify API.
     *
     * @return array{
     *     matches: list<array{
     *         similarity: float,
     *         is_match: bool,
     *         enrollment: Enrollments,
     *     }>,
     *     face_count: int|null,
     *     frames_sampled: int|null,
     *     frames_with_face: int|null,
     *     threshold: float|null,
     *     candidate_count: int,
     *     error: string|null,
     * }
     */
    public function searchByImage(UploadedFile $image): array
    {
        $candidates = $this->buildIdentifyCandidates();

        if ($candidates === []) {
            return $this->emptySearchResult(
                candidateCount: 0,
                error: __('No enrolled faces with embeddings are available to search.'),
            );
        }

        $contents = @file_get_contents($image->getRealPath());

        if ($contents === false) {
            return $this->emptySearchResult(
                candidateCount: count($candidates),
                error: __('Unable to read the uploaded image.'),
            );
        }

        $result = $this->faceClient->identify(
            base64_encode($contents),
            $candidates,
        );

        if ($result === null) {
            return $this->emptySearchResult(
                candidateCount: count($candidates),
                error: __('Face search failed. Check that the face service is running and the image contains a face.'),
            );
        }

        return [
            'matches' => $this->resolveIdentifyMatches($result['matches']),
            'face_count' => $result['face_count'],
            'frames_sampled' => null,
            'frames_with_face' => null,
            'threshold' => $result['threshold'],
            'candidate_count' => count($candidates),
            'error' => null,
        ];
    }

    /**
     * Search enrollments by face video via the face video identify API.
     *
     * @return array{
     *     matches: list<array{
     *         similarity: float,
     *         is_match: bool,
     *         enrollment: Enrollments,
     *     }>,
     *     face_count: int|null,
     *     frames_sampled: int|null,
     *     frames_with_face: int|null,
     *     threshold: float|null,
     *     candidate_count: int,
     *     error: string|null,
     * }
     */
    public function searchByVideo(UploadedFile $video): array
    {
        $candidates = $this->buildIdentifyCandidates();

        if ($candidates === []) {
            return $this->emptySearchResult(
                candidateCount: 0,
                error: __('No enrolled faces with embeddings are available to search.'),
            );
        }

        $contents = @file_get_contents($video->getRealPath());

        if ($contents === false) {
            return $this->emptySearchResult(
                candidateCount: count($candidates),
                error: __('Unable to read the uploaded video.'),
            );
        }

        $result = $this->faceClient->identifyFromVideo(
            base64_encode($contents),
            $candidates,
        );

        if ($result === null) {
            return $this->emptySearchResult(
                candidateCount: count($candidates),
                error: __('Video search failed. Check that the face service is running and the video contains a face.'),
            );
        }

        return [
            'matches' => $this->resolveIdentifyMatches($result['matches']),
            'face_count' => null,
            'frames_sampled' => $result['frames_sampled'],
            'frames_with_face' => $result['frames_with_face'],
            'threshold' => $result['threshold'],
            'candidate_count' => count($candidates),
            'error' => null,
        ];
    }

    /**
     * @return list<array{user_id: string, embedding: list<float>}>
     */
    private function buildIdentifyCandidates(): array
    {
        return $this->repository->imagesWithEmbeddings()
            ->map(function (EnrollmentImages $image): ?array {
                $embedding = $image->embedding;

                if (! is_array($embedding) || $embedding === []) {
                    return null;
                }

                return [
                    'user_id' => (string) $image->enrollment_id,
                    'embedding' => array_values(array_map('floatval', $embedding)),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<array{user_id: string, similarity: float, is_match: bool}>  $matches
     * @return list<array{similarity: float, is_match: bool, enrollment: Enrollments}>
     */
    private function resolveIdentifyMatches(array $matches): array
    {
        $bestByEnrollment = [];

        foreach ($matches as $match) {
            $enrollmentId = (int) $match['user_id'];

            if ($enrollmentId < 1) {
                continue;
            }

            $existing = $bestByEnrollment[$enrollmentId] ?? null;

            if ($existing === null || $match['similarity'] > $existing['similarity']) {
                $bestByEnrollment[$enrollmentId] = $match;
            }
        }

        $enrollments = $this->repository->findManyByIds(array_keys($bestByEnrollment));

        $resolved = [];

        foreach ($bestByEnrollment as $enrollmentId => $match) {
            $enrollment = $enrollments->get($enrollmentId);

            if ($enrollment === null) {
                continue;
            }

            $resolved[] = [
                'similarity' => $match['similarity'],
                'is_match' => $match['is_match'],
                'enrollment' => $enrollment,
            ];
        }

        usort(
            $resolved,
            fn (array $left, array $right): int => $right['similarity'] <=> $left['similarity'],
        );

        return $resolved;
    }

    /**
     * @return array{
     *     matches: list<array{similarity: float, is_match: bool, enrollment: Enrollments}>,
     *     face_count: int|null,
     *     frames_sampled: int|null,
     *     frames_with_face: int|null,
     *     threshold: float|null,
     *     candidate_count: int,
     *     error: string|null,
     * }
     */
    private function emptySearchResult(int $candidateCount, string $error): array
    {
        return [
            'matches' => [],
            'face_count' => null,
            'frames_sampled' => null,
            'frames_with_face' => null,
            'threshold' => null,
            'candidate_count' => $candidateCount,
            'error' => $error,
        ];
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
