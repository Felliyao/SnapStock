<x-app-layout>
    <div
        class="py-6 sm:py-8"
        x-data="{
            searchQuery: '',
            selectedOrder: null,
            transactions: @js($transactions->getCollection()->map(fn ($transaction) => [
                'id' => $transaction->id,
                'date' => $transaction->created_at->format('d/m/Y'),
                'time' => $transaction->created_at->format('H:i'),
                'status' => $transaction->status,
                'total' => (float) $transaction->total,
                'paymentMethod' => match ($transaction->payment_method) {
                    'cash' => 'Tunai',
                    'qris' => 'QRIS',
                    'transfer' => 'Transfer',
                    default => null,
                },
                'amountPaid' => $transaction->amount_paid !== null ? (float) $transaction->amount_paid : null,
                'changeAmount' => $transaction->change_amount !== null ? (float) $transaction->change_amount : null,
                'items' => $transaction->items->map(fn ($item) => [
                    'name' => $item->product_name,
                    'qty' => $item->quantity,
                    'unit' => $item->unit,
                    'price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ])->values(),
            ])->values()),

            get filteredTransactions() {
                const query = this.searchQuery.trim().toLocaleLowerCase('id-ID');

                if (!query) {
                    return this.transactions;
                }

                return this.transactions.filter(transaction => {
                    const transactionId = String(transaction.id);
                    const formattedId = transactionId.padStart(6, '0');

                    return transactionId.includes(query) ||
                        formattedId.includes(query) ||
                        transaction.items.some(item => item.name.toLocaleLowerCase('id-ID').includes(query));
                });
            },

            formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0,
                }).format(Number(amount) || 0);
            },

            showDetails(transaction) {
                this.selectedOrder = transaction;
            },

            printTransaction() {
                window.print();
            }
        }"
    >
        <div class="mx-auto max-w-[1400px] space-y-6 px-4 sm:px-6 lg:px-8">
            <p class="text-xs font-medium text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600">Dashboard</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-600">Transaksi</span>
            </p>
            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-center">
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-800">Transaksi</h1>
                    <span
                        class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-600"
                        x-text="filteredTransactions.length + ' Transaksi'"
                    ></span>
                </div>

                <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                    <div class="relative min-w-[240px]">
                        <input
                            type="search"
                            x-model="searchQuery"
                            placeholder="Cari nomor transaksi atau produk..."
                            aria-label="Cari nomor transaksi atau produk"
                            class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-4 pr-10 text-xs text-slate-700 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                        <svg class="absolute right-3 top-2.5 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                            <path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"></path>
                        </svg>
                    </div>

                    <div class="hidden items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-500 shadow-sm xl:flex">
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="16" rx="2" stroke-width="2"></rect>
                            <path d="M16 3v4M8 3v4M3 11h18" stroke-width="2" stroke-linecap="round"></path>
                        </svg>
                        <span>{{ now()->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <template x-for="transaction in filteredTransactions" :key="transaction.id">
                    <article class="flex flex-col justify-between gap-4 rounded-lg border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="space-y-3 border-b border-slate-100 pb-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-700 text-xs font-bold text-white shadow-sm">
                                        TR
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="truncate text-sm font-extrabold text-slate-800" x-text="'Transaksi #' + String(transaction.id).padStart(6, '0')"></h2>
                                        <p class="text-[11px] font-medium text-slate-400" x-text="transaction.items.length + ' produk'"></p>
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700"
                                    x-text="transaction.status === 'completed' ? 'Selesai' : transaction.status"
                                ></span>
                            </div>

                            <div class="flex items-center justify-between pt-1 text-[11px] font-medium text-slate-400">
                                <span x-text="transaction.date"></span>
                                <span x-text="transaction.time"></span>
                            </div>
                            <div x-show="transaction.paymentMethod" class="flex items-center justify-between text-[11px] font-medium">
                                <span class="text-slate-400">Metode pembayaran</span>
                                <span class="font-bold text-slate-600" x-text="transaction.paymentMethod"></span>
                            </div>
                        </div>

                        <div class="flex-1 space-y-2">
                            <div class="grid grid-cols-12 border-b border-slate-50 pb-1 text-[11px] font-bold text-slate-400">
                                <div class="col-span-7">Produk</div>
                                <div class="col-span-2 text-center">Qty</div>
                                <div class="col-span-3 text-right">Subtotal</div>
                            </div>

                            <div class="min-h-[110px] space-y-1.5">
                                <template x-for="(item, idx) in transaction.items.slice(0, 3)" :key="idx">
                                    <div class="grid grid-cols-12 items-center text-xs text-slate-700">
                                        <div class="col-span-7 truncate font-medium" x-text="item.name"></div>
                                        <div class="col-span-2 text-center font-semibold text-slate-500" x-text="item.qty"></div>
                                        <div class="col-span-3 text-right font-bold text-slate-800" x-text="formatCurrency(item.subtotal)"></div>
                                    </div>
                                </template>

                                <template x-if="transaction.items.length > 3">
                                    <button
                                        type="button"
                                        class="block w-full pt-1 text-center text-[11px] font-medium text-slate-400 hover:underline"
                                        @click="showDetails(transaction)"
                                        x-text="'+' + (transaction.items.length - 3) + ' produk lainnya'"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-3 border-t border-slate-100 pt-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-slate-400">Total</span>
                                <span class="text-base font-extrabold text-slate-800" x-text="formatCurrency(transaction.total)"></span>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="printTransaction()"
                                    class="rounded-lg border border-sky-200 bg-sky-50 px-2 py-2 text-center text-[10px] font-bold text-sky-700 transition hover:bg-sky-100"
                                >
                                    Cetak
                                </button>
                                <button
                                    type="button"
                                    @click="showDetails(transaction)"
                                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-2 text-center text-[10px] font-bold text-emerald-700 transition hover:bg-emerald-100"
                                >
                                    Detail
                                </button>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            <div
                x-show="filteredTransactions.length === 0"
                class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-12 text-center"
                style="display: none;"
            >
                <p class="font-semibold text-slate-700">
                    {{ $transactions->isEmpty() ? 'Belum ada transaksi' : 'Transaksi tidak ditemukan' }}
                </p>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $transactions->isEmpty() ? 'Transaksi yang diselesaikan melalui halaman tambah transaksi akan muncul di sini.' : 'Coba kata kunci lain.' }}
                </p>
            </div>

            @if ($transactions->hasPages())
                <div>{{ $transactions->links() }}</div>
            @endif

            <div
                x-show="selectedOrder !== null"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                style="display: none;"
                @keydown.escape.window="selectedOrder = null"
            >
                <section
                    @click.outside="selectedOrder = null"
                    class="w-full max-w-lg space-y-4 rounded-lg bg-white p-6 shadow-md"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="transaction-detail-title"
                >
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2
                                id="transaction-detail-title"
                                class="text-lg font-extrabold text-slate-800"
                                x-text="'Detail Transaksi #' + (selectedOrder ? String(selectedOrder.id).padStart(6, '0') : '')"
                            ></h2>
                            <p
                                class="text-xs text-slate-400"
                                x-text="selectedOrder ? selectedOrder.date + ' • ' + selectedOrder.time : ''"
                            ></p>
                        </div>
                        <button
                            type="button"
                            @click="selectedOrder = null"
                            class="text-lg font-bold text-slate-400 hover:text-slate-600"
                            aria-label="Tutup detail transaksi"
                        >
                            ×
                        </button>
                    </div>

                    <template x-if="selectedOrder">
                        <div class="space-y-4">
                            <div class="max-h-60 divide-y divide-slate-100 overflow-y-auto">
                                <template x-for="(item, idx) in selectedOrder.items" :key="idx">
                                    <div class="flex items-center justify-between gap-4 py-2 text-xs">
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-slate-800" x-text="item.name"></p>
                                            <p class="text-[11px] text-slate-400" x-text="item.qty + ' ' + item.unit + ' × ' + formatCurrency(item.price)"></p>
                                        </div>
                                        <span class="shrink-0 font-bold text-slate-800" x-text="formatCurrency(item.subtotal)"></span>
                                    </div>
                                </template>
                            </div>

                            <div x-show="selectedOrder.paymentMethod" class="space-y-2 border-t border-slate-100 pt-3 text-xs">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Metode pembayaran</span>
                                    <span class="font-bold text-slate-700" x-text="selectedOrder.paymentMethod"></span>
                                </div>
                                <div x-show="selectedOrder.paymentMethod === 'Tunai'" class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Uang diterima</span>
                                    <span class="font-semibold text-slate-700" x-text="formatCurrency(selectedOrder.amountPaid)"></span>
                                </div>
                                <div x-show="selectedOrder.paymentMethod === 'Tunai'" class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Kembalian</span>
                                    <span class="font-semibold text-slate-700" x-text="formatCurrency(selectedOrder.changeAmount)"></span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-lg bg-slate-50 p-3 text-sm font-extrabold text-slate-800">
                                <span>Total Pembayaran:</span>
                                <span class="text-base text-emerald-600" x-text="formatCurrency(selectedOrder.total)"></span>
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                        <form
                            method="POST"
                            :action="'{{ url('/transactions') }}/' + (selectedOrder ? selectedOrder.id : '')"
                            @submit="return confirm('Hapus transaksi ini? Stok produk akan dikembalikan.')"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="rounded-lg bg-red-50 px-4 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100"
                            >
                                Hapus
                            </button>
                        </form>
                        <button
                            type="button"
                            @click="selectedOrder = null"
                            class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                        >
                            Tutup
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
