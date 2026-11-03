<x-app-layout>
    <div
        class="bg-slate-50 py-6 sm:py-8"
        x-data="{
            generateUrl: '{{ route('products.ai-generate') }}',
            saveUrl: '{{ route('products.ai-store') }}',
            csrfToken: '{{ csrf_token() }}',
            step: 'input',
            mode: 'text',
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
                        this.errorMessage = 'Pilih foto kemasan atau nota terlebih dahulu.';
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
        <div class="mx-auto w-full max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="shrink-0 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-100 hover:text-slate-800">
                        <span aria-hidden="true">←</span> Kembali
                    </a>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-800 sm:text-2xl">✨ AI Instant Catalog Generator</h1>
                        <p class="text-xs text-slate-500 sm:text-sm">Buat katalog dari deskripsi atau foto produk.</p>
                    </div>
                </div>
                <span class="hidden w-fit rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 sm:inline-block">
                    SnapStock Smart AI
                </span>
            </header>

            <div
                x-show="errorMessage"
                x-cloak
                role="alert"
                class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                x-text="errorMessage"
            ></div>

            <section x-show="step === 'input'" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Ceritakan produk jualanmu</h2>
                    <p class="mt-1 text-sm text-slate-500">Pilih cara membuat katalog. Hasilnya bisa diperiksa sebelum disimpan.</p>
                </div>

                <div class="flex flex-col gap-2 border-b border-slate-200 sm:flex-row" role="tablist" aria-label="Cara membuat katalog">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'text'"
                        @click="mode = 'text'; errorMessage = ''"
                        :class="mode === 'text' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-3 py-3 text-left text-sm font-semibold transition sm:text-center"
                    >
                        ✏️ Ketik deskripsi produk
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'scan'"
                        @click="mode = 'scan'; errorMessage = ''"
                        :class="mode === 'scan' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="border-b-2 px-3 py-3 text-left text-sm font-semibold transition sm:text-center"
                    >
                        📸 Scan kemasan atau nota
                    </button>
                </div>

                <div x-show="mode === 'text'" class="space-y-3">
                    <label for="ai-prompt" class="block text-sm font-medium text-slate-800">Deskripsi katalog</label>
                    <textarea
                        id="ai-prompt"
                        x-model="aiPrompt"
                        rows="5"
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

                <div x-show="mode === 'scan'" class="space-y-3">
                    <label for="catalog-image" class="block text-sm font-medium text-slate-800">Foto kemasan atau nota</label>
                    <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition hover:border-indigo-400">
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

                <div class="flex justify-end border-t border-slate-100 pt-4">
                    <button
                        type="button"
                        @click="generateAi()"
                        :disabled="mode === 'text' ? !aiPrompt.trim() : !imageFile"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                    >
                        <span>Generasikan katalog dengan AI</span>
                        <span aria-hidden="true">⚡</span>
                    </button>
                </div>
            </section>

            <section x-show="step === 'loading'" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm sm:p-12">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-3xl text-indigo-600" aria-hidden="true">
                    ⚙️
                </div>
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">Sedang meracik katalog...</h2>
                    <p class="text-sm text-slate-500">AI sedang membaca informasi produk. Proses ini mungkin perlu beberapa saat.</p>
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
