<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUnitMaterialTest extends TestCase
{
    use RefreshDatabase;

    // FR-A16, AC-45, NFR-18: rich material is sanitized before storage and safe to preview.
    public function test_admin_material_html_is_sanitized_and_previewed(): void
    {
        $admin = $this->admin();
        $unit = $this->unit($admin);

        $this->actingAs($admin)
            ->put(route('admin.modules.units.update', [$unit->module, $unit]), [
                'title' => $unit->title,
                'learning_objective' => $unit->learning_objective,
                'video_url' => $unit->video_url,
                'duration_minutes' => $unit->duration_minutes,
                'sequence_order' => $unit->sequence_order,
                'reading_content' => '<p>Materi <strong>tebal</strong></p><script>alert(1)</script><img src="https://evil.test/a.jpg" onerror="alert(1)">',
            ])
            ->assertRedirect();

        $savedContent = $unit->fresh()->reading_content;
        $this->assertStringContainsString('<strong>tebal</strong>', $savedContent);
        $this->assertStringNotContainsString('<script', $savedContent);
        $this->assertStringNotContainsString('evil.test', $savedContent);
        $this->assertStringNotContainsString('onerror', $savedContent);

        $this->get(route('admin.units.preview', $unit))
            ->assertOk()
            ->assertSee('<strong>tebal</strong>', false)
            ->assertDontSee('alert(1)', false);
    }

    // FR-A16, AC-47: Admin uploads are type-checked and stored outside the public disk.
    public function test_admin_can_upload_and_view_a_private_material_image(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $unit = $this->unit($admin);

        $response = $this->actingAs($admin)->postJson(
            route('admin.units.material-images.store', $unit),
            ['image' => $this->pngUpload()],
        );

        $response->assertCreated()->assertJsonStructure(['url', 'alt']);
        $imageUrl = $response->json('url');
        $filename = basename($imageUrl);

        Storage::disk('local')->assertExists("unit-materials/{$unit->id}/{$filename}");
        Storage::disk('public')->assertMissing("unit-materials/{$unit->id}/{$filename}");
        $this->get($imageUrl)->assertOk()->assertHeader('content-type', 'image/png');

        $this->put(route('admin.modules.units.update', [$unit->module, $unit]), [
            'title' => $unit->title,
            'learning_objective' => $unit->learning_objective,
            'video_url' => $unit->video_url,
            'duration_minutes' => $unit->duration_minutes,
            'sequence_order' => $unit->sequence_order,
            'reading_content' => '<p>Materi bergambar<img src="'.$imageUrl.'" alt="Materi"></p>',
        ])->assertRedirect();

        $this->assertStringContainsString($imageUrl, $unit->fresh()->reading_content);
        $this->get(route('admin.units.preview', $unit))
            ->assertOk()
            ->assertSee($imageUrl, false);
    }

    // FR-A16, AC-47: unsupported image formats and excess image files are rejected.
    public function test_material_image_upload_rejects_invalid_type_and_more_than_ten_images(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $unit = $this->unit($admin);

        $this->actingAs($admin)
            ->postJson(route('admin.units.material-images.store', $unit), [
                'image' => UploadedFile::fake()->createWithContent('note.txt', 'not an image'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);

        foreach (range(1, 10) as $index) {
            Storage::disk('local')->put("unit-materials/{$unit->id}/{$index}.png", 'image');
        }

        $this->postJson(route('admin.units.material-images.store', $unit), ['image' => $this->pngUpload()])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Materi maksimal berisi 10 gambar. Hapus gambar lama dan simpan materi terlebih dahulu.');
    }

    // FR-A16, AC-47: reject content that exceeds the per-unit image limit.
    public function test_material_html_cannot_reference_more_than_ten_images(): void
    {
        $admin = $this->admin();
        $unit = $this->unit($admin);

        $this->actingAs($admin)
            ->put(route('admin.modules.units.update', [$unit->module, $unit]), [
                'title' => $unit->title,
                'learning_objective' => $unit->learning_objective,
                'video_url' => $unit->video_url,
                'duration_minutes' => $unit->duration_minutes,
                'sequence_order' => $unit->sequence_order,
                'reading_content' => str_repeat('<img src="image.png">', 11),
            ])
            ->assertSessionHasErrors([
                'reading_content' => 'Materi singkat maksimal berisi 10 gambar.',
            ]);
    }

    // FR-A16, FR-L11: only a Learner may fetch referenced images from a published module.
    public function test_learner_image_access_requires_a_published_module_and_admin_is_not_a_learner(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $unit = $this->unit($admin, ['status' => 'published']);
        $filename = '123e4567-e89b-12d3-a456-426614174000.png';
        Storage::disk('local')->put("unit-materials/{$unit->id}/{$filename}", base64_decode($this->pngBase64()));
        $unit->update([
            'reading_content' => '<p><img src="'.route('admin.units.material-images.show', [$unit, $filename], false).'"></p>',
        ]);
        $learner = User::factory()->create(['role' => Role::Learner->value]);

        $this->actingAs($learner)
            ->get("/api/units/{$unit->id}/material/images/{$filename}")
            ->assertOk();

        $unit->module->update(['status' => 'draft']);
        $this->get("/api/units/{$unit->id}/material/images/{$filename}")->assertNotFound();

        $this->actingAs($admin)
            ->get("/api/units/{$unit->id}/material/images/{$filename}")
            ->assertForbidden();
    }

    // FR-S05, AB-13: Learner and Superadmin cannot use the Admin image upload route.
    public function test_non_admin_roles_cannot_upload_material_images(): void
    {
        $moduleOwner = $this->admin();
        $unit = $this->unit($moduleOwner);

        foreach ([Role::Learner, Role::Superadmin] as $role) {
            $user = User::factory()->create(['role' => $role->value]);
            $this->actingAs($user)
                ->postJson(route('admin.units.material-images.store', $unit), ['image' => $this->pngUpload()])
                ->assertForbidden();
        }
    }

    // FR-A16: images no longer referenced are removed after a successful material update.
    public function test_unused_material_images_are_cleaned_after_update(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $unit = $this->unit($admin);
        $filename = '123e4567-e89b-12d3-a456-426614174000.png';
        Storage::disk('local')->put("unit-materials/{$unit->id}/{$filename}", 'image');

        $this->actingAs($admin)
            ->put(route('admin.modules.units.update', [$unit->module, $unit]), [
                'title' => $unit->title,
                'learning_objective' => $unit->learning_objective,
                'video_url' => $unit->video_url,
                'duration_minutes' => $unit->duration_minutes,
                'sequence_order' => $unit->sequence_order,
                'reading_content' => '<p>Materi diperbarui.</p>',
            ])
            ->assertRedirect();

        Storage::disk('local')->assertMissing("unit-materials/{$unit->id}/{$filename}");
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'reading_content' => '<p>Materi diperbarui.</p>']);
    }

    // FR-A16: deleting a unit also removes its private material files.
    public function test_deleting_unit_removes_its_material_images(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $unit = $this->unit($admin);
        Storage::disk('local')->put("unit-materials/{$unit->id}/image.png", 'image');

        $this->actingAs($admin)
            ->delete(route('admin.modules.units.destroy', [$unit->module, $unit]))
            ->assertRedirect();

        Storage::disk('local')->assertMissing("unit-materials/{$unit->id}/image.png");
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => Role::Admin->value,
            'is_temp_password' => false,
        ]);
    }

    private function unit(User $admin, array $moduleAttributes = []): Unit
    {
        $module = Module::query()->create(array_merge([
            'title' => 'Modul Materi',
            'description' => '',
            'learning_objectives' => null,
            'status' => 'draft',
            'created_by' => $admin->id,
        ], $moduleAttributes));

        return $module->units()->create([
            'title' => 'Unit Materi',
            'learning_objective' => 'Memahami materi unit.',
            'video_url' => 'https://example.test/video.mp4',
            'duration' => 60,
            'reading_content' => '',
            'sequence_order' => 1,
        ]);
    }

    private function pngUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('materi.png', base64_decode($this->pngBase64()));
    }

    private function pngBase64(): string
    {
        return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASsJTYQAAAAASUVORK5CYII=';
    }
}
