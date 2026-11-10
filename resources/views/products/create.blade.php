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
            rawMaterials: @js($rawMaterials->map(fn ($material) => [
                'id' => $material->id,
                'name' => $material->name,
                'unit' => $material->unit,
            ])->values()),
            selectedRawMaterials: @js(array_values(old('raw_materials', []))).map((material, index) => ({
                ...material,
                rowKey: index,
            })),
            nextRawMaterialKey: @js(count(old('raw_materials', []))),
            aiPrompt: '',
            imageFile: null,
            imagePreview: null,
            generatedProducts: [],
            errorMessage: '',
            isSaving: false,
            nextRecipeKey: 0,

            addRawMaterial() {
                if (this.selectedRawMaterials.length < this.rawMaterials.length) {
                    this.selectedRawMaterials.push({
                        rowKey: this.nextRawMaterialKey++,
                        raw_material_id: '',
                        quantity_per_product: '',
                    });
                }
            },

            removeRawMaterial(index) {
                this.selectedRawMaterials.splice(index, 1);
            },

            rawMaterialUnit(id) {
                const material = this.rawMaterials.find((item) => Number(item.id) === Number(id));

                return material ? material.unit : '';
            },

            addRecipeRow(product) {
                if (product.rawMaterialRows.length < this.rawMaterials.length) {
                    product.rawMaterialRows.push({
                        rowKey: this.nextRecipeKey++,
                        raw_material_id: '',
                        quantity_per_product: '',
                    });
                }
            },

            removeRecipeRow(product, index) {
                product.rawMaterialRows.splice(index, 1);
            },

            selectProductImage(product, file) {
                if (product.imagePreview) {
                    URL.revokeObjectURL(product.imagePreview);
                }

                product.imageFile = file;
                product.imagePreview = file ? URL.createObjectURL(file) : null;
            },

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

                    this.generatedProducts = data.products.map((product) => ({
                        ...product,
                        rawMaterialRows: [],
                        imageFile: null,
                        imagePreview: null,
                        showRecipe: false,
                    }));
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

                const body = new FormData();

                this.generatedProducts.forEach((product, index) => {
                    body.append(`products[${index}][name]`, product.name ?? '');
                    body.append(`products[${index}][category]`, product.category ?? '');
                    body.append(`products[${index}][price]`, product.price ?? '');
                    body.append(`products[${index}][stock]`, product.stock ?? '');
                    body.append(`products[${index}][unit]`, product.unit ?? '');

                    if (product.imageFile) {
                        body.append(`products[${index}][image]`, product.imageFile);
                    }

                    product.rawMaterialRows.forEach((row) => {
                        if (!row.raw_material_id) {
                            return;
                        }

                        body.append(`products[${index}][raw_materials][${row.rowKey}][raw_material_id]`, row.raw_material_id);
                        body.append(`products[${index}][raw_materials][${row.rowKey}][quantity_per_product]`, row.quantity_per_product ?? '');
                    });
                });

                try {
                    const response = await fetch(this.saveUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body,
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
                class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                x-text="errorMessage"
            ></div>

            @if ($errors->any())
                <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section x-show="step === 'input'" class="space-y-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:flex lg:min-h-0 lg:flex-1 lg:flex-col lg:space-y-4 lg:p-8">
                <div class="space-y-0.5">
                    <h2 class="text-xl font-bold text-slate-800 lg:text-2xl">Buat katalog produk</h2>
                    <p class="text-sm text-slate-500 lg:text-base">Pilih cara membuat katalog. Hasilnya bisa diperiksa sebelum disimpan.</p>
                </div>

                <div class="flex flex-row gap-1 overflow-x-auto border-b border-slate-200 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:gap-0 lg:gap-0" role="tablist" aria-label="Cara membuat katalog">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'manual'"
                        @click="mode = 'manual'; errorMessage = ''"
                        :class="mode === 'manual' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="shrink-0 whitespace-nowrap border-b-2 px-2.5 py-2 text-xs font-semibold transition sm:px-3 sm:py-2.5 sm:text-sm sm:text-center lg:text-base"
                    >
                        Isi manual
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'text'"
                        @click="mode = 'text'; errorMessage = ''"
                        :class="mode === 'text' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="shrink-0 whitespace-nowrap border-b-2 px-2.5 py-2 text-xs font-semibold transition sm:px-3 sm:py-2.5 sm:text-sm sm:text-center lg:text-base"
                    >
                        <x-heroicon-o-pencil-square class="mr-1.5 inline h-4 w-4 align-[-0.125em]" aria-hidden="true" />Ketik deskripsi produk
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'scan'"
                        @click="mode = 'scan'; errorMessage = ''"
                        :class="mode === 'scan' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="shrink-0 whitespace-nowrap border-b-2 px-2.5 py-2 text-xs font-semibold transition sm:px-3 sm:py-2.5 sm:text-sm sm:text-center lg:text-base"
                    >
                        <x-heroicon-o-camera class="mr-1.5 inline h-4 w-4 align-[-0.125em]" aria-hidden="true" />Kenali dari foto
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === 'csv'"
                        @click="mode = 'csv'; errorMessage = ''"
                        :class="mode === 'csv' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="shrink-0 whitespace-nowrap border-b-2 px-2.5 py-2 text-xs font-semibold transition sm:px-3 sm:py-2.5 sm:text-sm sm:text-center lg:text-base"
                    >
                        <x-heroicon-o-chart-bar class="mr-1.5 inline h-4 w-4 align-[-0.125em]" aria-hidden="true" />Impor CSV
                    </button>
                </div>

                <form x-show="mode === 'manual'" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-4 lg:flex lg:min-h-0 lg:flex-1 lg:flex-col lg:overflow-y-auto lg:[scrollbar-width:none] lg:[&::-webkit-scrollbar]:hidden lg:space-y-3">
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
                                class="w-full rounded-lg border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
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
                                class="w-full rounded-lg border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
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
                                class="w-full rounded-lg border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
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
                                class="w-full rounded-lg border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
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
                                class="w-full rounded-lg border-slate-300 py-2.5 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 lg:text-base"
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
                                class="block w-full rounded-lg border border-slate-300 text-sm text-slate-600 file:mr-3 file:border-0 file:bg-emerald-50 file:px-3 file:py-2.5 file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                            >
                            <p class="text-xs text-slate-500">JPG, PNG, atau WEBP; maksimal 5 MB.</p>
                        </div>
                    </div>

                    <section class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Kebutuhan bahan baku (opsional)</h3>
                                <p class="mt-1 text-xs text-slate-500">Tambahkan bahan yang digunakan dan jumlahnya per unit produk, atau lewati bagian ini.</p>
                            </div>
                            <button
                                type="button"
                                x-show="rawMaterials.length > 0"
                                @click="addRawMaterial()"
                                :disabled="selectedRawMaterials.length >= rawMaterials.length"
                                class="shrink-0 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Tambah bahan baku
                            </button>
                        </div>
                        <p x-show="selectedRawMaterials.length === 0 && rawMaterials.length > 0" class="border-t border-slate-200 pt-3 text-xs text-slate-500">
                            Belum ada bahan baku dipilih. Kamu bisa langsung menyimpan produk.
                        </p>
                        <p x-show="rawMaterials.length === 0" class="border-t border-slate-200 pt-3 text-xs text-slate-500">
                            Belum ada jenis stok bahan baku. Produk tetap bisa disimpan tanpa bahan baku.
                        </p>
                        <template x-for="(material, index) in selectedRawMaterials" :key="material.rowKey">
                            <div class="grid grid-cols-1 items-end gap-3 border-t border-slate-200 pt-3 sm:grid-cols-[minmax(0,1fr)_160px_auto]">
                                <div class="space-y-1.5">
                                    <label :for="`recipe-material-${index}`" class="block text-xs font-semibold text-slate-700">Jenis bahan baku</label>
                                    <select
                                        :id="`recipe-material-${index}`"
                                        :name="`raw_materials[${index}][raw_material_id]`"
                                        x-model="material.raw_material_id"
                                        required
                                        class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                        <option value="">Pilih bahan baku</option>
                                        <template x-for="option in rawMaterials" :key="option.id">
                                            <option
                                                :value="option.id"
                                                :disabled="selectedRawMaterials.some((selected, selectedIndex) => selectedIndex !== index && Number(selected.raw_material_id) === Number(option.id))"
                                                x-text="`${option.name} (${option.unit})`"
                                            ></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label :for="`recipe-quantity-${index}`" class="block text-xs font-semibold text-slate-700">Jumlah per unit</label>
                                    <input
                                        :id="`recipe-quantity-${index}`"
                                        type="number"
                                        :name="`raw_materials[${index}][quantity_per_product]`"
                                        x-model="material.quantity_per_product"
                                        min="0"
                                        max="4294967295"
                                        step="0.001"
                                        required
                                        class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                </div>
                                <div class="flex items-center justify-between gap-3 sm:pb-2">
                                    <span class="text-xs text-slate-500" x-text="rawMaterialUnit(material.raw_material_id)"></span>
                                    <button
                                        type="button"
                                        @click="removeRawMaterial(index)"
                                        class="rounded-lg px-2 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                        :aria-label="`Hapus bahan baku nomor ${index + 1}`"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </template>
                    </section>

                    <div class="flex justify-end border-t border-slate-100 pt-4 lg:pt-3">
                        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 sm:w-auto lg:text-base">
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
                        class="w-full rounded-lg border-slate-300 p-4 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Contoh: Saya jual donat cokelat seharga Rp35.000, stok 20 box. Tambahkan juga donat stroberi seharga Rp38.000, stok 15 box."
                    ></textarea>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium text-slate-500">Contoh:</span>
                        <button
                            type="button"
                            @click="aiPrompt = 'Jualan donat cokelat Rp35.000 stok 20 box dan donat stroberi Rp38.000 stok 15 box.'"
                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            <x-heroicon-o-cake class="mr-1 inline h-3.5 w-3.5 align-[-0.125em]" aria-hidden="true" />Donat variasi
                        </button>
                        <button
                            type="button"
                            @click="aiPrompt = 'Jual snack box arisan berisi kue basah, harga Rp15.000, stok 30 paket.'"
                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            <x-heroicon-o-archive-box class="mr-1 inline h-3.5 w-3.5 align-[-0.125em]" aria-hidden="true" />Snack box
                        </button>
                    </div>
                </div>

                <div x-show="mode === 'scan'" class="space-y-3 lg:space-y-2">
                    <label for="catalog-image" class="block text-sm font-medium text-slate-800">Foto produk, kemasan, atau nota</label>
                    <p class="text-sm text-slate-500">AI akan memperkirakan nama dan kategori produk dari tampilannya. Harga dan stok yang tidak terlihat akan diisi 0.</p>
                    <div class="rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-4 text-center transition hover:border-indigo-400 lg:p-3">
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
                                <x-heroicon-o-camera class="mx-auto h-10 w-10 text-indigo-500" aria-hidden="true" />
                                <span class="block text-sm font-semibold text-slate-800">Pilih atau ambil foto</span>
                                <span class="block text-xs text-slate-500">Format JPG, PNG, atau WEBP; maksimal 5 MB.</span>
                            </label>
                        </template>
                        <template x-if="imagePreview">
                            <div class="space-y-3">
                                <img :src="imagePreview" alt="Pratinjau foto katalog" class="mx-auto max-h-64 max-w-full rounded-lg border border-slate-200 object-contain shadow-sm">
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
                    <form method="POST" action="{{ route('products.import.preview') }}" enctype="multipart/form-data" class="space-y-3 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-4 lg:space-y-2 lg:p-3">
                        @csrf
                        <label for="catalog-csv" class="block text-sm font-medium text-slate-800">Pilih file CSV</label>
                        <input
                            id="catalog-csv"
                            type="file"
                            name="file"
                            accept=".csv,text/csv"
                            required
                            class="block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        >
                        <button type="submit" class="w-full rounded-lg bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 sm:w-auto">
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
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto lg:py-2"
                    >
                        <span>Generasikan katalog dengan AI</span>
                        <x-heroicon-o-bolt class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>
            </section>

            @if (isset($rows))
                @include('products.import-preview')
            @endif

            <section x-show="step === 'loading'" class="space-y-4 rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm sm:p-12">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600" aria-hidden="true">
                    <x-heroicon-o-cog-6-tooth class="h-8 w-8" />
                </div>
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">Sedang meracik katalog...</h2>
                    <p class="text-sm text-slate-500">AI sedang mengenali produk dan membaca informasi yang terlihat. Proses ini mungkin perlu beberapa saat.</p>
                </div>
            </section>

            <section x-show="step === 'review'" class="space-y-6">
                <div class="flex flex-col gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 sm:flex-row sm:items-center sm:justify-between">
                    <p class="font-medium">
                        <x-heroicon-s-check-circle class="mr-1 inline h-4 w-4 text-emerald-600" aria-hidden="true" />AI berhasil menyiapkan <strong x-text="generatedProducts.length"></strong> produk untuk ditinjau.
                    </p>
                    <button type="button" @click="step = 'input'" class="w-fit text-xs font-semibold text-emerald-700 underline hover:text-emerald-900">
                        Ubah input
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="(product, index) in generatedProducts" :key="index">
                        <article class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2 space-y-1">
                                    <label class="block text-xs font-medium text-slate-600">Nama produk</label>
                                    <input type="text" x-model="product.name" maxlength="255" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="col-span-2 space-y-1 sm:col-span-1">
                                    <label class="block text-xs font-medium text-slate-600">Kategori</label>
                                    <input type="text" x-model="product.category" maxlength="100" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-slate-600">Harga (Rp)</label>
                                    <input type="number" x-model="product.price" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-slate-600">Stok</label>
                                    <input type="number" x-model="product.stock" min="0" step="1" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-slate-600">Satuan</label>
                                    <input type="text" x-model="product.unit" maxlength="30" class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <span class="block text-xs font-medium text-slate-600">Foto produk (opsional)</span>
                                <input
                                    :id="`review-image-${index}`"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="sr-only"
                                    @change="selectProductImage(product, $event.target.files[0] || null)"
                                >
                                <template x-if="!product.imagePreview">
                                    <label :for="`review-image-${index}`" class="flex cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-3 py-3 text-xs font-semibold text-slate-500 transition hover:border-indigo-400 hover:text-indigo-600">
                                        Pilih foto
                                    </label>
                                </template>
                                <template x-if="product.imagePreview">
                                    <div class="space-y-2">
                                        <img :src="product.imagePreview" :alt="`Pratinjau ${product.name}`" class="h-28 w-full rounded-lg border border-slate-200 object-cover">
                                        <label :for="`review-image-${index}`" class="inline-flex cursor-pointer text-xs font-semibold text-indigo-700 hover:text-indigo-900">
                                            Ganti foto
                                        </label>
                                    </div>
                                </template>
                            </div>

                            <div class="space-y-2 border-t border-slate-100 pt-3">
                                <button
                                    type="button"
                                    @click="product.showRecipe = !product.showRecipe"
                                    class="flex w-full items-center justify-between text-xs font-semibold text-slate-600 transition hover:text-indigo-700"
                                >
                                    <span>Kebutuhan bahan baku (opsional)</span>
                                    <span x-text="product.showRecipe ? '−' : '+'" aria-hidden="true"></span>
                                </button>
                                <div x-show="product.showRecipe" x-cloak class="space-y-3">
                                    <p x-show="product.rawMaterialRows.length === 0" class="text-xs text-slate-500">Belum ada bahan baku dipilih.</p>
                                    <template x-for="(row, rowIndex) in product.rawMaterialRows" :key="row.rowKey">
                                        <div class="grid grid-cols-[minmax(0,1fr)_100px_auto] items-end gap-2">
                                            <div class="space-y-1">
                                                <label class="block text-[11px] font-medium text-slate-600">Bahan baku</label>
                                                <select x-model="row.raw_material_id" class="w-full rounded-lg border-slate-300 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Pilih bahan baku</option>
                                                    <template x-for="option in rawMaterials" :key="option.id">
                                                        <option
                                                            :value="option.id"
                                                            :disabled="product.rawMaterialRows.some((selected, selectedIndex) => selectedIndex !== rowIndex && Number(selected.raw_material_id) === Number(option.id))"
                                                            x-text="`${option.name} (${option.unit})`"
                                                        ></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="block text-[11px] font-medium text-slate-600">Jumlah/unit</label>
                                                <input type="number" x-model="row.quantity_per_product" min="0" max="4294967295" step="0.001" class="w-full rounded-lg border-slate-300 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeRecipeRow(product, rowIndex)"
                                                class="rounded-lg px-2 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </template>
                                    <button
                                        type="button"
                                        @click="addRecipeRow(product)"
                                        :disabled="product.rawMaterialRows.length >= rawMaterials.length"
                                        class="rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Tambah bahan baku
                                    </button>
                                </div>
                            </div>

                            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-3">
                                <span class="text-xs text-slate-500">Stok: <strong class="text-slate-700" x-text="`${product.stock} ${product.unit}`"></strong></span>
                                <button
                                    type="button"
                                    @click="generatedProducts.splice(index, 1)"
                                    class="text-xs font-semibold text-red-600 transition hover:text-red-700"
                                >
                                    Hapus produk
                                </button>
                            </div>
                        </article>
                    </template>
                </div>

                <div class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="text-center sm:text-left">
                        <h2 class="text-sm font-bold text-slate-800">Simpan produk ke katalog?</h2>
                        <p class="text-xs text-slate-500">Periksa hasilnya sebelum menyimpannya ke akunmu.</p>
                    </div>
                    <div class="flex w-full gap-3 sm:w-auto">
                        <button
                            type="button"
                            @click="step = 'input'"
                            :disabled="isSaving"
                            class="w-1/2 rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200 disabled:opacity-50 sm:w-auto"
                        >
                            Kembali
                        </button>
                        <button
                            type="button"
                            @click="saveProducts()"
                            :disabled="isSaving || generatedProducts.length === 0"
                            class="w-1/2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60 sm:w-auto"
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
