<x-app-layout>
    <div class="py-6 sm:py-8" x-data="{
        searchQuery: '',
        selectedCategory: 'All',
        activeModal: null,
        selectedItem: null,

        items: @js($rawMaterials->map(fn ($material) => [
            'id' => $material->id,
            'name' => $material->name,
            'category' => $material->category ?: 'Lainnya',
            'location' => $material->location ?: 'Gudang Utama',
            'stock' => (int) $material->stock,
            'unit' => $material->unit ?: 'pcs',
            'expirationDate' => $material->expiration_date?->format('Y-m-d'),
            'addedAt' => optional($material->created_at)->format('d-m-Y') ?: now()->format('d-m-Y'),
        ])->values()),

        get filteredItems() {
            return this.items.filter(item => {
                let matchSearch = item.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                  item.category.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                  item.location.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                  String(item.id).includes(this.searchQuery);
                let matchCat = this.selectedCategory === 'All' || item.category === this.selectedCategory;
                return matchSearch && matchCat;
            });
        },

        openEdit(item) {
            this.selectedItem = JSON.parse(JSON.stringify(item));
            this.activeModal = 'edit';
        },

    }">
        <div class="max-w-[1500px] mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div role="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <!-- CARD CONTAINER TABEL KELOLA STOK (STYLE INVENITY) -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                
                <!-- CARD HEADER: TITLE & TOP TOOLBAR BUTTONS -->
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-light text-slate-700 tracking-tight">Stok Bahan Baku Dapur</h1>
                        <p class="text-xs text-slate-400 mt-1">Daftar inventaris bahan makanan, lokasi penyimpanan, dan tanggal kedaluwarsa</p>
                    </div>

                    <!-- TOP ACTIONS (SEARCH, FILTER & BUTTONS REPLICA) -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- SEARCH BAR -->
                        <div class="relative">
                            <input 
                                type="text" 
                                x-model="searchQuery" 
                                placeholder="Cari bahan baku..." 
                                class="bg-slate-50 border border-slate-200 rounded-lg pl-3 pr-8 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-sky-500 w-44 sm:w-56"
                            >
                            <span class="absolute right-2.5 top-2 text-slate-400 text-xs">🔍</span>
                        </div>

                        <!-- FILTER KATEGORI -->
                        <select x-model="selectedCategory" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs text-slate-600 focus:outline-none focus:ring-1 focus:ring-sky-500">
                            <option value="All">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- TABLE INVENITY REPLICA -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-medium">
                            <tr>
                                <th class="py-3.5 px-4 w-12 text-center">#</th>
                                <th class="py-3.5 px-4 w-16">Id</th>
                                <th class="py-3.5 px-4">Nama Bahan</th>
                                <th class="py-3.5 px-4">Kategori</th>
                                <th class="py-3.5 px-4">Lokasi Storage</th>
                                <th class="py-3.5 px-4">Stok / Qty</th>
                                <th class="py-3.5 px-4">Kedaluwarsa (Exp)</th>
                                <th class="py-3.5 px-4">Added at</th>
                                <th class="py-3.5 px-4 text-center w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in filteredItems" :key="item.id">
                                <tr class="hover:bg-sky-50/40 transition">
                                    <td class="py-3.5 px-4 text-center text-slate-400 font-mono" x-text="index + 1"></td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-800 font-mono" x-text="item.id"></td>
                                    <td class="py-3.5 px-4 font-bold text-slate-800" x-text="item.name"></td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md text-[11px] font-medium" x-text="item.category"></span>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500" x-text="item.location"></td>
                                    <td class="py-3.5 px-4 font-bold text-slate-800" x-text="`${item.stock} ${item.unit}`"></td>
                                    
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono" x-text="item.expirationDate || '-' "></span>
                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4 text-slate-400 font-mono" x-text="item.addedAt"></td>
                                    
                                    <!-- ACTIONS (ICON EDIT & HAPUS SESUAI INVENITY) -->
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex items-center justify-center space-x-3 text-slate-400">
                                            <button @click="openEdit(item)" class="hover:text-sky-600 transition" title="Edit Data">
                                                ✏️
                                            </button>
                                            <form method="POST" :action="'{{ url('/materials') }}/' + item.id" @submit="return confirm('Hapus bahan baku ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="hover:text-red-600 transition" title="Hapus Data">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="filteredItems.length === 0">
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-slate-400 text-xs">
                                        Tidak ada bahan baku yang cocok dengan pencarian.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- FOOTER STATS & LEGEND -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                    <div class="flex items-center space-x-4">
                        <span>Menampilkan <strong class="text-slate-700" x-text="filteredItems.length"></strong> dari <strong class="text-slate-700" x-text="items.length"></strong> bahan baku</span>
                    </div>

                    <div class="flex items-center space-x-4 text-[11px]">
                        <span>Tanggal kedaluwarsa ditampilkan jika tersedia.</span>
                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- MODAL EDIT DATA BAHAN BAKU -->
            <!-- ========================================== -->
            <div x-show="activeModal === 'edit'" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
                <div @click.away="activeModal = null" class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4">
                    <div class="flex justify-between items-center border-b pb-3">
                        <h3 class="font-bold text-slate-800 text-base">Edit Bahan Baku Dapur</h3>
                        <button @click="activeModal = null" class="text-slate-400 hover:text-slate-600 font-bold text-base">×</button>
                    </div>

                    <form x-show="selectedItem" method="POST" :action="'{{ url('/materials') }}/' + selectedItem.id" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nama Bahan Baku:</label>
                            <input type="text" name="name" x-model="selectedItem.name" required maxlength="255" class="w-full rounded-lg border-slate-300 text-xs">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Kategori:</label>
                                <input type="text" name="category" x-model="selectedItem.category" maxlength="100" class="w-full rounded-lg border-slate-300 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Lokasi Storage:</label>
                                <input type="text" name="location" x-model="selectedItem.location" maxlength="255" class="w-full rounded-lg border-slate-300 text-xs">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Jumlah / Stok:</label>
                                <input type="number" name="stock" x-model.number="selectedItem.stock" min="0" max="4294967295" required class="w-full rounded-lg border-slate-300 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Satuan:</label>
                                <input type="text" name="unit" x-model="selectedItem.unit" maxlength="30" required class="w-full rounded-lg border-slate-300 text-xs">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Kedaluwarsa:</label>
                            <input type="date" name="expiration_date" x-model="selectedItem.expirationDate" class="w-full rounded-lg border-slate-300 text-xs">
                        </div>

                        <div class="flex justify-end space-x-2 pt-3 border-t">
                            <button type="button" @click="activeModal = null" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium">Batal</button>
                            <button type="submit" class="px-5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-bold shadow-sm">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>