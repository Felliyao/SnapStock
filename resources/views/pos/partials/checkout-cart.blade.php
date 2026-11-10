<header class="relative border-b border-slate-100 px-5 py-4 text-center">
    <h2 class="text-lg font-extrabold text-slate-800">Checkout</h2>
    <p class="mt-1 text-xs text-slate-500"><span x-text="itemCount"></span> item dalam keranjang</p>
    @if ($closable ?? false)
        <button
            type="button"
            @click="checkoutModalOpen = false"
            aria-label="Tutup keranjang"
            class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
        >×</button>
    @endif
</header>

<div class="grid grid-cols-12 border-b border-slate-100 bg-slate-50 px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-slate-500">
    <span class="col-span-6">Produk</span>
    <span class="col-span-3 text-center">Jumlah</span>
    <span class="col-span-3 text-right">Harga</span>
</div>

<div class="min-h-40 flex-1 divide-y divide-slate-100 overflow-y-auto">
    <template x-for="item in cart" :key="item.id">
        <article class="grid grid-cols-12 items-center gap-1 px-3 py-3 text-sm sm:px-4">
            <div class="col-span-6 flex min-w-0 items-center gap-2">
                <button type="button" @click="removeItem(item)" :aria-label="`Hapus ${item.name}`" class="shrink-0 rounded p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                    <span aria-hidden="true">×</span>
                </button>
                <div class="min-w-0">
                    <p class="truncate text-xs font-bold text-slate-700" x-text="item.name"></p>
                    <p class="text-[11px] text-slate-400" x-text="formatCurrency(item.price)"></p>
                </div>
            </div>
            <div class="col-span-3 flex items-center justify-center gap-1">
                <span class="w-5 text-center text-xs font-bold text-slate-800" x-text="item.qty"></span>
            </div>
            <span class="col-span-3 text-right text-xs font-bold text-slate-700" x-text="formatCurrency(item.price * item.qty)"></span>
        </article>
    </template>

    <div x-show="cart.length === 0" class="flex h-full min-h-48 flex-col items-center justify-center gap-2 px-6 text-center">
        <x-heroicon-o-shopping-cart class="h-8 w-8 text-slate-300" aria-hidden="true" />
        <p class="text-sm font-semibold text-slate-600">Keranjang masih kosong</p>
        <p class="text-xs text-slate-400">Pilih produk untuk memulai pesanan.</p>
    </div>
</div>

<div class="border-t border-slate-100 bg-slate-50/80 p-4 sm:p-5">
    <div class="flex items-center justify-between text-base font-extrabold">
        <span class="text-slate-800">Total</span>
        <span class="text-lg text-emerald-700" x-text="formatCurrency(total)"></span>
    </div>
</div>
