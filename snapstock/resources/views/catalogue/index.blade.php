<x-app-layout>
    <div
        class="min-h-[calc(100vh-4rem)] bg-indigo-50/70 px-3 py-5 sm:px-6 sm:py-8"
        x-data="{
            products: @js($products),
            searchQuery: '',
            selectedCategories: null,
            minimumPrice: null,
            maximumPrice: null,
            onlyInStock: false,
            sortBy: 'name',

            get categories() {
                return [...new Set(this.products.map(product => product.category))]
                    .sort((first, second) => first.localeCompare(second, 'id'));
            },

            get filteredProducts() {
                const query = this.searchQuery.trim().toLocaleLowerCase('id-ID');

                return this.products
                    .filter(product => {
                        const matchesName = product.name.toLocaleLowerCase('id-ID').includes(query);
                        const matchesCategory = this.selectedCategories === null
                            || this.selectedCategories.includes(product.category);
                        const matchesMinimumPrice = this.minimumPrice === null || this.minimumPrice === ''
                            || product.price >= this.minimumPrice;
                        const matchesMaximumPrice = this.maximumPrice === null || this.maximumPrice === ''
                            || product.price <= this.maximumPrice;
                        const matchesStock = !this.onlyInStock || product.stock > 0;

                        return matchesName && matchesCategory && matchesMinimumPrice
                            && matchesMaximumPrice && matchesStock;
                    })
                    .sort((first, second) => {
                        if (this.sortBy === 'price-low') {
                            return first.price - second.price;
                        }

                        if (this.sortBy === 'price-high') {
                            return second.price - first.price;
                        }

                        return first.name.localeCompare(second.name, 'id');
                    });
            },

            get allCategoriesSelected() {
                return this.selectedCategories === null
                    || (this.categories.length > 0
                        && this.categories.every(category => this.selectedCategories.includes(category)));
            },

            toggleCategory(category) {
                const selectedCategories = this.selectedCategories ?? this.categories;

                this.selectedCategories = selectedCategories.includes(category)
                    ? selectedCategories.filter(selected => selected !== category)
                    : [...selectedCategories, category];
            },

            toggleAllCategories() {
                this.selectedCategories = this.allCategoriesSelected ? [] : null;
            },

            clearFilters() {
                this.searchQuery = '';
                this.selectedCategories = null;
                this.minimumPrice = null;
                this.maximumPrice = null;
                this.onlyInStock = false;
                this.sortBy = 'name';
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
        <main class="mx-auto min-h-[calc(100vh-8rem)] max-w-7xl rounded-2xl border border-slate-100 bg-white px-4 py-5 shadow-sm sm:px-6 sm:py-7 lg:px-8">
            <header class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-medium text-slate-400">
                        <a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Dashboard</a>
                        <span aria-hidden="true">/</span>
                        <span class="text-slate-600">Katalog</span>
                    </p>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-extrabold tracking-tight text-slate-800 sm:text-3xl">Katalog Produk</h1>
                        <span class="flex h-6 min-w-6 items-center justify-center rounded-full bg-indigo-100 px-2 text-xs font-bold text-indigo-700" x-text="filteredProducts.length"></span>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <label class="relative block w-full sm:w-56">
                        <span class="sr-only">Cari produk</span>
                        <input
                            type="search"
                            x-model="searchQuery"
                            placeholder="Cari produk..."
                            class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-3 pr-9 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-indigo-500"
                        >
                        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 1 0 3.481 9.758l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 8.5 3ZM4.5 8.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                        </svg>
                    </label>

                    <label class="sr-only" for="catalog-sort">Urutkan produk</label>
                    <select id="catalog-sort" x-model="sortBy" class="rounded-xl border-slate-200 bg-white py-2.5 pl-3 pr-8 text-sm font-semibold text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="name">Nama produk</option>
                        <option value="price-low">Harga terendah</option>
                        <option value="price-high">Harga tertinggi</option>
                    </select>

                </div>
            </header>

            <div class="grid grid-cols-1 items-start gap-6 pt-6 lg:grid-cols-[230px_minmax(0,1fr)]">
                <aside class="space-y-5 rounded-2xl border border-slate-100 bg-white p-4 sm:p-5">
                    <section class="space-y-3">
                        <h2 class="text-sm font-bold text-slate-800">Kategori</h2>
                        <div class="space-y-2.5">
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                                <input
                                    type="checkbox"
                                    :checked="allCategoriesSelected"
                                    @change="toggleAllCategories()"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span>Semua kategori</span>
                            </label>
                            <template x-for="category in categories" :key="category">
                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                                    <input
                                        type="checkbox"
                                        :checked="selectedCategories === null || selectedCategories.includes(category)"
                                        @change="toggleCategory(category)"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span x-text="category"></span>
                                </label>
                            </template>
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <section class="space-y-3">
                        <h2 class="text-sm font-bold text-slate-800">Rentang harga</h2>
                        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                            <label class="sr-only" for="minimum-price">Harga minimum</label>
                            <input
                                id="minimum-price"
                                type="number"
                                min="0"
                                x-model.number="minimumPrice"
                                placeholder="Min"
                                class="min-w-0 rounded-lg border-slate-200 px-2.5 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <span class="text-xs text-slate-400">–</span>
                            <label class="sr-only" for="maximum-price">Harga maksimum</label>
                            <input
                                id="maximum-price"
                                type="number"
                                min="0"
                                x-model.number="maximumPrice"
                                placeholder="Maks"
                                class="min-w-0 rounded-lg border-slate-200 px-2.5 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                    </section>

                    <div class="border-t border-slate-100"></div>

                    <label class="flex cursor-pointer items-center justify-between gap-3">
                        <span class="text-sm font-semibold text-slate-700">Stok tersedia</span>
                        <input
                            type="checkbox"
                            x-model="onlyInStock"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        >
                    </label>

                    <div class="flex gap-2 border-t border-slate-100 pt-4">
                        <span class="flex flex-1 items-center justify-center rounded-lg bg-indigo-600 px-3 py-2.5 text-xs font-bold text-white" x-text="`${filteredProducts.length} produk`"></span>
                        <button
                            type="button"
                            @click="clearFilters()"
                            class="rounded-lg border border-slate-200 px-3 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                        >
                            Bersihkan
                        </button>
                    </div>
                </aside>

                <section aria-label="Daftar produk">
                    <div x-show="filteredProducts.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <article class="group flex min-h-72 flex-col overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                                <div class="relative flex h-40 items-center justify-center overflow-hidden bg-slate-50">
                                    <template x-if="product.image">
                                        <img :src="product.image" :alt="`Foto produk ${product.name}`" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                                    </template>
                                    <span class="absolute bottom-3 left-3 z-10 rounded-md bg-white/90 px-2 py-1 text-[11px] font-medium text-slate-500" x-text="product.category"></span>
                                    <span
                                        class="absolute right-3 top-3 z-10 rounded-full px-2 py-1 text-[11px] font-semibold"
                                        :class="product.stock > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                        x-text="product.stock > 0 ? 'Tersedia' : 'Stok habis'"
                                    ></span>
                                </div>

                                <div class="flex flex-1 flex-col gap-3 p-4">
                                    <div class="flex-1 space-y-1">
                                        <h3 class="line-clamp-2 text-sm font-bold leading-5 text-slate-800" x-text="product.name"></h3>
                                        <p x-show="product.description" class="line-clamp-2 text-xs text-slate-500" x-text="product.description"></p>
                                        <p class="text-xs text-slate-500">
                                            Stok:
                                            <span class="font-semibold text-slate-700" x-text="`${product.stock} ${product.unit}`"></span>
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                                        <span class="text-sm font-extrabold text-slate-800" x-text="formatCurrency(product.price)"></span>
                                        <a
                                            :href="product.editUrl"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-center text-xs font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                                        >
                                            Edit produk
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>

                    <div x-show="filteredProducts.length === 0" class="flex min-h-72 flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 text-center">
                        <span class="text-4xl" aria-hidden="true">📦</span>
                        <h2 class="font-semibold text-slate-700" x-text="products.length === 0 ? 'Katalog masih kosong' : 'Produk tidak ditemukan'"></h2>
                        <p class="max-w-sm text-sm text-slate-500" x-text="products.length === 0 ? 'Tambahkan produk ke katalog agar bisa mulai berjualan.' : 'Coba ubah kata kunci atau filter yang dipilih.'"></p>
                    </div>
                </section>
            </div>
        </main>
    </div>
</x-app-layout>
