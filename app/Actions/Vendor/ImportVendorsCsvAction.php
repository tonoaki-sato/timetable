<?php

namespace App\Actions\Vendor;

use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Support\CsvSanitizer;
use App\Support\Validation\VendorValidationRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportVendorsCsvAction
{
    public function __construct(
        protected VendorRepositoryInterface $vendorRepository
    ) {}

    /**
     * CSVファイルから出展・出店者マスターを一括取り込み（全行検証＋トランザクション）
     *
     * @return array{created: int, updated: int, total: int}
     *
     * @throws ValidationException
     */
    public function execute(UploadedFile $file): array
    {
        $rows = CsvSanitizer::parseUploadedCsv($file);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'CSVファイルにデータ行が含まれていません。',
            ]);
        }

        // 先頭行がヘッダー行か判定
        $firstRow = $rows[0];
        $hasHeader = isset($firstRow[0]) && (
            $firstRow[0] === 'ID'
            || str_contains($firstRow[0], '出展')
            || (isset($firstRow[1]) && str_contains($firstRow[1], '出展'))
        );

        $usesFiveColumns = count($firstRow) >= 5
            || ($hasHeader && strtoupper($firstRow[0] ?? '') === 'ID');

        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        if ($dataRows === []) {
            throw ValidationException::withMessages([
                'csv_file' => '取り込み対象のデータ行がありません（ヘッダー行のみ）。',
            ]);
        }

        $rules = VendorValidationRules::rules();
        $attributes = VendorValidationRules::attributes();

        $validatedRows = [];
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $lineNumber = $hasHeader ? $index + 2 : $index + 1;

            if ($usesFiveColumns || count($row) >= 5) {
                $rawId = $row[0] ?? '';
                $payload = [
                    'name' => $row[1] ?? '',
                    'contact_person' => ($row[2] ?? '') !== '' ? $row[2] : null,
                    'contact' => ($row[3] ?? '') !== '' ? $row[3] : null,
                    'notes' => ($row[4] ?? '') !== '' ? $row[4] : null,
                ];
            } else {
                $rawId = '';
                $payload = [
                    'name' => $row[0] ?? '',
                    'contact_person' => ($row[1] ?? '') !== '' ? $row[1] : null,
                    'contact' => ($row[2] ?? '') !== '' ? $row[2] : null,
                    'notes' => ($row[3] ?? '') !== '' ? $row[3] : null,
                ];
            }

            $validator = Validator::make($payload, $rules, [], $attributes);
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $msg) {
                    $errors[] = "{$lineNumber}行目: {$msg}";
                }

                continue;
            }

            $validatedRows[] = [
                'id' => ctype_digit($rawId) ? (int) $rawId : null,
                'data' => $validator->validated(),
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'csv_file' => $errors,
            ]);
        }

        return DB::transaction(function () use ($validatedRows): array {
            $existingAll = $this->vendorRepository->getAllBasic();
            $byId = $existingAll->keyBy('id');
            $byName = $existingAll->keyBy('name');

            $created = 0;
            $updated = 0;

            foreach ($validatedRows as $row) {
                $target = null;
                if ($row['id'] !== null && $byId->has($row['id'])) {
                    $target = $byId->get($row['id']);
                } elseif ($byName->has($row['data']['name'])) {
                    $target = $byName->get($row['data']['name']);
                }

                if ($target !== null) {
                    $fresh = $this->vendorRepository->updateWithoutReload($target, $row['data']);
                    $byId->put($fresh->id, $fresh);
                    $byName->put($fresh->name, $fresh);
                    $updated++;
                } else {
                    $fresh = $this->vendorRepository->create($row['data']);
                    $byId->put($fresh->id, $fresh);
                    $byName->put($fresh->name, $fresh);
                    $created++;
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'total' => $created + $updated,
            ];
        });
    }
}
