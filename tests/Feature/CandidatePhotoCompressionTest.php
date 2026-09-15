<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\ElectionSetting;
use App\Models\User;
use App\Support\ImageCompressor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidatePhotoCompressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        ElectionSetting::create([
            'id' => 1,
            'school_name' => 'SMA Kebangsaan',
            'election_title' => 'Pemilihan Ketua OSIS',
            'academic_year' => '2026/2027',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@pilketos.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
    }

    public function test_uploaded_candidate_photo_is_compressed_under_200kb(): void
    {
        Storage::fake('public');

        // Create a fake large image (1600x2000 pixels)
        $file = UploadedFile::fake()->image('large_candidate.jpg', 1600, 2000);

        $response = $this->actingAs($this->admin)->post(route('admin.candidates.store'), [
            'candidate_number' => 1,
            'leader_name' => 'Calon Kompres',
            'co_leader_name' => 'Wakil Kompres',
            'vision' => 'Visi uji coba',
            'mission' => 'Misi uji coba',
            'photo' => $file,
        ]);

        $response->assertRedirect(route('admin.candidates.index'));

        $candidate = Candidate::where('candidate_number', 1)->first();
        $this->assertNotNull($candidate);
        $this->assertNotNull($candidate->photo_path);

        Storage::disk('public')->assertExists($candidate->photo_path);

        // Assert that the stored image size is under or equal to 200 KB (204,800 bytes)
        $storedSize = Storage::disk('public')->size($candidate->photo_path);
        $this->assertLessThanOrEqual(ImageCompressor::DEFAULT_MAX_BYTES, $storedSize);
    }

    public function test_updating_candidate_photo_compresses_and_removes_old_photo(): void
    {
        Storage::fake('public');

        $candidate = Candidate::create([
            'candidate_number' => 2,
            'leader_name' => 'Calon Lama',
            'vision' => 'Visi lama',
            'mission' => 'Misi lama',
            'photo_path' => 'candidates/old_photo.jpg',
        ]);

        Storage::disk('public')->put('candidates/old_photo.jpg', 'dummy content');

        $newFile = UploadedFile::fake()->image('new_photo.png', 1200, 1600);

        $response = $this->actingAs($this->admin)->put(route('admin.candidates.update', $candidate), [
            'candidate_number' => 2,
            'leader_name' => 'Calon Baru',
            'vision' => 'Visi baru',
            'mission' => 'Misi baru',
            'photo' => $newFile,
        ]);

        $response->assertRedirect(route('admin.candidates.index'));

        // Old photo should be deleted
        Storage::disk('public')->assertMissing('candidates/old_photo.jpg');

        $candidate->refresh();
        $this->assertNotEquals('candidates/old_photo.jpg', $candidate->photo_path);
        Storage::disk('public')->assertExists($candidate->photo_path);

        $storedSize = Storage::disk('public')->size($candidate->photo_path);
        $this->assertLessThanOrEqual(ImageCompressor::DEFAULT_MAX_BYTES, $storedSize);
    }
}
