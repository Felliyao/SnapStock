<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RawMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_receipt_scan(): void
    {
        $this->post(route('materials.scan-receipt'), [
            'image' => UploadedFile::fake()->image('nota.jpg'),
        ])->assertRedirect(route('login'));
    }

    public function test_receipt_scan_requires_an_image(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('materials.scan-receipt'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_receipt_scan_returns_extracted_items(): void
    {
        Http::preventStrayRequests();
        config(['services.openai.api_key' => 'test-api-key']);
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "```json\n".json_encode([
                                'items' => [
                                    ['name' => 'Tepung Terigu', 'quantity' => 2, 'unit' => 'kg'],
                                    ['name' => 'Minyak Goreng', 'quantity' => 1, 'unit' => 'botol'],
                                ],
                            ])."\n```",
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('materials.scan-receipt'), [
                'image' => UploadedFile::fake()->image('nota.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('items.0.name', 'Tepung Terigu')
            ->assertJsonPath('items.0.quantity', 2)
            ->assertJsonPath('items.1.unit', 'botol');
    }

    public function test_batch_restock_updates_multiple_materials(): void
    {
        $user = User::factory()->create();
        $flour = $user->rawMaterials()->create(['name' => 'Tepung Terigu', 'stock' => 1, 'unit' => 'kg']);
        $oil = $user->rawMaterials()->create(['name' => 'Minyak Goreng', 'stock' => 2, 'unit' => 'botol']);

        $this->actingAs($user)
            ->post(route('materials.restock-batch'), [
                'items' => [
                    ['raw_material_id' => $flour->id, 'quantity' => '2.5'],
                    ['raw_material_id' => $oil->id, 'quantity' => '3', 'expiration_date' => '2027-01-01'],
                ],
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertEquals(3.5, (float) $flour->fresh()->stock);
        $this->assertEquals(5.0, (float) $oil->fresh()->stock);
        $this->assertSame('2027-01-01', $oil->fresh()->expiration_date?->toDateString());
    }

    public function test_batch_restock_rejects_materials_from_other_users(): void
    {
        $user = User::factory()->create();
        $otherMaterial = User::factory()->create()->rawMaterials()->create([
            'name' => 'Bahan Orang',
            'stock' => 1,
            'unit' => 'kg',
        ]);

        $this->actingAs($user)
            ->post(route('materials.restock-batch'), [
                'items' => [
                    ['raw_material_id' => $otherMaterial->id, 'quantity' => '1'],
                ],
            ])
            ->assertNotFound();

        $this->assertEquals(1.0, (float) $otherMaterial->fresh()->stock);
    }
}
