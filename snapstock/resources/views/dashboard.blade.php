<x-app-layout>
    <!-- Alpine.js State untuk Modal Pop-up & Data Simulasi -->
    <div class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-8 sm:px-6 lg:px-8" x-data="{ 
        activeModal: null,
        successMessage: '',
        
        // State form Tambah Transaksi (POS)
        selectedItem: 'Donat Cokelat (Rp 35.000)', qty: 1,
        
        // State form Tambah Stok
        targetBarang: 'Donat Cokelat', jumlahTambah: 10,

        triggerSuccess(msg) {
            this.successMessage = msg;
            setTimeout(() => { this.successMessage = ''; this.activeModal = null; }, 1500);
        }
    }">
        <div class="w-full max-w-7xl space-y-6">

            <!-- NOTIFIKASI SUKSES GLOBAL -->
            <div x-show="successMessage" class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm p-4 rounded-xl flex items-center space-x-2 shadow-sm" style="display: none;">
                <span>✅ <span x-text="successMessage"></span></span>
            </div>

            <header class="space-y-3 text-center">
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-800 sm:text-4xl">
                    ⚡ SnapStock ⚡
                </h1>
                <p class="text-sm font-medium text-indigo-700">Sistem POS & Manajemen Stok</p>
            </header>

            <!-- ========================================== -->
            <!-- 1. 3 PILIHAN UTAMA (CARD AKSI CEPAT) -->
            <!-- ========================================== -->
            <div class="flex flex-col gap-4 lg:flex-row">
                
                <!-- Pilihan 1: Tambah Barang (Navigasi ke Halaman AI Khusus) -->
                <div class="flex flex-1 flex-col items-center gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-indigo-300 transition">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-2xl text-indigo-600">
                        ✨
                    </div>
                    <a href="{{ route('products.ai-create') }}" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-center text-lg font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                        Tambah Produk
                    </a>
                    <a class="text-sm font-semibold text-indigo-600 hover:underline">
                        Kelola Katalog <span aria-hidden="true">→</span>
                    </a>
                </div>

                <!-- Pilihan 2: Tambah Transaksi (POS) -->
                <div class="flex flex-1 flex-col items-center gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-2xl text-emerald-600">
                            🛒
                    </div>
                    <button type="button" @click="activeModal = 'transaksi'" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-center text-lg font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                        Tambah Transaksi
                    </button>
                    <div class="text-sm font-semibold text-emerald-600">
                        Kelola Transaksi <span aria-hidden="true">→</span>
                    </div>
                </div>

                <!-- Pilihan 3: Tambah Stok (Inventory) -->
                <div class="flex flex-1 flex-col items-center gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-2xl text-indigo-600">
                            📦
                    </div>
                    <button type="button" @click="activeModal = 'stok'" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-center text-lg font-bold text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                        Tambah Stok
                    </button>
                    <div class="text-sm font-semibold text-indigo-600">
                        Kelola Stok <span aria-hidden="true">→</span>
                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- MODAL POP-UP 2: TAMBAH TRANSAKSI (POS) -->
            <!-- ========================================== -->
            <div x-show="activeModal === 'transaksi'" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
                <div @click.away="activeModal = null" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                    <div class="flex justify-between items-center border-b pb-3">
                        <h3 class="font-bold text-lg text-slate-800">🛒 Tambah Transaksi Kasir</h3>
                        <button @click="activeModal = null" class="text-slate-500 hover:text-slate-800 font-bold text-lg">×</button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-800 mb-1">Pilih Produk:</label>
                            <select x-model="selectedItem" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                <option>Donat Cokelat Lumer (Rp 35.000)</option>
                                <option>Snack Box Arisan (Rp 15.000)</option>
                                <option>Es Kopi Susu Aren (Rp 18.000)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-800 mb-1">Jumlah Beli (Qty):</label>
                            <input type="number" x-model="qty" value="1" min="1" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                        </div>
                        <div class="bg-emerald-50 p-3 rounded-xl flex justify-between items-center text-sm font-bold text-slate-800">
                            <span>Status Pembayaran:</span>
                            <span class="text-emerald-600">Lunas (Cash / QRIS)</span>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-3 border-t">
                        <button @click="activeModal = null" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium">Batal</button>
                        <button @click="triggerSuccess('Transaksi berhasil dicatat & struk kasir dibuat!')" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium">Proses & Bayar</button>
                    </div>
                </div>
            </div>


            <!-- ========================================== -->
            <!-- MODAL POP-UP 3: TAMBAH STOK -->
            <!-- ========================================== -->
            <div x-show="activeModal === 'stok'" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display: none;">
                <div @click.away="activeModal = null" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                    <div class="flex justify-between items-center border-b pb-3">
                        <h3 class="font-bold text-lg text-slate-800">📦 Tambah / Restock Barang</h3>
                        <button @click="activeModal = null" class="text-slate-500 hover:text-slate-800 font-bold text-lg">×</button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-800 mb-1">Pilih Barang yang Direstock:</label>
                            <select x-model="targetBarang" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option>Donat Cokelat Lumer (Stok saat ini: 20)</option>
                                <option>Snack Box Arisan (Stok saat ini: 30)</option>
                                <option>Es Kopi Susu Aren (Stok saat ini: 25)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-800 mb-1">Jumlah Penambahan Stok:</label>
                            <input type="number" x-model="jumlahTambah" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Contoh: 10">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-3 border-t">
                        <button @click="activeModal = null" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium">Batal</button>
                        <button @click="triggerSuccess('Stok berhasil ditambahkan/diperbarui!')" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium">Simpan Perubahan Stok</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>