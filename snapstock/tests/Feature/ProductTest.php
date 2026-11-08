<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_management_actions_without_listing_catalog_products(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->products()->create([
            'name' => 'Donat Cokelat',
            'price' => 35000,
            'stock' => 20,
            'unit' => 'box',
        ]);
        $otherUser->products()->create([
            'name' => 'Produk Rahasia',
            'price' => 10000,
            'stock' => 5,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelola Produk')
            ->assertSee('href="'.route('catalogue.index').'"', false)
            ->assertSee('Kelola Transaksi')
            ->assertSee('Kelola Stok')
            ->assertSee('Bahan Baru')
            ->assertDontSee('Persediaan Produk')
            ->assertDontSee('Produk Rahasia');
    }

    public function test_catalogue_displays_only_the_authenticated_users_database_products_without_promotion_tags(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->products()->create([
            'name' => 'Produk Database',
            'category' => 'Minuman',
            'description' => 'Produk yang disimpan di database.',
            'price' => 18000,
            'stock' => 12,
            'unit' => 'cup',
        ]);
        $otherUser->products()->create([
            'name' => 'Produk Pengguna Lain',
            'category' => 'Rahasia',
            'price' => 10000,
            'stock' => 5,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->get(route('catalogue.index'))
            ->assertOk()
            ->assertSee('Produk Database')
            ->assertSee('Edit produk')
            ->assertSee(':href="product.editUrl"', false)
            ->assertDontSee('Tambah produk')
            ->assertDontSee('Tambah transaksi')
            ->assertDontSee('categoryIcon')
            ->assertSee('Minuman')
            ->assertSee('18000')
            ->assertSee('12')
            ->assertDontSee('Produk Pengguna Lain')
            ->assertDontSee('Donat Cokelat Lumer')
            ->assertDontSee('Favorite')
            ->assertDontSee('Special')
            ->assertDontSee('badge')
            ->assertDontSee('hargaCoret')
            ->assertDontSee('isFavorite');
    }

    public function test_guest_is_redirected_from_the_catalogue_page(): void
    {
        $this->get(route('catalogue.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_editing_a_product(): void
    {
        $product = User::factory()->create()->products()->create([
            'name' => 'Produk Privat',
            'price' => 10000,
            'stock' => 3,
            'unit' => 'pcs',
        ]);

        $this->get(route('products.edit', $product))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_edit_their_own_product_from_the_catalogue(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Produk Lama',
            'category' => 'Makanan',
            'description' => 'Deskripsi lama.',
            'price' => 10000,
            'stock' => 3,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('value="Produk Lama"', false)
            ->assertDontSee('name="description"', false)
            ->assertDontSee('Deskripsi lama.');

        $this->actingAs($user)
            ->put(route('products.update', $product), [
                'name' => 'Produk Baru',
                'category' => 'Minuman',
                'price' => '25000',
                'stock' => '8',
                'unit' => 'botol',
            ])
            ->assertRedirect(route('catalogue.index'))
            ->assertSessionHas('status', 'Produk berhasil diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $user->id,
            'name' => 'Produk Baru',
            'category' => 'Minuman',
            'description' => 'Deskripsi lama.',
            'price' => 25000,
            'stock' => 8,
            'unit' => 'botol',
        ]);
    }

    public function test_user_cannot_edit_another_users_product(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $otherUser->products()->create([
            'name' => 'Produk Privat',
            'price' => 10000,
            'stock' => 3,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->get(route('products.edit', $product))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('products.update', $product), [
                'name' => 'Produk Diretas',
                'price' => '1',
                'stock' => '0',
                'unit' => 'pcs',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $otherUser->id,
            'name' => 'Produk Privat',
            'price' => 10000,
            'stock' => 3,
        ]);
    }

    public function test_product_edit_validates_fields_before_saving(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Produk Lama',
            'price' => 10000,
            'stock' => 3,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->from(route('products.edit', $product))
            ->put(route('products.update', $product), [
                'name' => '',
                'price' => 'bukan harga',
                'stock' => '-1',
                'unit' => 'pcs',
            ])
            ->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors(['name', 'price', 'stock']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produk Lama',
            'price' => 10000,
            'stock' => 3,
        ]);
    }

    public function test_user_can_restock_their_own_product_from_the_dashboard(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Donat Cokelat',
            'price' => 35000,
            'stock' => 20,
            'unit' => 'box',
        ]);

        $this->actingAs($user)
            ->post(route('products.stock.store'), [
                'product_id' => $product->id,
                'quantity' => 5,
                'stock_action' => 'restock',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Stok Donat Cokelat berhasil ditambah 5 box. Stok sekarang 25 box.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $user->id,
            'stock' => 25,
        ]);
    }

    public function test_user_cannot_restock_another_users_product(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $otherUser->products()->create([
            'name' => 'Produk Privat',
            'price' => 10000,
            'stock' => 5,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->post(route('products.stock.store'), [
                'product_id' => $product->id,
                'quantity' => 10,
                'stock_action' => 'restock',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'user_id' => $otherUser->id,
            'stock' => 5,
        ]);
    }

    public function test_restock_rejects_quantities_that_would_exceed_the_stock_limit(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Produk Hampir Penuh',
            'price' => 10000,
            'stock' => 4294967295,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('products.stock.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
                'stock_action' => 'restock',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 4294967295,
        ]);
    }

    public function test_restock_rejects_zero_quantity_without_changing_stock(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Kopi Susu',
            'price' => 18000,
            'stock' => 12,
            'unit' => 'cup',
        ]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('products.stock.store'), [
                'product_id' => $product->id,
                'quantity' => 0,
                'stock_action' => 'restock',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 12,
        ]);
    }

    public function test_guest_cannot_restock_products(): void
    {
        $this->post(route('products.stock.store'), [
            'product_id' => 1,
            'quantity' => 1,
            'stock_action' => 'restock',
        ])->assertRedirect(route('login'));
    }

    public function test_sales_products_and_raw_materials_are_kept_in_separate_tables(): void
    {
        $user = User::factory()->create();

        $user->products()->create([
            'name' => 'Donat Cokelat',
            'category' => 'Makanan',
            'price' => 35000,
            'stock' => 20,
            'unit' => 'box',
        ]);

        $user->rawMaterials()->create([
            'name' => 'Tepung Terigu',
            'category' => 'Tepung & Pati',
            'stock' => 15,
            'unit' => 'kg',
            'location' => 'Gudang Utama',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Donat Cokelat')
            ->assertSee('Tepung Terigu');

        $this->actingAs($user)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Tepung Terigu')
            ->assertSee('Tepung & Pati')
            ->assertDontSee('Tambah Bahan Baku')
            ->assertDontSee('Donat Cokelat');

        $this->assertDatabaseHas('products', ['name' => 'Donat Cokelat']);
        $this->assertDatabaseHas('raw_materials', ['name' => 'Tepung Terigu', 'stock' => 15]);
    }

    public function test_user_can_create_update_and_delete_their_raw_materials(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('materials.store'), [
                'name' => 'Tepung Terigu',
                'category' => 'Tepung & Pati',
                'stock' => 15,
                'unit' => 'kg',
                'location' => 'Gudang Utama',
                'expiration_date' => '2027-01-01',
            ])
            ->assertRedirect(route('inventory.index'));

        $rawMaterial = $user->rawMaterials()->firstOrFail();
        $this->assertDatabaseHas('raw_materials', [
            'id' => $rawMaterial->id,
            'name' => 'Tepung Terigu',
            'expiration_date' => '2027-01-01',
        ]);

        $this->actingAs($user)
            ->put(route('materials.update', $rawMaterial), [
                'name' => 'Tepung Protein Tinggi',
                'category' => 'Tepung & Pati',
                'stock' => 12,
                'unit' => 'kg',
                'location' => 'Rak A',
                'expiration_date' => '2027-05-01',
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('raw_materials', [
            'id' => $rawMaterial->id,
            'name' => 'Tepung Protein Tinggi',
            'stock' => 12,
            'location' => 'Rak A',
            'expiration_date' => '2027-05-01',
        ]);

        $this->actingAs($user)
            ->delete(route('materials.destroy', $rawMaterial))
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseMissing('raw_materials', ['id' => $rawMaterial->id]);
    }

    public function test_user_cannot_update_or_delete_another_users_raw_material(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $rawMaterial = $otherUser->rawMaterials()->create([
            'name' => 'Tepung Privat',
            'stock' => 5,
            'unit' => 'kg',
        ]);

        $this->actingAs($user)
            ->put(route('materials.update', $rawMaterial), [
                'name' => 'Diubah',
                'stock' => 10,
                'unit' => 'kg',
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('materials.destroy', $rawMaterial))
            ->assertNotFound();

        $this->assertDatabaseHas('raw_materials', [
            'id' => $rawMaterial->id,
            'user_id' => $otherUser->id,
            'name' => 'Tepung Privat',
            'stock' => 5,
        ]);
    }

    public function test_user_can_restock_their_raw_material_without_changing_sales_product_stock(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Donat Cokelat',
            'price' => 35000,
            'stock' => 20,
            'unit' => 'box',
        ]);
        $rawMaterial = $user->rawMaterials()->create([
            'name' => 'Tepung Terigu',
            'stock' => 15,
            'unit' => 'kg',
        ]);

        $this->actingAs($user)
            ->post(route('materials.stock.store'), [
                'raw_material_id' => $rawMaterial->id,
                'quantity' => 5,
                'stock_action' => 'restock',
                'expiration_date' => '2027-06-01',
            ])
            ->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('raw_materials', [
            'id' => $rawMaterial->id,
            'stock' => 20,
            'expiration_date' => '2027-06-01',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 20,
        ]);
    }

    public function test_pos_displays_only_the_authenticated_users_products_and_starts_with_an_empty_cart(): void
    {
        $user = User::factory()->create(['name' => 'Kasir Utama']);
        $otherUser = User::factory()->create();

        $user->products()->create([
            'name' => 'Kopi Susu',
            'category' => 'Minuman',
            'price' => 18000,
            'stock' => 12,
            'unit' => 'cup',
        ]);
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tambah Transaksi')
            ->assertSee(route('pos.index'));

        $otherUser->products()->create([
            'name' => 'Produk Privat',
            'price' => 10000,
            'stock' => 5,
            'unit' => 'pcs',
        ]);

        $posResponse = $this->actingAs($user)->get(route('pos.index'));

        $posResponse
            ->assertOk()
            ->assertSee('Kopi Susu')
            ->assertDontSee('categoryIcon')
            ->assertSee('@click="addToCart(product)"', false)
            ->assertSee('@click="decreaseProductQuantity(product)"', false)
            ->assertSee('x-text="quantityFor(product)"', false)
            ->assertDontSee('@click="increaseQty(item)"', false)
            ->assertDontSee('@click="decreaseQty(item)"', false)
            ->assertSee('Kasir Utama')
            ->assertSee('Dashboard')
            ->assertDontSee('Produk Privat')
            ->assertDontSee('Costa Coffee')
            ->assertDontSee('Risus Fringilla')
            ->assertSee('currency: \'IDR\'', false)
            ->assertDontSee('discountPercent')
            ->assertDontSee('taxPercent')
            ->assertDontSee('Diskon (%)')
            ->assertDontSee('Pajak')
            ->assertSee('Semua')
            ->assertSee('Checkout')
            ->assertDontSee('Tahan pesanan')
            ->assertDontSee('Show More')
            ->assertSee('@click="checkout()"', false)
            ->assertSee(route('transactions.store'), false)
            ->assertDontSee('Checkout belum terhubung', false);

        $this->assertNull($posResponse->viewData('products')->first()['image']);
    }

    public function test_guest_is_redirected_from_the_pos_page(): void
    {
        $this->get(route('pos.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_save_a_product_and_see_it_on_the_dashboard(): void
    {
        $user = User::factory()->create();
        $data = [
            'name' => 'Donat Cokelat',
            'category' => 'Makanan',
            'price' => '35000',
            'stock' => '20',
            'unit' => 'box',
        ];

        $this->actingAs($user)
            ->post(route('products.store'), $data)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('products', [
            'user_id' => $user->id,
            'name' => 'Donat Cokelat',
            'price' => 35000,
            'stock' => 20,
            'image_path' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Produk berhasil ditambahkan.');
    }

    public function test_product_fields_are_validated_before_saving(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('products.store'), [
                'name' => '',
                'price' => 'bukan harga',
                'stock' => '-1',
                'unit' => 'pcs',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['name', 'price', 'stock']);

        $this->assertSame(0, Product::query()->count());
    }

    public function test_manual_product_entry_displays_individual_product_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Isi manual')
            ->assertSee('action="'.route('products.store').'"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="category"', false)
            ->assertSee('name="price"', false)
            ->assertSee('name="stock"', false)
            ->assertSee('name="unit"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="image"', false)
            ->assertDontSee('name="description"', false);
    }

    public function test_user_can_save_a_manual_product_photo_and_see_it_in_the_catalogue(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('donat.jpg');

        $this->actingAs($user)
            ->post(route('products.store'), [
                'name' => 'Donat Foto',
                'price' => '35000',
                'stock' => '20',
                'unit' => 'box',
                'image' => $image,
            ])
            ->assertRedirect(route('dashboard'));

        $product = Product::query()->where('name', 'Donat Foto')->firstOrFail();

        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_path' => $product->image_path,
        ]);

        $imageUrl = Storage::disk('public')->url($product->image_path);
        $response = $this->actingAs($user)->get(route('catalogue.index'));

        $response
            ->assertOk()
            ->assertSee(':src="product.image"', false)
            ->assertViewHas('products', fn ($products): bool => $products->first()['image'] === $imageUrl);

        $this->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee(':src="product.image"', false)
            ->assertViewHas('products', fn ($products): bool => $products->first()['image'] === $imageUrl);
    }

    public function test_manual_product_entry_rejects_non_image_uploads(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => 'Produk Invalid',
                'price' => '10000',
                'stock' => '1',
                'unit' => 'pcs',
                'image' => UploadedFile::fake()->createWithContent('not-an-image.txt', 'not an image'),
            ])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors(['image']);

        $this->assertSame(0, Product::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
