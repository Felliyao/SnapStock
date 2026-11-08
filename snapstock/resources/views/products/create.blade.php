<x-app-layout>
    <div
        class="bg-slate-50"
        :class="step === 'input' ? 'lg:h-[calc(100vh-4rem)] lg:overflow-hidden' : ''"
        x-data="{
            generateUrl: '{{ route('products.ai-generate') }}',
            saveUrl: '{{ route('products.ai-store') }}',
            csrfToken: '{{ csrf_token() }}',
            step: @js(isset($rows) ? 'import-review' : 'input'),
            mode: @js(isset($rows) ? 'csv' : (old('_form_mode') === 'manual' ? 'manual' : 'text')),
            aiPrompt: '',
            imageFile: null,
            imagePreview: null,
            generatedProducts: [],
            errorMessage: '',
            isSaving: false,

            selectImage(file) {
                if (this.imagePreview) {
                    URL.revokeObjectURL(this.imagePreview);
                }

                this.imageFile = file;
                this.imagePreview = file ? URL.createObjectURL(file) : null;
                this.errorMessage = '';
            },

            async generateAi() {
                this.step = 'loading';
                this.errorMessage = '';

                const body = new FormData();
                body.append('mode', this.mode);

                if (this.mode === 'scan') {
                    if (!this.imageFile) {
                        this.step = 'input';
                        this.errorMessage = 'Pilih foto produk, kemasan, atau nota terlebih dahulu.';
                        return;
                    }

                    body.append('image', this.imageFile);
                } else {
                    body.append('prompt', this.aiPrompt);
                }

                try {
                    const response = await fetch(this.generateUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body,
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'AI gagal membuat katalog. Silakan coba lagi.');
                    }

                    this.generatedProducts = data.products;
                    this.step = 'review';
                } catch (error) {
                    this.errorMessage = error instanceof Error
                        ? error.message
                        : 'Terjadi kesalahan saat menghubungi AI.';
                    this.step = 'input';
                }
            },

            async saveProducts() {
                this.isSaving = true;
                this.errorMessage = '';

                try {
                    const response = await fetch(this.saveUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({ products: this.generatedProducts }),
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Katalog gagal disimpan. Silakan coba lagi.');
                    }

                    window.location.assign(data.redirect);
                } catch (error) {
                    this.errorMessage = error instanceof Error
                        ? error.message
                        : 'Terjadi kesalahan saat menyimpan katalog.';
                    this.isSaving = false;
                }
            },
        }"
    >
        <div class="mx-auto flex w-full max-w-5xl flex-col space-y-4 px-4 py-4 sm:px-6 sm:py-6 lg:space-y-3 lg:px-8 lg:py-3" :class="step === 'input' ? 'lg:h-full lg:min-h-0' : ''">

            <div
                x-show="errorMessage"
                x-cloak
                role="alert"
                class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                x-text="errorMessage"
            ></div>

            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section x-show="step === 'input'" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:min-h-0 lg:flex-1 lg:space-y-4 lg:p-8">
                <div class="space-y-0.5">
                    <h2 class="text-xl font-bold text-slate-800 lg:text-2xl">Buat katalog produk</h2>
                    <p class="text-sm text-slate-500 lg:text-base">Pilih cara membuat katalog. Hasilnya bisa diperiksa sebelum disimpan.</p>
                </div>

                <div class="flex flex-col gap-1 border-b border-slate-200 sm:flex-row lg:gap-0" role="tablist" aria-label="Cara membuat katalog">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'manual'"
                        @click="mode = 'manual'; errorMessage = ''"
                        :class="mode === 'manual' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-3 py-2.5 text-left text-sm font-semibold transition sm:text-center lg:text-base"
                    >
                        Isi manual
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'text'"
                        @click="mode = 'text'; errorMessage = ''"
                        :class="mode === 'text' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-3 py-2.5 text-left text-sm font-semibold transition sm:text-center lg:text-base"
                    >
                        ✏️ Ketik deskripsi produk
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'scan'"
                        @click="mode = 'scan'; errorMessage = ''"
                        :class="mode === 'scan' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-3 py-2.5 text-left text-sm font-semibold transition sm:text-center lg:text-base"
                    >
                        📸 Kenali dari foto
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'csv'"
                        @click="mode = 'csv'; errorMessage = ''"
                        :class="mode === 'csv' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-3 py-2.5 text-left text-sm font-semibold transition sm:text-center lg:text-base"
                    >
                        📊 Impor CSV
                    </button>
                </div>

                <form x-show="mode === 'manual'" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-4 lg:space-y-3">
                    @csrf
                    <input type="hidden" name="_form_mode" value="manual">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:gap-x-5 lg:gap-y-3">
                        <div class="space-y-1">
                            <label for="product-name" class="block text-sm font-medium text-slate-800 lg:text-base">Nama produk <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input
                                id="product-name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                maxlength="255"
                                required
                                class="w-full rounded-xl border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
                                placeholder="Contoh: Donat cokelat"
                            >
                        </div>

                        <div class="space-y-1">
                            <label for="product-category" class="block text-sm font-medium text-slate-800 lg:text-base">Kategori</label>
                            <input
                                id="product-category"
                                type="text"
                                name="category"
                                value="{{ old('category') }}"
                                maxlength="100"
                                class="w-full rounded-xl border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
                                placeholder="Contoh: Makanan"
                            >
                        </div>

                        <div class="space-y-1">
                            <label for="product-price" class="block text-sm font-medium text-slate-800 lg:text-base">Harga jual (Rp) <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input
                                id="product-price"
                                type="number"
                                name="price"
                                value="{{ old('price') }}"
                                min="0"
                                step="0.01"
                                required
                                class="w-full rounded-xl border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
                                placeholder="Contoh: 35000"
                            >
                        </div>

                        <div class="space-y-1">
                            <label for="product-stock" class="block text-sm font-medium text-slate-800 lg:text-base">Stok awal <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input
                                id="product-stock"
                                type="number"
                                name="stock"
                                value="{{ old('stock') }}"
                                min="0"
                                step="1"
                                required
                                class="w-full rounded-xl border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
                                placeholder="Contoh: 20"
                            >
                        </div>

                        <div class="space-y-1">
                            <label for="product-unit" class="block text-sm font-medium text-slate-800 lg:text-base">Satuan <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input
                                id="product-unit"
                                type="text"
                                name="unit"
                                value="{{ old('unit', 'pcs') }}"
                                maxlength="30"
                                required
                                class="w-full rounded-xl border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
                                placeholder="Contoh: pcs, box, kg"
                            >
                        </div>

                        <div class="space-y-1">
                            <label for="product-image" class="block text-sm font-medium text-slate-800 lg:text-base">Foto produk (opsional)</label>
                            <input
                                id="product-image"
                                type="file"
                                name="image"
                                accept="image/jpeg,image/png,image/webp"
                                class="block w-full rounded-xl border border-slate-300 text-sm text-slate-600 file:mr-3 file:border-0 file:bg-emerald-50 file:px-3 file:py-2.5 file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                            >
                            <p class="text-xs text-slate-500">JPG, PNG, atau WEBP; maksimal 5 MB.</p>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-slate-100 pt-4 lg:pt-3">
                        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 sm:w-auto lg:text-base">
                            Simpan produk
                        </button>
                    </div>
                </form>

                <div x-show="mode === 'text'" class="space-y-3 lg:space-y-2">
                    <label for="ai-prompt" class="block text-sm font-medium text-slate-800">Deskripsi katalog</label>
                    <textarea
                        id="ai-prompt"
                        x-model="aiPrompt"
                        rows="4"
                        maxlength="2000"
                        class="w-full rounded-2xl border-slate-300 p-4 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Contoh: Saya jual donat cokelat seharga Rp35.000, stok 20 box. Tambahkan juga donat stroberi seharga Rp38.000, stok 15 box."
                    ></textarea>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">Contoh:</span>
                        <button
                            type="button"
                            @click="aiPrompt = 'Jualan donat cokelat Rp35.000 stok 20 box dan donat stroberi Rp38.000 stok 15 box.'"
                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            🍩 Donat variasi
                        </button>
                        <button
                            type="button"
                            @click="aiPrompt = 'Jual snack box arisan berisi kue basah, harga Rp15.000, stok 30 paket.'"
                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            📦 Snack box
                        </button>
                    </div>
                </div>

                <div x-show="mode === 'scan'" class="space-y-3 lg:space-y-2">
                    <label for="catalog-image" class="block text-sm font-medium text-slate-800">Foto produk, kemasan, atau nota</label>
                    <p class="text-sm text-slate-500">AI akan memperkirakan nama dan kategori produk dari tampilannya. Harga dan stok yang tidak terlihat akan diisi 0.</p>
                    <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-4 text-center transition hover:border-indigo-400 lg:p-3">
                        <input
                            id="catalog-image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            capture="environment"
                            class="sr-only"
                            @change="selectImage($event.target.files[0] || null)"
                        >
                        <template x-if="!imagePreview">
                            <label for="catalog-image" class="block cursor-pointer space-y-2">
                                <span class="text-3xl" aria-hidden="true">📷</span>
                                <span class="block text-sm font-semibold text-slate-800">Pilih atau ambil foto</span>
                                <span class="block text-xs text-slate-500">Format JPG, PNG, atau WEBP; maksimal 5 MB.</span>
                            </label>
                        </template>
                        <template x-if="imagePreview">
                            <div class="space-y-3">
                                <img :src="imagePreview" alt="Pratinjau foto katalog" class="mx-auto max-h-64 max-w-full rounded-xl border border-slate-200 object-contain shadow-sm">
                                <label for="catalog-image" class="inline-flex cursor-pointer rounded-lg px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
                                    Ganti foto
                                </label>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="mode === 'csv'" class="space-y-3 lg:space-y-2">
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-slate-800">Impor atau perbarui katalog dari file CSV</h3>
                        <p class="text-sm text-slate-500">Produk dengan nama yang sama akan diperbarui jika ada perbedaan. Periksa semua perubahan sebelum menyimpan.</p>
                        <p class="text-xs text-slate-500">Maksimal 2 MB dan 500 produk. Kolom wajib: name, price, stock. Harga gunakan angka tanpa pemisah ribuan.</p>
                        <a href="{{ route('products.import.template') }}" class="inline-flex text-sm font-semibold text-indigo-700 underline hover:text-indigo-900">
                            Unduh template CSV
                        </a>
                    </div>
                    <form method="POST" action="{{ route('products.import.preview') }}" enctype="multipart/form-data" class="space-y-3 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-4 lg:space-y-2 lg:p-3">
                        @csrf
                        <label for="catalog-csv" class="block text-sm font-medium text-slate-800">Pilih file CSV</label>
                        <input
                            id="catalog-csv"
                            type="file"
                            name="file"
                            accept=".csv,text/csv"
                            required
                            class="block w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        >
                        <button type="submit" class="w-full rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 sm:w-auto">
                            Tinjau data CSV
                        </button>
                    </form>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-3 lg:pt-2">
                    <button
                        type="button"
                        @click="generateAi()"
                        x-show="mode === 'text' || mode === 'scan'"
                        :disabled="mode === 'text' ? !aiPrompt.trim() : !imageFile"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto lg:py-2"
                    >
                        <span>Generasikan katalog dengan AI</span>
                        <span aria-hidden="true">⚡</span>
                    </button>
                </div>
            </section>

            @if (isset($rows))
                @include('products.import-preview')
            @endif

            <section x-show="step === 'loading'" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm sm:p-12">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-3xl text-indigo-600" aria-hidden="true">
                    ⚙️
                </div>
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">Sedang meracik katalog...</h2>
                    <p class="text-sm text-slate-500">AI sedang mengenali produk dan membaca informasi yang terlihat. Proses ini mungkin perlu beberapa saat.</p>
                </div>
            </section>

            <section x-show="step === 'review'" class="space-y-6">
                <div class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 sm:flex-row sm:items-center sm:justify-between">
                    <p class="font-medium">
                        ✅ AI berhasil menyiapkan <strong x-text="generatedProducts.length"></strong> produk untuk ditinjau.
                    </p>
                    <button type="button" @click="step = 'input'" class="w-fit text-xs font-semibold text-emerald-700 underline hover:text-emerald-900">
                        Ubah input
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="(product, index) in generatedProducts" :key="index">
                        <article class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700" x-text="product.category"></span>
                                    <span class="shrink-0 text-xs text-slate-500">
                                        Stok: <strong class="text-slate-700" x-text="`${product.stock} ${product.unit}`"></strong>
                                    </span>
                                </div>
                                <h3 class="font-bold text-slate-800" x-text="product.name"></h3>
                                <p class="text-sm leading-relaxed text-slate-600" x-text="product.description"></p>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                                <span class="text-xs text-slate-500">Harga jual</span>
                                <span class="font-extrabold text-indigo-700" x-text="`Rp ${Number(product.price).toLocaleString('id-ID')}`"></span>
                            </div>
                        </article>
                    </template>
                </div>

                <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="text-center sm:text-left">
                        <h2 class="text-sm font-bold text-slate-800">Simpan produk ke katalog?</h2>
                        <p class="text-xs text-slate-500">Periksa hasilnya sebelum menyimpannya ke akunmu.</p>
                    </div>
                    <div class="flex w-full gap-3 sm:w-auto">
                        <button
                            type="button"
                            @click="step = 'input'"
                            :disabled="isSaving"
                            class="w-1/2 rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200 disabled:opacity-50 sm:w-auto"
                        >
                            Kembali
                        </button>
                        <button
                            type="button"
                            @click="saveProducts()"
                            :disabled="isSaving || generatedProducts.length === 0"
                            class="w-1/2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60 sm:w-auto"
                        >
                            <span x-show="!isSaving">Simpan katalog</span>
                            <span x-show="isSaving">Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
