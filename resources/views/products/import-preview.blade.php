<section x-show="step === 'import-review'" class="space-y-6">
            <header class="flex items-center gap-3 border-b border-slate-200 pb-5">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-800 sm:text-2xl">Tinjau impor katalog</h1>
                    <p class="text-sm text-slate-500">{{ count($rows) }} baris ditemukan. Periksa dan edit perubahan sebelum mengimpor.</p>
                </div>
            </header>

            @if ($invalidCount > 0)
                <div role="alert" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    {{ $invalidCount }} baris memiliki masalah. Perbaiki file CSV dan unggah kembali; belum ada produk yang disimpan.
                </div>
            @else
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    Semua baris valid. Produk bernama sama di katalogmu akan diperbarui; produk lain akan ditambahkan. Kolom foto dan bahan baku opsional.
                </div>
            @endif

            @php($fieldLabels = ['name' => 'Nama', 'category' => 'Kategori', 'price' => 'Harga', 'stock' => 'Stok', 'unit' => 'Satuan'])

            @if ($invalidCount === 0)
                <form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
            @endif

            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full min-w-[1300px] divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">Baris</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Nama</th>
                            <th scope="col" class="px-4 py-3">Kategori</th>
                            <th scope="col" class="px-4 py-3">Harga</th>
                            <th scope="col" class="px-4 py-3">Stok</th>
                            <th scope="col" class="px-4 py-3">Satuan</th>
                            <th scope="col" class="px-4 py-3">Perubahan / masalah</th>
                            <th scope="col" class="px-4 py-3">Foto &amp; bahan baku</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($rows as $rowIndex => $row)
                            <tr x-data="{ materials: [] }" class="{{ $row['errors'] !== [] ? 'bg-red-50/60' : '' }}">
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
                                <td class="px-4 py-3">
                                    @if ($invalidCount === 0)
                                        <input
                                            type="text"
                                            name="products[{{ $rowIndex }}][name]"
                                            value="{{ $row['product']['name'] }}"
                                            maxlength="255"
                                            required
                                            class="w-full min-w-36 rounded-lg border-slate-300 text-sm font-medium text-slate-800 focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                    @else
                                        <span class="font-medium">{{ $row['product']['name'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($invalidCount === 0)
                                        <input
                                            type="text"
                                            name="products[{{ $rowIndex }}][category]"
                                            value="{{ $row['product']['category'] ?? '' }}"
                                            maxlength="100"
                                            class="w-full min-w-28 rounded-lg border-slate-300 text-sm text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                    @else
                                        {{ $row['product']['category'] ?: '—' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($invalidCount === 0)
                                        <input
                                            type="number"
                                            name="products[{{ $rowIndex }}][price]"
                                            value="{{ $row['product']['price'] }}"
                                            min="0"
                                            step="0.01"
                                            required
                                            class="w-full min-w-24 rounded-lg border-slate-300 text-sm text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                    @else
                                        {{ $row['product']['price'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($invalidCount === 0)
                                        <input
                                            type="number"
                                            name="products[{{ $rowIndex }}][stock]"
                                            value="{{ $row['product']['stock'] }}"
                                            min="0"
                                            step="1"
                                            required
                                            class="w-full min-w-20 rounded-lg border-slate-300 text-sm text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                    @else
                                        {{ $row['product']['stock'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($invalidCount === 0)
                                        <input
                                            type="text"
                                            name="products[{{ $rowIndex }}][unit]"
                                            value="{{ $row['product']['unit'] }}"
                                            maxlength="30"
                                            required
                                            class="w-full min-w-20 rounded-lg border-slate-300 text-sm text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                    @else
                                        {{ $row['product']['unit'] }}
                                    @endif
                                </td>
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
                                <td class="px-4 py-3">
                                    @if ($invalidCount === 0)
                                        <details class="group">
                                            <summary class="cursor-pointer select-none text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                                                Atur foto &amp; bahan baku
                                            </summary>
                                            <div class="mt-2 space-y-3">
                                                <div>
                                                    <label class="mb-1 block text-[11px] font-medium text-slate-600">Foto produk (opsional)</label>
                                                    <input
                                                        type="file"
                                                        name="products[{{ $rowIndex }}][image]"
                                                        accept="image/jpeg,image/png,image/webp"
                                                        class="w-full max-w-56 text-xs text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-2.5 file:py-1.5 file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                                                    >
                                                </div>
                                                <div>
                                                    <p class="mb-1 text-[11px] font-medium text-slate-600">Kebutuhan bahan baku (opsional)</p>
                                                    <template x-for="(material, materialIndex) in materials" :key="materialIndex">
                                                        <div class="mb-1.5 flex items-center gap-1.5">
                                                            <select
                                                                :name="`products[{{ $rowIndex }}][raw_materials][${materialIndex}][raw_material_id]`"
                                                                x-model="material.raw_material_id"
                                                                class="min-w-0 flex-1 rounded-lg border-slate-300 text-xs text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"
                                                            >
                                                                <option value="">Pilih bahan baku</option>
                                                                <template x-for="option in rawMaterials" :key="option.id">
                                                                    <option
                                                                        :value="option.id"
                                                                        :disabled="materials.some((selected, selectedIndex) => selectedIndex !== materialIndex && Number(selected.raw_material_id) === Number(option.id))"
                                                                        x-text="`${option.name} (${option.unit})`"
                                                                    ></option>
                                                                </template>
                                                            </select>
                                                            <input
                                                                type="number"
                                                                :name="`products[{{ $rowIndex }}][raw_materials][${materialIndex}][quantity_per_product]`"
                                                                x-model="material.quantity_per_product"
                                                                min="0"
                                                                max="4294967295"
                                                                step="0.001"
                                                                placeholder="Jumlah"
                                                                class="w-20 rounded-lg border-slate-300 text-xs text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"
                                                            >
                                                            <button
                                                                type="button"
                                                                @click="materials.splice(materialIndex, 1)"
                                                                class="rounded-lg px-1.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                                                                aria-label="Hapus bahan baku"
                                                            >×</button>
                                                        </div>
                                                    </template>
                                                    <button
                                                        type="button"
                                                        @click="materials.push({ raw_material_id: '', quantity_per_product: '' })"
                                                        :disabled="materials.length >= rawMaterials.length"
                                                        class="rounded-lg border border-emerald-200 bg-white px-2.5 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                    >
                                                        + Bahan baku
                                                    </button>
                                                </div>
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($invalidCount === 0)
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ route('products.create') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Kembali
                        </a>
                        <button
                            type="submit"
                            class="rounded-lg bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                        >
                            Impor katalog
                        </button>
                    </div>
                </form>
            @else
                <a href="{{ route('products.create') }}" class="inline-flex rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Kembali untuk memperbaiki file
                </a>
            @endif
</section>
