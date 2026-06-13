<?php

namespace Tests\Feature\Api;

use App\Models\AdministrativeRequest;
use App\Models\CitizenReport;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pièces jointes : seul le propriétaire (ou le staff) peut joindre un fichier à
 * son signalement / sa démarche. Photos (jpg/png) ou PDF, 5 Mo max.
 */
class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private const TESTING_SECRET = 'super-secret-test-key-ulky-2026-abcdefgh';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.clerk.testing_secret' => self::TESTING_SECRET]);
        $this->seed(RolesSeeder::class);
        Storage::fake('public');
    }

    private function makeJwt(string $clerkId): string
    {
        return JWT::encode([
            'sub' => $clerkId, 'iat' => time(), 'exp' => time() + 3600, 'iss' => 'https://clerk.test',
        ], self::TESTING_SECRET, 'HS256');
    }

    private function citizen(string $clerkId): User
    {
        $u = User::factory()->create(['clerk_id' => $clerkId]);
        $u->assignRole('citizen');

        return $u;
    }

    private function reportFor(User $user): CitizenReport
    {
        return CitizenReport::create([
            'user_id' => $user->id, 'category' => 'voirie', 'title' => 'Test', 'status' => 'new',
        ]);
    }

    public function test_le_proprietaire_joint_une_photo_a_son_signalement(): void
    {
        $citizen = $this->citizen('user_att_owner');
        $report = $this->reportFor($citizen);

        $response = $this->withToken($this->makeJwt('user_att_owner'))
            ->post("/api/v1/reports/{$report->id}/attachments", [
                'file' => UploadedFile::fake()->image('degat.jpg', 800, 600),
            ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('data.original_name', 'degat.jpg');

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => CitizenReport::class,
            'attachable_id' => $report->id,
            'uploaded_by' => $citizen->id,
        ]);

        $path = $report->attachments()->first()->path;
        Storage::disk('public')->assertExists($path);
    }

    public function test_un_autre_citoyen_ne_peut_pas_joindre_a_mon_signalement(): void
    {
        $owner = $this->citizen('user_att_a');
        $report = $this->reportFor($owner);
        $this->citizen('user_att_b');

        $this->withToken($this->makeJwt('user_att_b'))
            ->post("/api/v1/reports/{$report->id}/attachments", [
                'file' => UploadedFile::fake()->image('x.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(403);
    }

    public function test_un_type_de_fichier_invalide_est_refuse(): void
    {
        $citizen = $this->citizen('user_att_bad');
        $report = $this->reportFor($citizen);

        $this->withToken($this->makeJwt('user_att_bad'))
            ->post("/api/v1/reports/{$report->id}/attachments", [
                'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_le_proprietaire_joint_un_document_a_sa_demarche(): void
    {
        $citizen = $this->citizen('user_att_dem');
        $demarche = AdministrativeRequest::create([
            'user_id' => $citizen->id, 'type' => 'acte', 'title' => 'Acte', 'status' => 'submitted',
        ]);

        $this->withToken($this->makeJwt('user_att_dem'))
            ->post("/api/v1/requests/{$demarche->id}/attachments", [
                'file' => UploadedFile::fake()->create('justificatif.pdf', 50, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(201);

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => AdministrativeRequest::class,
            'attachable_id' => $demarche->id,
        ]);
    }
}
