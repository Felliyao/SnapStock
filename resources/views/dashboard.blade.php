<x-app-layout>
    <div
        class="flex min-h-[calc(100vh-4rem)] items-center justify-center bg-slate-50 px-4 py-8 sm:px-6 lg:px-8"
        x-data="{
            activeModal: @js(in_array(old('stock_action'), ['restock', 'create_material'], true) ? 'stok' : null),
            successMessage: '',
            products: @js($products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => (int) $product->stock,
                'unit' => $product->unit ?: 'pcs',
            ])->values()),
            rawMaterials: @js($rawMaterials->map(fn ($material) => [
                'id' => $material->id,
                'name' => $material->name,
                'stock' => (float) $material->stock,
                'unit' => $material->unit ?: 'pcs',
                'location' => $material->location ?: 'Gudang Utama',
            ])->values()),
            selectedItem: @js($products->first()?->id ?? null),
            selectedRawMaterial: @js($rawMaterials->first()?->id ?? null),
            qty: 1,
            materialQty: 1,
            stokTab: @js(old('stock_action') === 'restock' ? 'produk' : 'new-material'),
            bahanMode: 'manual',
            targetBahan: 'Tepung Terigu Segitiga Biru 1kg',
            stokBahanSaatIni: 1.5,
            satuanBahan: 'kg',
            jumlahTambahBahan: 5,
            expDateBahan: '',
            notaImagePreview: null,
            scannedItems: [],
            scanLoading: false,
            scanError: '',

            async scanNota(file) {
                if (!file) {
                    return;
                }
                this.notaImagePreview = URL.createObjectURL(file);
                this.scanLoading = true;
                this.scanError = '';
                this.scannedItems = [];
                const body = new FormData();
                body.append('image', file);
                try {
                    const response = await fetch(@js(route('materials.scan-receipt')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body,
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        this.scanError = data.message || 'Scan nota gagal. Coba lagi.';
                        return;
                    }
                    this.scannedItems = (data.items || []).map((item) => ({
                        name: item.name,
                        quantity: item.quantity,
                        unit: item.unit,
                        price: item.price ?? null,
                        raw_material_id: this.matchMaterialId(item.name),
                    }));
                    if (this.scannedItems.length === 0) {
                        this.scanError = 'AI tidak menemukan item pada nota. Coba foto yang lebih jelas.';
                    }
                } catch (error) {
                    this.scanError = 'Gagal menghubungi server. Periksa koneksi lalu coba lagi.';
                } finally {
                    this.scanLoading = false;
                }
            },

            matchMaterialId(name) {
                const normalize = (value) => (value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
                const target = normalize(name);
                let bestId = '';
                let bestScore = 0;
                for (const material of this.rawMaterials) {
                    const candidate = normalize(material.name);
                    let score = 0;
                    if (candidate === target) {
                        score = 100;
                    } else if (candidate.includes(target) || target.includes(candidate)) {
                        score = 50;
                    } else {
                        score = target.split(' ').filter((word) => word.length > 2 && candidate.includes(word)).length * 10;
                    }
                    if (score > bestScore) {
                        bestScore = score;
                        bestId = String(material.id);
                    }
                }
                return bestScore >= 20 ? bestId : '';
            },

            triggerSuccess(msg) {
                this.successMessage = msg;
                setTimeout(() => {
                    this.successMessage = '';
                    this.activeModal = null;
                }, 1500);
            }
        }"
    >
        <div class="mx-auto w-full max-w-7xl space-y-8">
            @if (session('status'))
                <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <header class="space-y-2 text-center">
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-800 sm:text-4xl">SnapStock</h1>
                <p class="text-sm font-medium text-slate-600">Sistem POS &amp; Manajemen Stok Instan</p>
            </header>

            <section aria-label="Aksi cepat" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <article class="flex flex-col items-center gap-4 rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm transition hover:border-indigo-300">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600" aria-hidden="true"><x-heroicon-o-sparkles class="h-6 w-6" /></div>
                    <a href="{{ route('products.create') }}" class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-center text-lg font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                        Tambah Produk
                    </a>
                    <a href="{{ route('catalogue.index') }}" class="text-sm font-semibold text-indigo-700 underline-offset-4 hover:text-indigo-900 hover:underline">
                        Kelola Produk <span aria-hidden="true">→</span>
                    </a>
                </article>

                <article class="flex flex-col items-center gap-4 rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm transition hover:border-emerald-300">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600" aria-hidden="true"><x-heroicon-o-shopping-cart class="h-6 w-6" /></div>
                    <a href="{{ route('pos.index') }}" class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-center text-lg font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        Tambah Transaksi
                    </a>
                    <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-emerald-700 underline-offset-4 hover:text-emerald-900 hover:underline">
                        Kelola Transaksi <span aria-hidden="true">→</span>
                    </a>
                </article>

                <article class="flex flex-col items-center gap-4 rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm transition hover:border-amber-300">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50 text-amber-600" aria-hidden="true"><x-heroicon-o-archive-box class="h-6 w-6" /></div>
                    <button type="button" @click="activeModal = 'stok'" class="w-full rounded-lg bg-amber-600 px-4 py-3 text-lg font-bold text-white transition hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">
                        Tambah Stok
                    </button>
                    <a href="{{ route('inventory.index') }}" class="text-sm font-semibold text-amber-700 underline-offset-4 hover:text-amber-900 hover:underline">
                        Kelola Stok <span aria-hidden="true">→</span>
                    </a>
                </article>
            </section>

        </div>

        <div
            x-show="activeModal === 'stok'"
            x-cloak
            @click.self="activeModal = null"
            @keydown.escape.window="activeModal = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            style="display: none;"
        >
            <div @click.away="activeModal = null" class="w-full max-w-lg space-y-4 rounded-lg bg-white p-6 shadow-md">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800">
                        <x-heroicon-o-archive-box class="h-5 w-5 text-amber-600" aria-hidden="true" />
                        <span>Manajemen &amp; Restock Stok</span>
                    </h3>
                    <button @click="activeModal = null" class="text-lg font-bold text-slate-500 hover:text-slate-800">×</button>
                </div>

                <div class="flex border-b border-slate-200">
                    <button
                        @click="stokTab = 'new-material'"
                        :class="stokTab === 'new-material' ? 'border-amber-600 text-amber-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="flex-1 border-b-2 pb-2 text-center text-xs transition sm:text-sm"
                    >
                        + Bahan Baru
                    </button>
                    <button
                        @click="stokTab = 'produk'"
                        :class="stokTab === 'produk' ? 'border-amber-600 text-amber-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="flex-1 border-b-2 pb-2 text-center text-xs transition sm:text-sm"
                    >
                        <x-heroicon-o-cake class="mr-1 inline h-3.5 w-3.5 align-[-0.125em]" aria-hidden="true" />Stok Produk
                    </button>
                    <button
                        @click="stokTab = 'bahan'"
                        :class="stokTab === 'bahan' ? 'border-amber-600 text-amber-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="flex-1 border-b-2 pb-2 text-center text-xs transition sm:text-sm"
                    >
                        <x-heroicon-o-fire class="mr-1 inline h-3.5 w-3.5 align-[-0.125em]" aria-hidden="true" />Restock Bahan
                    </button>
                </div>

                @if ($products->isNotEmpty() || $rawMaterials->isNotEmpty())
                    @if ($products->isNotEmpty())
                    <form x-show="stokTab === 'produk'" method="POST" action="{{ route('products.stock.store') }}" class="space-y-4" style="display: none;">
                        @csrf
                        <input type="hidden" name="stock_action" value="restock">

                        <div class="space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-800">Pilih Produk Jualan:</label>
                                <select x-model="selectedItem" name="product_id" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id || $loop->first)>
                                            {{ $product->name }} (Sisa: {{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-800">Jumlah Tambah Stok (Box/Pcs):</label>
                                <input
                                    type="number"
                                    name="quantity"
                                    x-model.number="qty"
                                    min="1"
                                    value="{{ old('quantity', 1) }}"
                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                >
                            </div>

                            <div class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs font-semibold text-amber-800">
                                <span>Stok Akhir Etalase Kasir:</span>
                                <span class="text-sm font-extrabold" x-text="(products.find(item => item.id === Number(selectedItem))?.stock || 0) + (qty || 0) + ' item'"></span>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 border-t pt-3">
                            <button type="button" @click="activeModal = null" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Batal</button>
                            <button type="submit" class="rounded-lg bg-amber-600 px-5 py-2 text-xs font-bold text-white shadow-md transition hover:bg-amber-700">
                                Simpan Restock
                            </button>
                        </div>
                    </form>
                    @else
                        <p x-show="stokTab === 'produk'" class="text-sm text-slate-600">Belum ada produk jualan untuk direstock.</p>
                    @endif

                    @if ($rawMaterials->isNotEmpty())
                    <form x-show="stokTab === 'bahan'" method="POST" action="{{ route('materials.stock.store') }}" class="space-y-4" style="display: none;">
                        @csrf
                        <input type="hidden" name="stock_action" value="restock">

                        <div class="space-y-4">
                            <div class="flex space-x-2 rounded-lg bg-slate-100 p-1 text-xs font-semibold">
                                <button type="button" @click="bahanMode = 'manual'" :class="bahanMode === 'manual' ? 'bg-white text-slate-800 shadow' : 'text-slate-500'" class="flex-1 rounded-lg py-1.5 transition">
                                    <x-heroicon-o-bolt class="mr-1 inline h-3.5 w-3.5 align-[-0.125em]" aria-hidden="true" />Input Manual
                                </button>
                                <button type="button" @click="bahanMode = 'scan'" :class="bahanMode === 'scan' ? 'bg-indigo-600 text-white shadow' : 'text-slate-500'" class="flex-1 rounded-lg py-1.5 transition">
                                    <x-heroicon-o-camera class="mr-1 inline h-3.5 w-3.5 align-[-0.125em]" aria-hidden="true" />Scan Nota AI
                                </button>
                            </div>

                            <div x-show="bahanMode === 'manual'" class="space-y-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-800">Pilih Bahan Baku Dapur:</label>
                                    <select x-model="selectedRawMaterial" name="raw_material_id" :disabled="bahanMode !== 'manual'" required class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-500 focus:ring-amber-500">
                                        @foreach ($rawMaterials as $material)
                                            <option value="{{ $material->id }}" @selected((string) old('raw_material_id') === (string) $material->id || $loop->first)>
                                                {{ $material->name }} (Sisa: {{ number_format((float) $material->stock, 3, ',', '.') }} {{ $material->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-800">Jumlah Tambah:</label>
                                        <input type="number" name="quantity" x-model.number="materialQty" :disabled="bahanMode !== 'manual'" min="0.001" step="0.001" value="1" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-500 focus:ring-amber-500" placeholder="5">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-800">Satuan:</label>
                                        <input type="text" :value="rawMaterials.find(item => item.id === Number(selectedRawMaterial))?.unit || 'pcs'" class="w-full rounded-lg border-slate-300 bg-slate-100 text-xs" readonly>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-800">Tanggal Kedaluwarsa (Opsional):</label>
                                    <input type="date" name="expiration_date" x-model="expDateBahan" :disabled="bahanMode !== 'manual'" class="w-full rounded-lg border-slate-300 text-xs focus:border-amber-500 focus:ring-amber-500">
                                </div>
                            </div>

                            <div x-show="bahanMode === 'scan'" class="space-y-3" style="display: none;">
                                <div class="rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-4 text-center transition hover:border-indigo-500">
                                    <input type="file" @change="scanNota($event.target.files[0])" accept="image/*" class="hidden" id="nota-upload">

                                    <label for="nota-upload" class="block cursor-pointer space-y-2">
                                        <template x-if="!notaImagePreview">
                                            <div>
                                                <div class="mx-auto mb-1 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><x-heroicon-o-camera class="h-5 w-5" aria-hidden="true" /></div>
                                                <p class="text-xs font-bold text-slate-800">Ambil Foto Nota Belanja Pasar</p>
                                                <p class="text-[11px] text-slate-400">AI akan mengekstraksi bahan &amp; kuantitas otomatis</p>
                                            </div>
                                        </template>

                                        <template x-if="notaImagePreview">
                                            <div class="space-y-1">
                                                <img :src="notaImagePreview" class="mx-auto h-28 rounded-lg border border-slate-200 object-cover shadow-sm">
                                                <p class="text-[11px] font-bold text-indigo-600" x-show="!scanLoading">Nota siap diproses AI.</p>
                                            </div>
                                        </template>
                                    </label>
                                </div>

                                <div x-show="scanLoading" class="flex items-center justify-center gap-2 rounded-lg bg-indigo-50 p-3 text-xs font-semibold text-indigo-700">
                                    <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" aria-hidden="true" />
                                    <span>Memproses nota dengan AI...</span>
                                </div>

                                <p x-show="scanError" x-text="scanError" class="rounded-lg bg-red-50 p-3 text-xs font-semibold text-red-700"></p>

                                <div x-show="scannedItems.length > 0" class="space-y-2">
                                    <p class="text-[11px] font-bold text-slate-500">Hasil scan — cocokkan dengan bahan baku Anda:</p>
                                    <template x-for="(item, index) in scannedItems" :key="index">
                                        <div class="grid grid-cols-12 items-end gap-2 rounded-lg border border-slate-200 bg-white p-2.5">
                                            <div class="col-span-12 sm:col-span-5">
                                                <label class="mb-1 block truncate text-[10px] font-semibold text-slate-500" :title="item.name" x-text="item.name + (item.price ? ' · ±Rp ' + Number(item.price).toLocaleString('id-ID') : '')"></label>
                                                <select :name="'items['+index+'][raw_material_id]'" x-model="item.raw_material_id" class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">— Pilih bahan —</option>
                                                    @foreach ($rawMaterials as $material)
                                                        <option value="{{ $material->id }}">{{ $material->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-span-6 sm:col-span-3">
                                                <label class="mb-1 block text-[10px] font-semibold text-slate-500">Jumlah</label>
                                                <input type="number" :name="'items['+index+'][quantity]'" x-model.number="item.quantity" min="0.001" step="0.001" class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <div class="col-span-4 sm:col-span-2">
                                                <label class="mb-1 block text-[10px] font-semibold text-slate-500">Satuan</label>
                                                <input type="text" :value="item.unit" class="w-full rounded-lg border-slate-300 bg-slate-100 text-xs" readonly>
                                            </div>
                                            <div class="col-span-2 flex justify-end sm:col-span-2">
                                                <button type="button" @click="scannedItems.splice(index, 1)" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" aria-label="Hapus item">
                                                    <x-heroicon-o-trash class="h-4 w-4" aria-hidden="true" />
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 border-t pt-3">
                            <button type="button" @click="activeModal = null" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Batal</button>
                            <button type="submit" formaction="{{ route('materials.stock.store') }}" x-show="bahanMode === 'manual'" class="rounded-lg bg-amber-600 px-5 py-2 text-xs font-bold text-white shadow-md transition hover:bg-amber-700">
                                Simpan Restock
                            </button>
                            <button type="submit" formaction="{{ route('materials.restock-batch') }}" x-show="bahanMode === 'scan'" :disabled="scannedItems.length === 0 || scanLoading" class="rounded-lg bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-md transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                                Simpan <span x-text="scannedItems.length"></span> Item
                            </button>
                        </div>
                    </form>
                    @else
                        <p x-show="stokTab === 'bahan'" class="text-sm text-slate-600">Belum ada bahan baku untuk direstock. Tambahkan bahan baku dari halaman Kelola Stok.</p>
                    @endif
                @else
                    <div class="space-y-4">
                        <p x-show="stokTab === 'produk'" class="text-sm text-slate-600">Belum ada produk jualan untuk direstock.</p>
                        <p x-show="stokTab === 'bahan'" class="text-sm text-slate-600">Belum ada bahan baku untuk direstock. Gunakan tab Bahan Baru untuk menambah bahan.</p>
                    </div>
                @endif

                <form x-show="stokTab === 'new-material'" method="POST" action="{{ route('materials.store') }}" class="space-y-4" style="display: none;">
                    @csrf
                    <input type="hidden" name="stock_action" value="create_material">

                    <div class="space-y-3">
                        <div>
                            <label for="new-material-name" class="mb-1 block text-sm font-medium text-slate-800">Nama Bahan Baku:</label>
                            <input id="new-material-name" type="text" name="name" value="{{ old('stock_action') === 'create_material' ? old('name') : '' }}" maxlength="255" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" placeholder="Contoh: Tepung Terigu Protein ">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="new-material-category" class="mb-1 block text-sm font-medium text-slate-800">Kategori:</label>
                                <input id="new-material-category" type="text" name="category" value="{{ old('stock_action') === 'create_material' ? old('category') : '' }}" maxlength="100" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" placeholder="Contoh: Tepung">
                            </div>
                            <div>
                                <label for="new-material-location" class="mb-1 block text-sm font-medium text-slate-800">Lokasi:</label>
                                <input id="new-material-location" type="text" name="location" value="{{ old('stock_action') === 'create_material' ? old('location') : '' }}" maxlength="255" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" placeholder="Gudang Utama">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="new-material-stock" class="mb-1 block text-sm font-medium text-slate-800">Stok Awal:</label>
                                <input id="new-material-stock" type="number" name="stock" value="{{ old('stock_action') === 'create_material' ? old('stock', 0) : 0 }}" min="0" max="4294967295" step="0.001" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            </div>
                            <div>
                                <label for="new-material-unit" class="mb-1 block text-sm font-medium text-slate-800">Satuan:</label>
                                <input id="new-material-unit" type="text" name="unit" value="{{ old('stock_action') === 'create_material' ? old('unit', 'kg') : 'kg' }}" maxlength="30" required class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" placeholder="kg, liter, pcs">
                            </div>
                        </div>

                        <div>
                            <label for="new-material-expiration" class="mb-1 block text-sm font-medium text-slate-800">Tanggal Kedaluwarsa (Opsional):</label>
                            <input id="new-material-expiration" type="date" name="expiration_date" value="{{ old('stock_action') === 'create_material' ? old('expiration_date') : '' }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 border-t pt-3">
                        <button type="button" @click="activeModal = null" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">Batal</button>
                        <button type="submit" class="rounded-lg bg-amber-600 px-5 py-2 text-xs font-bold text-white shadow-md transition hover:bg-amber-700">
                            Simpan Bahan Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
