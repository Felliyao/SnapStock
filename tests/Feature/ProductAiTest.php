<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_ai_catalog_page(): void
    {
        $this->get(route('products.ai-create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_open_the_product_creation_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Buat katalog produk')
            ->assertSee('Foto produk, kemasan, atau nota')
            ->assertSee('Simpan katalog');
    }

    public function test_ai_generation_returns_products_from_gemini(): void
    {
        Http::preventStrayRequests();
        config([
            'services.gemini.api_key' => 'test-api-key',
            'services.gemini.model' => 'gpt-4o-mini',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/openai/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'products' => [
                                    [
                                        'name' => 'Donat Cokelat',
                                        'category' => 'Donat',
                                        'price' => 35000,
                                        'stock' => 20,
                                        'unit' => 'box',
                                    ],
                                ],
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                ],
            ]),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('products.ai-generate'), [
                'mode' => 'text',
                'prompt' => 'Jualan donat cokelat',
            ])
            ->assertOk()
            ->assertJsonPath('products.0.name', 'Donat Cokelat')
            ->assertJsonPath('products.0.price', 35000)
            ->assertJsonPath('products.0.stock', 20);

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer '.config('services.gemini.api_key'))
            && data_get($request->data(), 'messages.1.content.0.text') === 'Jualan donat cokelat'
        );
    }

    public function test_ai_generation_returns_a_clear_error_when_api_key_is_missing(): void
    {
        Http::preventStrayRequests();
        config(['services.gemini.api_key' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('products.ai-generate'), [
                'mode' => 'text',
                'prompt' => 'Jualan donat',
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', 'Fitur AI belum dikonfigurasi. Tambahkan GEMINI_API_KEY pada file .env.');

        $this->assertSame(0, Product::query()->count());
    }

    public function test_ai_generation_reports_an_invalid_gemini_api_key(): void
    {
        Http::preventStrayRequests();
        config(['services.gemini.api_key' => 'invalid-api-key']);
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/openai/chat/completions' => Http::response([], 401),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('products.ai-generate'), [
                'mode' => 'text',
                'prompt' => 'Jualan donat',
            ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Kunci AI tidak valid. Periksa konfigurasi GEMINI_API_KEY.');
    }

    public function test_ai_generation_can_identify_a_product_from_a_photo(): void
    {
        Http::preventStrayRequests();
        config([
            'services.gemini.api_key' => 'test-api-key',
            'services.gemini.model' => 'gpt-4o-mini',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/openai/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'products' => [
                                    [
                                        'name' => 'Tepung Terigu',
                                        'category' => 'Bahan Baku',
                                        'price' => 14000,
                                        'stock' => 10,
                                        'unit' => 'pcs',
                                    ],
                                ],
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                ],
            ]),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('products.ai-generate'), [
                'mode' => 'scan',
                'image' => UploadedFile::fake()->image('product.png'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('products.0.name', 'Tepung Terigu');

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions'
            && str_starts_with(
                (string) data_get($request->data(), 'messages.1.content.1.image_url.url'),
                'data:image/'
            )
            && str_contains(
                (string) data_get($request->data(), 'messages.1.content.0.text'),
                'termasuk produk tanpa kemasan'
            )
            && str_contains(
                (string) data_get($request->data(), 'messages.0.content'),
                'Untuk identitas yang tidak pasti'
            )
        );
    }

    public function test_ai_generation_requires_a_photo_in_scan_mode(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('products.ai-generate'), ['mode' => 'scan'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_user_can_save_ai_generated_products_to_their_catalog(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('products.ai-store'), [
                'products' => [
                    [
                        'name' => 'Donat Cokelat',
                        'category' => 'Donat',
                        'price' => 35000,
                        'stock' => 20,
                        'unit' => 'box',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Katalog berhasil disimpan.')
            ->assertJsonPath('redirect', route('dashboard'));

        $this->assertDatabaseHas('products', [
            'user_id' => $user->id,
            'name' => 'Donat Cokelat',
            'category' => 'Donat',
            'price' => 35000,
            'stock' => 20,
            'unit' => 'box',
            'image_path' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Donat Cokelat');
    }

    public function test_ai_catalog_save_validates_products_before_persisting(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('products.ai-store'), [
                'products' => [
                    [
                        'name' => '',
                        'price' => 'not-a-price',
                        'stock' => -1,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'products.0.name',
                'products.0.price',
                'products.0.stock',
                'products.0.unit',
            ]);

        $this->assertSame(0, Product::query()->count());
    }

    public function test_ai_created_products_have_no_photo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('products.ai-store'), [
                'products' => [
                    [
                        'name' => 'Produk AI',
                        'category' => 'Makanan',
                        'price' => 10000,
                        'stock' => 2,
                        'unit' => 'pcs',
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'user_id' => $user->id,
            'name' => 'Produk AI',
            'image_path' => null,
        ]);
    }

    public function test_user_can_save_ai_products_with_a_photo_and_raw_materials(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $rawMaterial = $user->rawMaterials()->create([
            'name' => 'Tepung Terigu',
            'stock' => 10,
            'unit' => 'kg',
        ]);

        $this->actingAs($user)
            ->post(route('products.ai-store'), [
                'products' => [
                    [
                        'name' => 'Donat Cokelat',
                        'category' => 'Donat',
                        'price' => '35000',
                        'stock' => '20',
                        'unit' => 'box',
                        'image' => UploadedFile::fake()->image('donat.jpg'),
                        'raw_materials' => [
                            [
                                'raw_material_id' => $rawMaterial->id,
                                'quantity_per_product' => '0.125',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Katalog berhasil disimpan.');

        $product = $user->products()->firstOrFail();

        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertDatabaseHas('product_raw_material', [
            'product_id' => $product->id,
            'raw_material_id' => $rawMaterial->id,
            'quantity_per_product' => '0.125',
        ]);
    }
}
