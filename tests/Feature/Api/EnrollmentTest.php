<?php

namespace Tests\Feature\Api;

use App\Models\Enrollments;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_gate_user_can_list_create_show_update_and_delete_enrollments(): void
    {
        Storage::fake('public');

        $user = User::factory()->gate()->create();
        $token = $user->createToken('mobile')['plainTextToken'];

        $this->withToken($token)
            ->getJson('/api/enrollments')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $create = $this->withToken($token)->postJson('/api/enrollments', [
            'name' => 'Aung Aung',
            'father_name' => 'U Maung',
            'mother_name' => 'Daw Mya',
            'nrc' => '12/ABC(N)123456',
            'is_f_alive' => true,
            'is_m_alive' => true,
            'remark' => 'API',
            'address' => 'Yangon',
            'images' => [
                UploadedFile::fake()->image('face1.jpg'),
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Aung Aung')
            ->assertJsonCount(1, 'data.images');

        $enrollmentId = $create->json('data.id');

        $this->withToken($token)
            ->getJson("/api/enrollments/{$enrollmentId}")
            ->assertOk()
            ->assertJsonPath('data.address', 'Yangon');

        $this->withToken($token)
            ->putJson("/api/enrollments/{$enrollmentId}", [
                'name' => 'Updated Name',
                'father_name' => 'U Maung',
                'mother_name' => 'Daw Mya',
                'nrc' => '12/ABC(N)123456',
                'is_f_alive' => false,
                'is_m_alive' => true,
                'remark' => null,
                'address' => 'Mandalay',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.address', 'Mandalay');

        $imageId = $create->json('data.images.0.id');

        $this->withToken($token)
            ->deleteJson("/api/enrollments/{$enrollmentId}/images/{$imageId}")
            ->assertOk();

        $this->withToken($token)
            ->deleteJson("/api/enrollments/{$enrollmentId}")
            ->assertOk();

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollmentId]);
    }

    public function test_enrollments_index_supports_search_and_pagination(): void
    {
        $user = User::factory()->gate()->create();
        $token = $user->createToken('mobile')['plainTextToken'];

        Enrollments::query()->create([
            'name' => 'Aung Aung',
            'father_name' => 'U Maung',
            'mother_name' => 'Daw Mya',
            'nrc' => '12/ABC(N)123456',
            'is_f_alive' => true,
            'is_m_alive' => true,
            'address' => 'Yangon',
        ]);

        Enrollments::query()->create([
            'name' => 'Someone Else',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'nrc' => null,
            'is_f_alive' => true,
            'is_m_alive' => true,
            'address' => 'Mandalay',
        ]);

        $this->withToken($token)
            ->getJson('/api/enrollments?search=Aung&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.name', 'Aung Aung');
    }

    public function test_identify_endpoints_require_files(): void
    {
        $user = User::factory()->gate()->create();
        $token = $user->createToken('mobile')['plainTextToken'];

        $this->withToken($token)
            ->postJson('/api/enrollments/identify', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);

        $this->withToken($token)
            ->postJson('/api/enrollments/identify-video', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['video']);
    }
}
