<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_catalog_import_page(): void
    {
        $this->get(route('products.import.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_open_the_import_page_and_download_a_csv_template(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('products.import.create'))
            ->assertRedirect(route('products.create'));

        $this->actingAs($user)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Buat katalog produk')
            ->assertSee('Impor CSV')
            ->assertSee('Unduh template CSV');

        $this->actingAs($user)
            ->get(route('products.import.template'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertStreamedContent("name,category,price,stock,unit\n");
    }

    public function test_user_can_preview_csv_updates_and_import_new_products_without_changing_another_users_catalog(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $existingProduct = $user->products()->create([
            'name' => 'Coffee',
            'category' => 'Old category',
            'price' => 10000,
            'stock' => 2,
            'unit' => 'bag',
        ]);
        $otherUsersProduct = $otherUser->products()->create([
            'name' => 'Coffee',
            'category' => 'Other category',
            'price' => 20000,
            'stock' => 9,
            'unit' => 'bag',
        ]);
        $unchangedProduct = $user->products()->create([
            'name' => 'Oolong',
            'category' => 'Tea',
            'price' => 12000,
            'stock' => 5,
            'unit' => 'bag',
        ]);
        $csv = UploadedFile::fake()->createWithContent(
            'catalog.csv',
            "name,category,price,stock,unit\ncoffee,Drinks,15000,7,bag\nTea,Drinks,8000,4,box\nOolong,Tea,12000,5,bag\n"
        );

        $this->actingAs($user)
            ->post(route('products.import.preview'), ['file' => $csv])
            ->assertOk()
            ->assertSee('Buat katalog produk')
            ->assertSee('Perbarui')
            ->assertSee('Tambah')
            ->assertSee('Drinks')
            ->assertSee('15000')
            ->assertSee('Tidak ada perubahan')
            ->assertSee('name="products[0][name]"', false);

        $this->actingAs($user)
            ->post(route('products.import.store'), [
                'products' => [
                    [
                        'name' => 'coffee',
                        'category' => 'Drinks',
                        'price' => '15000',
                        'stock' => '7',
                        'unit' => 'bag',
                    ],
                    [
                        'name' => 'Tea',
                        'category' => 'Drinks',
                        'price' => '8000',
                        'stock' => '4',
                        'unit' => 'box',
                    ],
                    [
                        'name' => 'Oolong',
                        'category' => 'Tea',
                        'price' => '12000',
                        'stock' => '5',
                        'unit' => 'bag',
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Impor selesai: 1 produk ditambahkan, 1 diperbarui, 1 tidak berubah.');

        $this->assertDatabaseCount('products', 4);
        $this->assertDatabaseHas('products', [
            'id' => $existingProduct->id,
            'user_id' => $user->id,
            'name' => 'coffee',
            'category' => 'Drinks',
            'price' => 15000,
            'stock' => 7,
            'unit' => 'bag',
        ]);
        $this->assertDatabaseHas('products', [
            'user_id' => $user->id,
            'name' => 'Tea',
            'price' => 8000,
            'stock' => 4,
            'image_path' => null,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $unchangedProduct->id,
            'user_id' => $user->id,
            'name' => 'Oolong',
            'price' => 12000,
            'stock' => 5,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $otherUsersProduct->id,
            'user_id' => $otherUser->id,
            'name' => 'Coffee',
            'price' => 20000,
            'stock' => 9,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Impor selesai: 1 produk ditambahkan, 1 diperbarui, 1 tidak berubah.');
    }

    public function test_preview_marks_invalid_csv_rows_and_does_not_import_products(): void
    {
        $user = User::factory()->create();
        $csv = UploadedFile::fake()->createWithContent(
            'catalog.csv',
            "name,price,stock\nProduct,not-a-price,3\n"
        );

        $this->actingAs($user)
            ->post(route('products.import.preview'), ['file' => $csv])
            ->assertOk()
            ->assertSee('Periksa')
            ->assertSee('belum ada produk yang disimpan')
            ->assertDontSee('name="products[0][name]"', false);

        $this->assertSame(0, Product::query()->count());
    }

    public function test_import_rejects_duplicate_names_and_invalid_product_values(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('products.import.store'), [
                'products' => [
                    [
                        'name' => 'Product',
                        'category' => null,
                        'price' => 'not-a-price',
                        'stock' => '2',
                        'unit' => 'pcs',
                    ],
                ],
            ])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['products.0.price']);

        $this->actingAs($user)
            ->post(route('products.import.store'), [
                'products' => [
                    [
                        'name' => 'Product',
                        'category' => null,
                        'price' => '100',
                        'stock' => '2',
                        'unit' => 'pcs',
                    ],
                    [
                        'name' => 'product',
                        'category' => null,
                        'price' => '200',
                        'stock' => '3',
                        'unit' => 'pcs',
                    ],
                ],
            ])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['products.1.name']);

        $this->assertSame(0, Product::query()->count());
    }

    public function test_csv_imported_products_have_no_photo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('products.import.store'), [
                'products' => [
                    [
                        'name' => 'Produk CSV',
                        'category' => null,
                        'price' => '10000',
                        'stock' => '2',
                        'unit' => 'pcs',
                    ],
                ],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('products', [
            'user_id' => $user->id,
            'name' => 'Produk CSV',
            'image_path' => null,
        ]);
    }

    public function test_preview_rejects_a_csv_missing_required_headers(): void
    {
        $user = User::factory()->create();
        $csv = UploadedFile::fake()->createWithContent('catalog.csv', "name,category\nProduct,Drinks\n");

        $this->actingAs($user)
            ->from(route('products.create'))
            ->post(route('products.import.preview'), ['file' => $csv])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['file']);
    }

    public function test_preview_accepts_semicolon_delimited_csv_files(): void
    {
        $user = User::factory()->create();
        $csv = UploadedFile::fake()->createWithContent('catalog.csv', "name;price;stock\nTea;8000;4\n");

        $this->actingAs($user)
            ->post(route('products.import.preview'), ['file' => $csv])
            ->assertOk()
            ->assertSee('Buat katalog produk')
            ->assertSee('Tea')
            ->assertSee('8000')
            ->assertSee('Tambah');
    }
}
