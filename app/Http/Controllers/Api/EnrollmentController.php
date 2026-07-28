<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchEnrollmentRequest;
use App\Http\Requests\SearchEnrollmentVideoRequest;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollments;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Throwable;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 12), 1), 100);

        $enrollments = $this->service->list(
            perPage: $perPage,
            search: $search !== '' ? $search : null,
        );

        return EnrollmentResource::collection($enrollments);
    }

    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        try {
            $enrollment = $this->service->create(
                $this->enrollmentData($request->validated()),
                $this->uploadedImages($request->validated()),
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('Unable to create enrollment. Please try again.'),
            ], 500);
        }

        return (new EnrollmentResource($enrollment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Enrollments $enrollment): EnrollmentResource
    {
        return new EnrollmentResource($this->service->find($enrollment->id));
    }

    public function update(
        UpdateEnrollmentRequest $request,
        Enrollments $enrollment,
    ): EnrollmentResource|JsonResponse {
        try {
            $enrollment = $this->service->update(
                $enrollment,
                $this->enrollmentData($request->validated()),
                $this->uploadedImages($request->validated()),
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('Unable to update enrollment. Please try again.'),
            ], 500);
        }

        return new EnrollmentResource($enrollment);
    }

    public function destroy(Enrollments $enrollment): JsonResponse
    {
        try {
            $this->service->delete($enrollment);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('Unable to delete enrollment. Please try again.'),
            ], 500);
        }

        return response()->json([
            'message' => __('Enrollment deleted.'),
        ]);
    }

    public function destroyImage(Enrollments $enrollment, int $image): JsonResponse
    {
        try {
            $this->service->deleteImage($enrollment, $image);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => __('Unable to delete image. Please try again.'),
            ], 500);
        }

        return response()->json([
            'message' => __('Image deleted.'),
        ]);
    }

    public function identify(SearchEnrollmentRequest $request): JsonResponse
    {
        $image = $request->file('image');

        if (! $image instanceof UploadedFile) {
            return response()->json($this->invalidSearchResult(__('A valid image is required.')), 422);
        }

        try {
            $result = $this->service->searchByImage($image);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(
                $this->invalidSearchResult(__('Unable to search by image. Please try again.')),
                500,
            );
        }

        return response()->json($this->formatSearchResult($result));
    }

    public function identifyVideo(SearchEnrollmentVideoRequest $request): JsonResponse
    {
        $video = $request->file('video');

        if (! $video instanceof UploadedFile) {
            return response()->json($this->invalidSearchResult(__('A valid video is required.')), 422);
        }

        try {
            $result = $this->service->searchByVideo($video);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(
                $this->invalidSearchResult(__('Unable to search by video. Please try again.')),
                500,
            );
        }

        return response()->json($this->formatSearchResult($result));
    }

    /**
     * @param  array{
     *     matches: list<array{similarity: float, is_match: bool, enrollment: Enrollments}>,
     *     face_count: int|null,
     *     frames_sampled: int|null,
     *     frames_with_face: int|null,
     *     threshold: float|null,
     *     candidate_count: int,
     *     error: string|null,
     * }  $result
     * @return array<string, mixed>
     */
    private function formatSearchResult(array $result): array
    {
        return [
            'matches' => array_map(
                fn (array $match): array => [
                    'similarity' => $match['similarity'],
                    'is_match' => $match['is_match'],
                    'enrollment' => (new EnrollmentResource($match['enrollment']->loadMissing('images')))->resolve(),
                ],
                $result['matches'],
            ),
            'face_count' => $result['face_count'],
            'frames_sampled' => $result['frames_sampled'],
            'frames_with_face' => $result['frames_with_face'],
            'threshold' => $result['threshold'],
            'candidate_count' => $result['candidate_count'],
            'error' => $result['error'],
        ];
    }

    /**
     * @return array{
     *     matches: list<mixed>,
     *     face_count: null,
     *     frames_sampled: null,
     *     frames_with_face: null,
     *     threshold: null,
     *     candidate_count: int,
     *     error: string,
     * }
     */
    private function invalidSearchResult(string $error): array
    {
        return [
            'matches' => [],
            'face_count' => null,
            'frames_sampled' => null,
            'frames_with_face' => null,
            'threshold' => null,
            'candidate_count' => 0,
            'error' => $error,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function enrollmentData(array $validated): array
    {
        return collect($validated)->except('images')->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<UploadedFile>
     */
    private function uploadedImages(array $validated): array
    {
        $images = $validated['images'] ?? [];

        return array_values(array_filter(
            $images,
            fn (mixed $image): bool => $image instanceof UploadedFile,
        ));
    }
}
