<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(
        private BackupService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('backups/index');
    }

    public function database(Request $request): BinaryFileResponse|RedirectResponse
    {
        $format = $request->query('format', 'sql');

        try {
            return $this->service->downloadDatabase((string) $format);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'backup' => __('Unable to create database backup. Please try again.'),
            ]);
        }
    }

    public function images(): BinaryFileResponse|RedirectResponse
    {
        try {
            return $this->service->downloadEnrollmentImages();
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'backup' => __('Unable to create enrollment images backup. Please try again.'),
            ]);
        }
    }
}
