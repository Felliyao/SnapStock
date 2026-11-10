<x-app-layout>
    <main class="min-h-[calc(100vh-4rem)] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl space-y-6">
            <header class="space-y-2">
                <a href="{{ route('catalogue.index') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                    ← Kembali ke katalog
                </a>
                <h1 class="text-2xl font-extrabold text-slate-800 sm:text-3xl">Edit produk</h1>
                <p class="text-sm text-slate-500">Perbarui informasi produk di katalogmu.</p>
            </header>

            @if ($errors->any())
                <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('products.update', $product) }}"
                enctype="multipart/form-data"
                x-data="{
                    rawMaterials: @js($rawMaterials->map(fn ($material) => [
                        'id' => $material->id,
                        'name' => $material->name,
                        'unit' => $material->unit,
                    ])->values()),
                    selectedRawMaterials: @js(array_values(old('raw_materials', $product->rawMaterials->map(fn ($material) => [
                        'raw_material_id' => $material->id,
                        'quantity_per_product' => (string) $material->pivot->quantity_per_product,
                    ])->all()))).map((material, index) => ({
                        ...material,
                        rowKey: index,
                    })),
                    nextRawMaterialKey: @js(count(old('raw_materials', $product->rawMaterials))),
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
                }"
                class="space-y-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-8"
            >
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="product-name" class="block text-sm font-semibold text-slate-700">Nama produk</label>
                        <input
                            id="product-name"
                            type="text"
                            name="name"
                            value="{{ old('name', $product->name) }}"
                            maxlength="255"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="space-y-1.5">
                        <label for="product-category" class="block text-sm font-semibold text-slate-700">Kategori</label>
                        <input
                            id="product-category"
                            type="text"
                            name="category"
                            value="{{ old('category', $product->category) }}"
                            maxlength="100"
                            class="w-full rounded-lg border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="space-y-1.5">
                        <label for="product-price" class="block text-sm font-semibold text-slate-700">Harga jual (Rp)</label>
                        <input
                            id="product-price"
                            type="number"
                            name="price"
                            value="{{ old('price', $product->price) }}"
                            min="0"
                            step="0.01"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="space-y-1.5">
                        <label for="product-stock" class="block text-sm font-semibold text-slate-700">Stok</label>
                        <input
                            id="product-stock"
                            type="number"
                            name="stock"
                            value="{{ old('stock', $product->stock) }}"
                            min="0"
                            step="1"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="space-y-1.5">
                        <label for="product-unit" class="block text-sm font-semibold text-slate-700">Satuan</label>
                        <input
                            id="product-unit"
                            type="text"
                            name="unit"
                            value="{{ old('unit', $product->unit) }}"
                            maxlength="30"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <label for="product-image" class="block text-sm font-semibold text-slate-700">Foto produk (opsional)</label>
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="Foto produk {{ $product->name }}" class="h-40 w-40 rounded-lg border border-slate-200 object-cover">
                            <p class="text-xs text-slate-500">Pilih foto baru untuk mengganti foto saat ini.</p>
                        @endif
                        <input
                            id="product-image"
                            type="file"
                            name="image"
                            accept="image/jpeg,image/png,image/webp"
                            class="block w-full rounded-lg border border-slate-300 text-sm text-slate-600 file:mr-3 file:border-0 file:bg-indigo-50 file:px-3 file:py-2.5 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        >
                        <p class="text-xs text-slate-500">JPG, PNG, atau WEBP; maksimal 5 MB.</p>
                    </div>

                    <section class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-slate-800">Kebutuhan bahan baku (opsional)</h2>
                                <p class="mt-1 text-xs text-slate-500">Tambahkan bahan yang digunakan dan jumlahnya per unit produk, atau hapus bahan jika tidak digunakan.</p>
                            </div>
                            <button
                                type="button"
                                x-show="rawMaterials.length > 0"
                                @click="addRawMaterial()"
                                :disabled="selectedRawMaterials.length >= rawMaterials.length"
                                class="shrink-0 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Tambah bahan baku
                            </button>
                        </div>
                        <p x-show="selectedRawMaterials.length === 0 && rawMaterials.length > 0" class="border-t border-slate-200 pt-3 text-xs text-slate-500">
                            Belum ada bahan baku dipilih. Produk tetap bisa disimpan tanpa bahan baku.
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
                                        class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
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
                                        class="w-full rounded-lg border-slate-300 text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500"
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
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('catalogue.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                        Simpan perubahan
                    </button>
                </div>
            </form>
        </div>
    </main>
</x-app-layout>
