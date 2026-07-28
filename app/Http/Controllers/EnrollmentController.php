<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportEnrollmentsRequest;
use App\Http\Requests\SearchEnrollmentRequest;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Models\Enrollments;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $service,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('enrollments/index', [
            'enrollments' => $this->service->list(search: $search !== '' ? $search : null),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function search(): Response
    {
        return Inertia::render('enrollments/search', [
            'result' => null,
        ]);
    }

    public function identify(SearchEnrollmentRequest $request): Response
    {
        $image = $request->file('image');

        if (! $image instanceof UploadedFile) {
            return Inertia::render('enrollments/search', [
                'result' => [
                    'matches' => [],
                    'face_count' => null,
                    'threshold' => null,
                    'candidate_count' => 0,
                    'error' => __('A valid image is required.'),
                ],
            ]);
        }

        try {
            $result = $this->service->searchByImage($image);
        } catch (Throwable $exception) {
            report($exception);

            $result = [
                'matches' => [],
                'face_count' => null,
                'threshold' => null,
                'candidate_count' => 0,
                'error' => __('Unable to search by image. Please try again.'),
            ];
        }

        return Inertia::render('enrollments/search', [
            'result' => $result,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('enrollments/create');
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        try {
            $enrollment = $this->service->create(
                $this->enrollmentData($request->validated()),
                $this->uploadedImages($request->validated()),
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'images' => __('Unable to create enrollment. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Enrollment created.'),
        ]);

        return to_route('enrollments.show', $enrollment);
    }

    public function show(Enrollments $enrollment): Response
    {
        return Inertia::render('enrollments/show', [
            'enrollment' => $this->service->find($enrollment->id),
        ]);
    }

    public function edit(Enrollments $enrollment): Response
    {
        return Inertia::render('enrollments/edit', [
            'enrollment' => $this->service->find($enrollment->id),
        ]);
    }

    public function update(
        UpdateEnrollmentRequest $request,
        Enrollments $enrollment,
    ): RedirectResponse {
        try {
            $this->service->update(
                $enrollment,
                $this->enrollmentData($request->validated()),
                $this->uploadedImages($request->validated()),
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'images' => __('Unable to update enrollment. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Enrollment updated.'),
        ]);

        return to_route('enrollments.show', $enrollment);
    }

    public function destroy(Enrollments $enrollment): RedirectResponse
    {
        try {
            $this->service->delete($enrollment);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'enrollment' => __('Unable to delete enrollment. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Enrollment deleted.'),
        ]);

        return to_route('enrollments.index');
    }

    public function export(): BinaryFileResponse
    {
        $timestamp = now()->format('Ymd_His');

        return $this->service->exportToExcel("enrollments_{$timestamp}.xlsx");
    }

    public function importTemplate(): BinaryFileResponse
    {
        return $this->service->downloadImportTemplate();
    }

    public function import(ImportEnrollmentsRequest $request): RedirectResponse
    {
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            return back()->withErrors([
                'file' => __('A valid Excel file is required.'),
            ]);
        }

        try {
            $result = $this->service->importFromExcel($file);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'file' => __('Unable to import enrollments. Please try again.'),
            ]);
        }

        return $this->flashImportResult($result);
    }

    public function destroyImage(
        Enrollments $enrollment,
        int $image,
    ): RedirectResponse {
        try {
            $this->service->deleteImage($enrollment, $image);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'images' => __('Unable to delete image. Please try again.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Image deleted.'),
        ]);

        return back();
    }

    /**
     * @param  array{imported: int, failures: array<int, array<string, mixed>>}  $result
     */
    private function flashImportResult(array $result): RedirectResponse
    {
        $imported = $result['imported'];
        $failureCount = count($result['failures']);

        $message = $failureCount === 0
            ? __(':count enrollment(s) imported.', ['count' => $imported])
            : __(':count imported, :failed row(s) skipped.', [
                'count' => $imported,
                'failed' => $failureCount,
            ]);

        Inertia::flash('toast', [
            'type' => $failureCount === 0 ? 'success' : 'warning',
            'message' => $message,
        ]);

        if ($failureCount > 0) {
            return back()->with('importFailures', $result['failures']);
        }

        return to_route('enrollments.index');
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
