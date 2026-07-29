<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_backups(): void
    {
        $this->get(route('backups.index'))->assertRedirect(route('login'));
        $this->get(route('backups.database'))->assertRedirect(route('login'));
        $this->get(route('backups.images'))->assertRedirect(route('login'));
    }

    public function test_gate_users_cannot_access_backups(): void
    {
        $gate = User::factory()->gate()->create();

        $this->actingAs($gate)
            ->get(route('backups.index'))
            ->assertForbidden();

        $this->actingAs($gate)
            ->get(route('backups.database'))
            ->assertForbidden();

        $this->actingAs($gate)
            ->get(route('backups.images'))
            ->assertForbidden();
    }

    public function test_admins_can_view_backups_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('backups.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('backups/index'));
    }

    public function test_admins_can_download_database_sql(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('backups.database'));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            '.sql',
            (string) $response->headers->get('content-disposition'),
        );
        $this->assertStringContainsString('CREATE TABLE', $response->streamedContent());
    }

    public function test_admins_can_download_database_zip(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('backups.database', [
            'format' => 'zip',
        ]));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            '.zip',
            (string) $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_admins_can_download_enrollment_images_zip(): void
    {
        $admin = User::factory()->admin()->create();

        Storage::disk('public')->put(
            'enrollments/1/sample.jpg',
            'fake-image-content',
        );

        $response = $this->actingAs($admin)->get(route('backups.images'));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            '.zip',
            (string) $response->headers->get('content-disposition'),
        );

        $zipPath = tempnam(sys_get_temp_dir(), 'backup-test-');
        file_put_contents($zipPath, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath) === true);
        $this->assertNotFalse($zip->locateName('enrollments/1/sample.jpg'));
        $zip->close();

        @unlink($zipPath);
    }
}
