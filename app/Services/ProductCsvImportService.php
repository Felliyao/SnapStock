<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProductCsvImportService
{
    private const PRODUCT_FIELDS = ['name', 'category', 'price', 'stock', 'unit'];

    private const REQUIRED_HEADERS = ['name', 'price', 'stock'];

    private const MAX_ROWS = 500;

    /**
     * @return array<int, array{number: int, product: array<string, mixed>, errors: array<int, string>, action: string, changes: array<string, array{old: mixed, new: mixed}>}>
     */
    public function preview(UploadedFile $file, User $user): array
    {
        $rows = $this->readRows($file);
        $names = [];

        foreach ($rows as $row) {
            $name = trim((string) $row['product']['name']);

            if ($name !== '' && $row['errors'] === []) {
                $names[] = Str::lower($name);
            }
        }

        $existingProducts = $names === []
            ? collect()
            : $user->products()
                ->whereIn(DB::raw('LOWER(name)'), array_values(array_unique($names)))
                ->orderBy('id')
                ->get()
                ->keyBy(fn (Product $product): string => Str::lower($product->name));

        foreach ($rows as &$row) {
            if ($row['errors'] !== []) {
                continue;
            }

            $name = Str::lower(trim((string) $row['product']['name']));
            $existingProduct = $existingProducts->get($name);
            $row['action'] = $existingProduct === null ? 'create' : 'update';
            $row['changes'] = $existingProduct === null
                ? []
                : $this->changes($existingProduct, $row['product']);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array{created: int, updated: int, unchanged: int}
     */
    public function import(User $user, array $products): array
    {
        $validator = Validator::make(['products' => $products], [
            'products' => ['required', 'array', 'min:1', 'max:'.self::MAX_ROWS],
            'products.*' => ['required', 'array'],
            'products.*.name' => ['required', 'string', 'max:255'],
            'products.*.category' => ['nullable', 'string', 'max:100'],
            'products.*.price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'products.*.stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'products.*.unit' => ['required', 'string', 'max:30'],
            'products.*.image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'products.*.raw_materials' => ['sometimes', 'array', 'max:100'],
            'products.*.raw_materials.*' => ['required', 'array:raw_material_id,quantity_per_product'],
            'products.*.raw_materials.*.raw_material_id' => [
                'required',
                'integer',
                Rule::exists('raw_materials', 'id')->where('user_id', $user->id),
            ],
            'products.*.raw_materials.*.quantity_per_product' => ['required', 'numeric', 'min:0', 'max:4294967295', 'decimal:0,3'],
        ]);
        $validated = $validator->validate()['products'];

        $normalizedNames = [];

        foreach ($validated as $index => $product) {
            $normalizedName = Str::lower(trim($product['name']));

            if (isset($normalizedNames[$normalizedName])) {
                throw ValidationException::withMessages([
                    'products.'.$index.'.name' => 'Nama produk harus unik di dalam file.',
                ]);
            }

            $normalizedNames[$normalizedName] = true;
        }

        return DB::transaction(function () use ($user, $validated, $normalizedNames): array {
            $existingProducts = $user->products()
                ->whereIn(DB::raw('LOWER(name)'), array_keys($normalizedNames))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (Product $product): string => Str::lower($product->name));

            $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

            foreach ($validated as $attributes) {
                $normalizedName = Str::lower(trim($attributes['name']));
                $existingProduct = $existingProducts->get($normalizedName);
                $image = $attributes['image'] ?? null;
                $rawMaterials = $attributes['raw_materials'] ?? null;
                unset($attributes['image'], $attributes['raw_materials'], $attributes['description']);

                $newImagePath = null;

                if ($image instanceof UploadedFile) {
                    $newImagePath = $image->storePublicly('products', 'public');

                    if ($newImagePath === false) {
                        throw new RuntimeException('The product image could not be stored.');
                    }

                    $attributes['image_path'] = $newImagePath;
                }

                if ($existingProduct === null) {
                    $product = $user->products()->create($attributes);

                    if ($rawMaterials !== null) {
                        $product->rawMaterials()->sync($this->recipeRequirements($rawMaterials));
                    }

                    $existingProducts->put($normalizedName, $product);
                    $counts['created']++;

                    continue;
                }

                $oldImagePath = $existingProduct->image_path;
                $existingProduct->fill($attributes);

                if ($existingProduct->isDirty() || $rawMaterials !== null) {
                    $existingProduct->save();

                    if ($rawMaterials !== null) {
                        $existingProduct->rawMaterials()->sync($this->recipeRequirements($rawMaterials));
                    }

                    if ($newImagePath !== null && $oldImagePath !== null) {
                        Storage::disk('public')->delete($oldImagePath);
                    }

                    $counts['updated']++;

                    continue;
                }

                $counts['unchanged']++;
            }

            return $counts;
        });
    }

    /**
     * @param  array<int, array{raw_material_id: int|string, quantity_per_product: int|float|string}>  $rawMaterials
     * @return array<int, array{quantity_per_product: int|float|string}>
     */
    private function recipeRequirements(array $rawMaterials): array
    {
        $requirements = [];

        foreach ($rawMaterials as $rawMaterial) {
            $quantity = (float) $rawMaterial['quantity_per_product'];

            if ($quantity > 0) {
                $requirements[(int) $rawMaterial['raw_material_id']] = [
                    'quantity_per_product' => $quantity,
                ];
            }
        }

        return $requirements;
    }

    /**
     * @return array<int, array{number: int, product: array<string, mixed>, errors: array<int, string>, action: string, changes: array<string, array{old: mixed, new: mixed}>}>
     */
    private function readRows(UploadedFile $file): array
    {
        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('The uploaded CSV file could not be opened.');
        }

        try {
            $headerLine = fgets($stream);

            if ($headerLine === false) {
                throw ValidationException::withMessages(['file' => 'File CSV kosong atau tidak dapat dibaca.']);
            }

            rewind($stream);
            $delimiterCounts = [
                ',' => substr_count($headerLine, ','),
                ';' => substr_count($headerLine, ';'),
                "\t" => substr_count($headerLine, "\t"),
            ];
            $delimiter = array_search(max($delimiterCounts), $delimiterCounts, true);
            $headers = fgetcsv($stream, null, $delimiter, '"', '');

            if ($headers === false) {
                throw ValidationException::withMessages(['file' => 'File CSV kosong atau tidak dapat dibaca.']);
            }

            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            $headers = array_map(fn (mixed $header): string => Str::lower(trim((string) $header)), $headers);

            if (count($headers) !== count(array_unique($headers))) {
                throw ValidationException::withMessages(['file' => 'Nama kolom di file CSV tidak boleh duplikat.']);
            }

            if (array_diff(self::REQUIRED_HEADERS, $headers) !== []) {
                throw ValidationException::withMessages([
                    'file' => 'CSV harus memiliki kolom name, price, dan stock. Unduh template untuk melihat formatnya.',
                ]);
            }

            $rows = [];
            $lineNumber = 1;

            while (($columns = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
                $lineNumber++;

                if ($columns === [null] || collect($columns)->every(fn (mixed $value): bool => trim((string) $value) === '')) {
                    continue;
                }

                if (count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages([
                        'file' => 'File CSV tidak boleh berisi lebih dari '.self::MAX_ROWS.' produk.',
                    ]);
                }

                $product = array_fill_keys(self::PRODUCT_FIELDS, null);
                $errors = [];

                if (count($columns) > count($headers)) {
                    $errors[] = 'Jumlah nilai pada baris ini tidak sesuai dengan header CSV.';
                }

                foreach ($headers as $index => $header) {
                    if (in_array($header, self::PRODUCT_FIELDS, true)) {
                        $product[$header] = trim((string) ($columns[$index] ?? ''));
                    }
                }

                $product['category'] = $product['category'] === '' ? null : $product['category'];
                $product['unit'] = $product['unit'] === null || $product['unit'] === '' ? 'pcs' : $product['unit'];

                $validator = Validator::make($product, [
                    'name' => ['required', 'string', 'max:255'],
                    'category' => ['nullable', 'string', 'max:100'],
                    'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
                    'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
                    'unit' => ['required', 'string', 'max:30'],
                ]);

                $errors = array_merge($errors, $validator->errors()->all());
                $rows[] = [
                    'number' => $lineNumber,
                    'product' => $product,
                    'errors' => $errors,
                    'action' => 'create',
                    'changes' => [],
                ];
            }

            if ($rows === []) {
                throw ValidationException::withMessages(['file' => 'File CSV tidak berisi produk untuk diimpor.']);
            }

            $nameRows = [];

            foreach ($rows as $index => $row) {
                if ($row['errors'] === []) {
                    $nameRows[Str::lower(trim((string) $row['product']['name']))][] = $index;
                }
            }

            foreach ($nameRows as $indices) {
                if (count($indices) > 1) {
                    foreach ($indices as $index) {
                        $rows[$index]['errors'][] = 'Nama produk muncul lebih dari sekali di file CSV.';
                    }
                }
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  array<string, mixed>  $newAttributes
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function changes(Product $product, array $newAttributes): array
    {
        $changes = [];

        foreach (self::PRODUCT_FIELDS as $field) {
            $oldValue = $product->getAttribute($field);
            $newValue = $newAttributes[$field];

            if ($field === 'price') {
                $oldValue = number_format((float) $oldValue, 2, '.', '');
                $newValue = number_format((float) $newValue, 2, '.', '');
            } elseif ($field === 'stock') {
                $oldValue = (string) $oldValue;
                $newValue = (string) $newValue;
            } elseif ($field === 'category') {
                $oldValue = $oldValue ?? '';
                $newValue = $newValue ?? '';
            }

            if ($oldValue !== $newValue) {
                $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        return $changes;
    }
}
