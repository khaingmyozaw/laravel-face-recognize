<?php

namespace Tests\Feature;

use App\Models\Enrollments;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_enrollments(): void
    {
        $this->get(route('enrollments.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_enrollments_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('enrollments.index'))
            ->assertOk();
    }

    public function test_authenticated_users_can_create_enrollment_with_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('enrollments.store'), [
            'name' => 'Aung Aung',
            'father_name' => 'U Maung',
            'mother_name' => 'Daw Mya',
            'nrc' => '12/ABC(N)123456',
            'is_f_alive' => true,
            'is_m_alive' => true,
            'remark' => 'Test',
            'address' => 'Yangon',
            'images' => [
                UploadedFile::fake()->image('face1.jpg'),
                UploadedFile::fake()->image('face2.png'),
            ],
        ]);

        $enrollment = Enrollments::query()->firstOrFail();

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertDatabaseHas('enrollments', [
            'name' => 'Aung Aung',
            'address' => 'Yangon',
        ]);
        $this->assertDatabaseCount('enrollment_images', 2);
    }

    public function test_authenticated_users_can_update_enrollment(): void
    {
        $user = User::factory()->create();
        $enrollment = Enrollments::query()->create([
            'name' => 'Old Name',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'nrc' => null,
            'is_f_alive' => true,
            'is_m_alive' => false,
            'remark' => null,
            'address' => 'Mandalay',
        ]);

        $response = $this->actingAs($user)->put(route('enrollments.update', $enrollment), [
            'name' => 'New Name',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'nrc' => '1/ABC(N)1',
            'is_f_alive' => false,
            'is_m_alive' => true,
            'remark' => 'Updated',
            'address' => 'Naypyidaw',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'name' => 'New Name',
            'address' => 'Naypyidaw',
            'is_f_alive' => false,
            'is_m_alive' => true,
        ]);
    }

    public function test_authenticated_users_can_delete_enrollment(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $enrollment = Enrollments::query()->create([
            'name' => 'Delete Me',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'is_f_alive' => true,
            'is_m_alive' => true,
            'address' => 'Yangon',
        ]);

        $path = UploadedFile::fake()->image('face.jpg')->store('enrollments/'.$enrollment->id, 'public');
        $enrollment->images()->create(['file_path' => $path]);

        $response = $this->actingAs($user)->delete(route('enrollments.destroy', $enrollment));

        $response->assertRedirect(route('enrollments.index'));
        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
        $this->assertDatabaseCount('enrollment_images', 0);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_authenticated_users_can_view_face_search_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('enrollments.search'))
            ->assertOk();
    }

    public function test_face_search_requires_an_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('enrollments.identify'))
            ->assertSessionHasErrors('image');
    }

    public function test_face_search_reports_when_no_embeddings_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('enrollments.identify'), [
            'image' => UploadedFile::fake()->image('probe.jpg'),
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('enrollments/search')
            ->where('result.candidate_count', 0)
            ->where('result.matches', [])
            ->whereNot('result.error', null));
    }
}
