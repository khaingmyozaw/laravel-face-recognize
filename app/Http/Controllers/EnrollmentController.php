<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchEnrollmentRequest;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Models\Enrollments;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('enrollments/index', [
            'enrollments' => $this->service->list(),
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
