<x-app-layout>
    <div
        class="bg-emerald-50/30"
        x-data="{
            products: @js($products),
            checkoutUrl: '{{ route('transactions.store') }}',
            csrfToken: '{{ csrf_token() }}',
            cart: [],
            searchQuery: '',
            selectedCategory: 'all',
            paymentMethod: 'cash',
            amountReceived: '',
            paymentModalOpen: false,
            checkoutModalOpen: false,
            notice: '',
            isCheckingOut: false,

            get categories() {
                const categories = [...new Set(this.products.map(product => product.category))];

                return [
                    { id: 'all', name: 'Semua' },
                    ...categories.map(name => ({ id: name, name })),
                ];
            },

            get filteredProducts() {
                const query = this.searchQuery.trim().toLocaleLowerCase('id-ID');

                return this.products.filter(product => {
                    const matchesName = product.name.toLocaleLowerCase('id-ID').includes(query);
                    const matchesCategory = this.selectedCategory === 'all' || product.category === this.selectedCategory;

                    return matchesName && matchesCategory;
                });
            },

            get total() {
                return this.cart.reduce((total, item) => total + item.price * item.qty, 0);
            },

            get itemCount() {
                return this.cart.reduce((count, item) => count + item.qty, 0);
            },

            get changeAmount() {
                return Math.max(Number(this.amountReceived || 0) - this.total, 0);
            },

            get canCheckout() {
                return this.cart.length > 0 && !this.isCheckingOut && (
                    this.paymentMethod !== 'cash' ||
                    (this.amountReceived !== '' && Number(this.amountReceived) >= this.total)
                );
            },

            quantityFor(product) {
                return this.cart.find(item => item.id === product.id)?.qty ?? 0;
            },

            addToCart(product) {
                const item = this.cart.find(cartItem => cartItem.id === product.id);

                if (product.stock < 1) {
                    this.notice = 'Stok produk ini sedang habis.';

                    return;
                }

                if (item) {
                    if (item.qty >= product.stock) {
                        this.notice = 'Jumlah di keranjang sudah mencapai batas stok.';

                        return;
                    }

                    item.qty++;
                } else {
                    this.cart.push({ ...product, qty: 1 });
                }

                this.notice = '';
            },

            decreaseProductQuantity(product) {
                const item = this.cart.find(cartItem => cartItem.id === product.id);

                if (!item) {
                    return;
                }

                if (item.qty > 1) {
                    item.qty--;

                    return;
                }

                this.removeItem(item);
            },

            removeItem(item) {
                this.cart = this.cart.filter(cartItem => cartItem.id !== item.id);
                this.notice = '';
            },

            openPaymentModal() {
                if (this.cart.length === 0 || this.isCheckingOut) {
                    return;
                }

                this.notice = '';
                this.checkoutModalOpen = false;
                this.paymentModalOpen = true;
            },

            async checkout() {
                if (!this.canCheckout) {
                    return;
                }

                this.isCheckingOut = true;
                this.notice = '';

                try {
                    const response = await fetch(this.checkoutUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({
                            payment_method: this.paymentMethod,
                            amount_paid: this.paymentMethod === 'cash' ? this.amountReceived : null,
                            items: this.cart.map(item => ({
                                product_id: item.id,
                                quantity: item.qty,
                            })),
                        }),
                    });
                    let data;

                    try {
                        data = await response.json();
                    } catch {
                        throw new Error('Server tidak mengembalikan respons transaksi yang valid.');
                    }

                    if (!response.ok) {
                        const validationMessage = data.errors
                            ? Object.values(data.errors).flat()[0]
                            : null;

                        throw new Error(validationMessage || data.message || 'Transaksi gagal disimpan. Silakan coba lagi.');
                    }

                    window.location.assign(data.redirect);
                } catch (error) {
                    this.notice = error instanceof Error
                        ? error.message
                        : 'Terjadi kesalahan saat menyimpan transaksi.';
                    this.isCheckingOut = false;
                }
            },

            formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0,
                }).format(Number(amount) || 0);
            },
        }"
    >
        <main class="mx-auto grid w-full grid-cols-1 gap-4 px-4 pb-24 pt-3 sm:gap-6 sm:px-6 sm:pb-24 sm:pt-4 lg:h-[calc(100vh-4rem)] lg:min-h-0 lg:grid-cols-12 lg:overflow-hidden lg:pb-4">
            <section class="flex min-w-0 flex-col gap-4 lg:col-span-8 lg:min-h-0">
                <p class="text-xs font-medium text-slate-400">
                    <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">Dashboard</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-slate-600">Kasir</span>
                </p>
                <nav aria-label="Kategori produk" class="grid grid-cols-3 gap-2 sm:grid-cols-4 xl:grid-cols-6">
                    <template x-for="category in categories" :key="category.id">
                        <button
                            type="button"
                            @click="selectedCategory = category.id"
                            :aria-pressed="selectedCategory === category.id"
                            :class="selectedCategory === category.id ? 'border-emerald-600 bg-white text-emerald-700 ring-1 ring-emerald-600 shadow-md' : 'border-slate-200 bg-white text-slate-500 hover:border-emerald-300 hover:text-emerald-700'"
                            class="flex min-h-14 items-center justify-center rounded-lg border px-2.5 py-2 text-center shadow-sm transition sm:px-2 sm:py-1.5"
                        >
                            <span class="text-[11px] font-bold leading-tight sm:text-xs" x-text="category.name"></span>
                        </button>
                    </template>
                </nav>

                <div class="flex min-h-[360px] flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm lg:min-h-0 lg:flex-1">
                    <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                        <label class="relative block w-full">
                            <span class="sr-only">Cari produk</span>
                            <input
                                type="search"
                                x-model="searchQuery"
                                placeholder="Cari nama produk..."
                                class="w-full rounded-full border border-slate-200 bg-slate-50 py-2.5 pl-4 pr-11 text-sm text-slate-700 placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500"
                            >
                            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-emerald-700" aria-hidden="true" />
                        </label>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-5">
                        <div x-show="filteredProducts.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-4 2xl:grid-cols-5">
                            <template x-for="product in filteredProducts" :key="product.id">
                                <article class="group flex min-h-52 flex-col rounded-lg border border-slate-200 bg-white p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-400 hover:shadow-md sm:p-4">
                                    <button
                                        type="button"
                                        @click="addToCart(product)"
                                        :disabled="product.stock < 1"
                                        class="flex flex-1 flex-col text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <span class="relative flex h-28 w-full items-center justify-center overflow-hidden rounded-lg bg-slate-50 transition group-hover:scale-[1.02]">
                                            <template x-if="product.image">
                                                <img :src="product.image" :alt="`Foto produk ${product.name}`" class="absolute inset-0 h-full w-full object-cover">
                                            </template>
                                        </span>
                                        <span class="mt-3 line-clamp-2 min-h-10 text-sm font-bold leading-5 text-slate-800" x-text="product.name"></span>
                                        <span class="mt-1 text-sm font-extrabold text-emerald-700" x-text="formatCurrency(product.price)"></span>
                                        <span class="mt-auto pt-2 text-xs text-slate-500">
                                            Stok: <span class="font-semibold" x-text="`${product.stock} ${product.unit}`"></span>
                                        </span>
                                    </button>
                                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2">
                                        <span class="text-xs font-medium text-slate-500">Jumlah</span>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                @click="decreaseProductQuantity(product)"
                                                :disabled="quantityFor(product) === 0"
                                                :aria-label="`Kurangi ${product.name}`"
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-emerald-300 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-40"
                                            >−</button>
                                            <span class="w-5 text-center text-sm font-bold text-slate-800" x-text="quantityFor(product)"></span>
                                            <button
                                                type="button"
                                                @click="addToCart(product)"
                                                :disabled="product.stock < 1 || quantityFor(product) >= product.stock"
                                                :aria-label="`Tambah ${product.name}`"
                                                class="flex h-8 w-8 items-center justify-center rounded-full border border-emerald-300 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-40"
                                            >+</button>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>

                        <div x-show="filteredProducts.length === 0" class="flex min-h-64 flex-col items-center justify-center gap-3 text-center">
                            <x-heroicon-o-archive-box class="h-10 w-10 text-slate-300" aria-hidden="true" />
                            <p class="font-semibold text-slate-700" x-text="products.length === 0 ? 'Katalog masih kosong' : 'Produk tidak ditemukan'"></p>
                            <p class="max-w-sm text-sm text-slate-500" x-text="products.length === 0 ? 'Tambahkan produk ke katalog agar bisa mulai berjualan.' : 'Coba kata kunci atau kategori yang berbeda.'"></p>
                            <a x-show="products.length === 0" href="{{ route('products.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-700">
                                Tambah produk
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="hidden min-w-0 flex-col gap-4 lg:col-span-4 lg:flex lg:min-h-0">
                <section class="flex min-h-[420px] flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm lg:min-h-0">
                    @include('pos.partials.checkout-cart')
                </section>

                <div class="space-y-3">
                    <div x-show="notice" x-cloak role="status" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800" x-text="notice"></div>
                    <div class="grid grid-cols-1 gap-3">
                        <button type="button" @click="cart = []; notice = ''" :disabled="cart.length === 0" class="rounded-lg border border-red-300 bg-white px-4 py-3 text-sm font-bold text-red-600 shadow-sm transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                            Batalkan
                        </button>
                    </div>
                    <button type="button" @click="openPaymentModal()" :disabled="cart.length === 0 || isCheckingOut" class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-4 text-base font-extrabold text-white shadow-md transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-text="isCheckingOut ? 'Menyimpan transaksi...' : 'Proses pembayaran'"></span>
                        <span aria-hidden="true">·</span>
                        <span x-text="formatCurrency(total)"></span>
                    </button>
                </div>
            </aside>
        </main>

        <button
            type="button"
            @click="checkoutModalOpen = true"
            :disabled="cart.length === 0"
            class="fixed inset-x-4 bottom-4 z-30 flex items-center justify-between gap-3 rounded-2xl bg-emerald-600 px-5 py-4 text-white shadow-lg transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 lg:hidden"
        >
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-heroicon-o-shopping-cart class="h-5 w-5" aria-hidden="true" />
                Checkout
                <span x-show="itemCount > 0" x-cloak class="rounded-full bg-white/20 px-2 py-0.5 text-xs" x-text="itemCount"></span>
            </span>
            <span class="text-base font-extrabold" x-text="formatCurrency(total)"></span>
        </button>

        <div
            x-show="checkoutModalOpen"
            x-cloak
            @keydown.escape.window="checkoutModalOpen = false"
            @click.self="checkoutModalOpen = false"
            class="fixed inset-0 z-40 flex items-end justify-center bg-slate-950/50 sm:items-center sm:p-4"
            role="presentation"
        >
            <section
                class="flex max-h-[85dvh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-white shadow-md sm:rounded-lg"
                role="dialog"
                aria-modal="true"
                aria-label="Keranjang checkout"
            >
                <div class="min-h-0 flex-1 overflow-y-auto">
                    @include('pos.partials.checkout-cart', ['closable' => true])
                </div>

                <div class="space-y-3 border-t border-slate-100 p-4 sm:p-5">
                    <div x-show="notice" x-cloak role="status" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800" x-text="notice"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="cart = []; notice = ''"
                            :disabled="cart.length === 0"
                            class="rounded-lg border border-red-300 bg-white px-4 py-3 text-sm font-bold text-red-600 shadow-sm transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >Batalkan</button>
                        <button
                            type="button"
                            @click="openPaymentModal()"
                            :disabled="cart.length === 0 || isCheckingOut"
                            class="flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span x-text="isCheckingOut ? 'Menyimpan...' : 'Proses pembayaran'"></span>
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <div
            x-show="paymentModalOpen"
            x-cloak
            @keydown.escape.window="if (!isCheckingOut) paymentModalOpen = false"
            @click.self="if (!isCheckingOut) paymentModalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/50 p-4"
            role="presentation"
        >
            <section
                class="my-auto w-full max-w-md space-y-5 rounded-lg bg-white p-5 shadow-md sm:p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="payment-modal-title"
            >
                <header class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h2 id="payment-modal-title" class="text-lg font-extrabold text-slate-800">Pembayaran</h2>
                        <p class="mt-1 text-xs text-slate-500"><span x-text="itemCount"></span> item dalam pesanan</p>
                    </div>
                    <button
                        type="button"
                        @click="paymentModalOpen = false"
                        :disabled="isCheckingOut"
                        aria-label="Tutup pembayaran"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:cursor-not-allowed"
                    >×</button>
                </header>

                <div class="flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-700">Total pembayaran</span>
                    <span class="text-lg font-extrabold text-emerald-700" x-text="formatCurrency(total)"></span>
                </div>

                <label class="block space-y-1.5">
                    <span class="text-xs font-bold text-slate-600">Metode pembayaran</span>
                    <select x-model="paymentMethod" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-700 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="cash">Tunai</option>
                        <option value="qris">QRIS</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </label>

                <div x-show="paymentMethod === 'cash'" class="space-y-2">
                    <label class="block space-y-1.5">
                        <span class="text-xs font-bold text-slate-600">Uang diterima (Rp)</span>
                        <input
                            type="number"
                            min="0"
                            step="1"
                            x-model="amountReceived"
                            inputmode="numeric"
                            placeholder="Masukkan nominal"
                            class="w-full rounded-lg border-slate-200 text-sm text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </label>
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <button type="button" @click="amountReceived = String(Math.ceil(total))" class="font-bold text-emerald-700 hover:text-emerald-800">Uang pas</button>
                        <span class="font-semibold text-slate-600">Kembalian: <span class="text-slate-800" x-text="formatCurrency(changeAmount)"></span></span>
                    </div>
                    <p x-show="amountReceived !== '' && Number(amountReceived) < total" class="text-xs text-red-600">Uang diterima belum mencukupi.</p>
                </div>

                <div x-show="notice" x-cloak role="alert" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800" x-text="notice"></div>

                <div class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        @click="paymentModalOpen = false"
                        :disabled="isCheckingOut"
                        class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >Kembali</button>
                    <button
                        type="button"
                        @click="checkout()"
                        :disabled="!canCheckout"
                        class="rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-text="isCheckingOut ? 'Menyimpan...' : 'Konfirmasi pembayaran'"></span>
                    </button>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
