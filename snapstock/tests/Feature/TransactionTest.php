<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_a_transaction(): void
    {
        $this->postJson(route('transactions.store'), [
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertUnauthorized();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_guest_is_redirected_from_transaction_history(): void
    {
        $this->get(route('transactions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_checkout_products_and_stock_is_decremented(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Kopi Susu',
            'price' => '12500.50',
            'stock' => 10,
            'unit' => 'cup',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('transactions.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 30000,
                'total' => '1.00',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Transaksi berhasil disimpan.')
            ->assertJsonPath('redirect', route('transactions.index'));

        $transaction = Transaction::query()->with('items')->firstOrFail();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $user->id,
            'total' => '25001.00',
            'status' => 'completed',
            'payment_method' => 'cash',
            'amount_paid' => '30000.00',
            'change_amount' => '4999.00',
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name' => 'Kopi Susu',
            'unit' => 'cup',
            'quantity' => 2,
            'unit_price' => '12500.50',
            'subtotal' => '25001.00',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8,
        ]);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Kopi Susu')
            ->assertSee('Transaksi #')
            ->assertSee('25001')
            ->assertSee('Tunai');
    }

    public function test_qris_checkout_records_the_payment_amount_and_is_shown_in_history(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Teh Lemon',
            'price' => 7000,
            'stock' => 4,
            'unit' => 'cup',
        ]);

        $this->actingAs($user)
            ->postJson(route('transactions.store'), [
                'payment_method' => 'qris',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ])
            ->assertCreated();

        $transaction = Transaction::query()->firstOrFail();
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'payment_method' => 'qris',
            'total' => '14000.00',
            'amount_paid' => '14000.00',
            'change_amount' => '0.00',
        ]);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('QRIS (simulasi)');
    }

    public function test_checkout_rejects_cash_payment_below_total_without_saving_transaction(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Roti',
            'price' => 10000,
            'stock' => 3,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->postJson(route('transactions.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 9999,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_paid']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);
    }

    public function test_checkout_rejects_insufficient_stock_without_saving_or_changing_stock(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Roti',
            'price' => 8000,
            'stock' => 1,
            'unit' => 'pcs',
        ]);

        $this->actingAs($user)
            ->postJson(route('transactions.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 16000,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 1,
        ]);
    }

    public function test_user_cannot_checkout_another_users_product(): void
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
            ->postJson(route('transactions.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 10000,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);
    }

    public function test_transaction_history_only_shows_the_authenticated_users_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = $user->transactions()->create([
            'total' => 12000,
            'status' => 'completed',
        ]);
        $transaction->items()->create([
            'product_name' => 'Produk Milik Saya',
            'unit' => 'pcs',
            'quantity' => 1,
            'unit_price' => 12000,
            'subtotal' => 12000,
        ]);
        $otherUsersTransaction = $otherUser->transactions()->create([
            'total' => 99000,
            'status' => 'completed',
        ]);
        $otherUsersTransaction->items()->create([
            'product_name' => 'Transaksi Rahasia',
            'unit' => 'pcs',
            'quantity' => 1,
            'unit_price' => 99000,
            'subtotal' => 99000,
        ]);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Produk Milik Saya')
            ->assertSee('12000')
            ->assertDontSee('Transaksi Rahasia')
            ->assertDontSee('99000');
    }

    public function test_user_can_delete_a_transaction_and_stock_is_restored(): void
    {
        $user = User::factory()->create();
        $product = $user->products()->create([
            'name' => 'Kopi Susu',
            'price' => 12500,
            'stock' => 8,
            'unit' => 'cup',
        ]);
        $transaction = $user->transactions()->create([
            'total' => 25000,
            'status' => 'completed',
            'payment_method' => 'cash',
            'amount_paid' => 30000,
            'change_amount' => 5000,
        ]);
        $transaction->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Kopi Susu',
            'unit' => 'cup',
            'quantity' => 2,
            'unit_price' => 12500,
            'subtotal' => 25000,
        ]);

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('status', 'Transaksi berhasil dihapus.');

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10,
        ]);
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = $otherUser->transactions()->create([
            'total' => 12000,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertNotFound();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }
}
