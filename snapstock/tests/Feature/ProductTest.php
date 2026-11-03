<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_only_the_authenticated_users_products(): void
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
            ->assertSee('Donat Cokelat')
            ->assertDontSee('Produk Rahasia');
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
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Donat Cokelat')
            ->assertSee('Makanan')
            ->assertSee('35.000');
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
}
