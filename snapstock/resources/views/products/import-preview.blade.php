<section x-show="step === 'import-review'" class="space-y-6">
            <header class="flex items-center gap-3 border-b border-slate-200 pb-5">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-800 sm:text-2xl">Tinjau impor katalog</h1>
                    <p class="text-sm text-slate-500">{{ count($rows) }} baris ditemukan. Periksa perubahan sebelum mengimpor.</p>
                </div>
            </header>

            @if ($invalidCount > 0)
                <div role="alert" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    {{ $invalidCount }} baris memiliki masalah. Perbaiki file CSV dan unggah kembali; belum ada produk yang disimpan.
                </div>
            @else
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    Semua baris valid. Produk bernama sama di katalogmu akan diperbarui; produk lain akan ditambahkan.
                </div>
            @endif

            @php($fieldLabels = ['name' => 'Nama', 'category' => 'Kategori', 'description' => 'Deskripsi', 'price' => 'Harga', 'stock' => 'Stok', 'unit' => 'Satuan'])
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full min-w-[1050px] divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Baris</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Nama</th>
                            <th scope="col" class="px-4 py-3">Kategori</th>
                            <th scope="col" class="px-4 py-3">Deskripsi</th>
                            <th scope="col" class="px-4 py-3">Harga</th>
                            <th scope="col" class="px-4 py-3">Stok</th>
                            <th scope="col" class="px-4 py-3">Perubahan / masalah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($rows as $row)
                            <tr class="{{ $row['errors'] !== [] ? 'bg-red-50/60' : '' }}">
                                <td class="whitespace-nowrap px-4 py-3">{{ $row['number'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($row['errors'] !== [])
                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">Periksa</span>
                                    @elseif ($row['action'] === 'update')
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Perbarui</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Tambah</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium">{{ $row['product']['name'] }}</td>
                                <td class="px-4 py-3">{{ $row['product']['category'] ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $row['product']['description'] ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $row['product']['price'] }}</td>
                                <td class="px-4 py-3">{{ $row['product']['stock'] }} {{ $row['product']['unit'] }}</td>
                                <td class="max-w-md px-4 py-3">
                                    @if ($row['errors'] !== [])
                                        <ul class="list-inside list-disc space-y-1 text-red-700">
                                            @foreach ($row['errors'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @elseif ($row['changes'] !== [])
                                        <ul class="space-y-1 text-xs">
                                            @foreach ($row['changes'] as $field => $change)
                                                <li>
                                                    <strong>{{ $fieldLabels[$field] }}:</strong>
                                                    {{ $change['old'] === '' ? '(kosong)' : $change['old'] }}
                                                    <span aria-hidden="true">→</span>
                                                    {{ $change['new'] === '' ? '(kosong)' : $change['new'] }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif ($row['action'] === 'update')
                                        <span class="text-xs text-slate-500">Tidak ada perubahan</span>
                                    @else
                                        <span class="text-xs text-slate-500">Produk baru</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($invalidCount === 0)
                <form method="POST" action="{{ route('products.import.store') }}" class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    @csrf
                    @foreach ($rows as $row)
                        @foreach ($row['product'] as $field => $value)
                            <input type="hidden" name="products[{{ $loop->parent->index }}][{{ $field }}]" value="{{ $value }}">
                        @endforeach
                    @endforeach
                    <a href="{{ route('products.create') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Kembali
                    </a>
                    <button
                        type="submit"
                        class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        Impor katalog
                    </button>
                </form>
            @else
                <a href="{{ route('products.create') }}" class="inline-flex rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Kembali untuk memperbaiki file
                </a>
            @endif
</section>
