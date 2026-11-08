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
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
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
                class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"
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
                            class="w-full rounded-xl border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="w-full rounded-xl border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="w-full rounded-xl border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="w-full rounded-xl border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="w-full rounded-xl border-slate-300 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <label for="product-image" class="block text-sm font-semibold text-slate-700">Foto produk (opsional)</label>
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="Foto produk {{ $product->name }}" class="h-40 w-40 rounded-xl border border-slate-200 object-cover">
                            <p class="text-xs text-slate-500">Pilih foto baru untuk mengganti foto saat ini.</p>
                        @endif
                        <input
                            id="product-image"
                            type="file"
                            name="image"
                            accept="image/jpeg,image/png,image/webp"
                            class="block w-full rounded-xl border border-slate-300 text-sm text-slate-600 file:mr-3 file:border-0 file:bg-indigo-50 file:px-3 file:py-2.5 file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        >
                        <p class="text-xs text-slate-500">JPG, PNG, atau WEBP; maksimal 5 MB.</p>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('catalogue.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                        Simpan perubahan
                    </button>
                </div>
            </form>
        </div>
    </main>
</x-app-layout>
