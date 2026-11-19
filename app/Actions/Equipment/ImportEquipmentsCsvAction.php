<?php

namespace App\Actions\Equipment;

use App\Enums\EquipmentCheckoutRule;
use App\Enums\EquipmentType;
use App\Http\Requests\StoreEquipmentRequest;
use App\Models\Equipment;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Services\EquipmentService;
use App\Support\CsvSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportEquipmentsCsvAction
{
    public function __construct(
        protected EquipmentRepositoryInterface $equipmentRepository,
        protected EquipmentService $equipmentService,
    ) {}

    /**
     * CSVファイルから資機材マスターを一括取り込み（全行検証＋トランザクション）
     *
     * @return array{created: int, updated: int, total: int}
     *
     * @throws ValidationException
     */
    public function execute(UploadedFile $file, ?int $userId = null): array
    {
        $rows = CsvSanitizer::parseUploadedCsv($file);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'csv_file' => 'CSVファイルにデータ行が含まれていません。',
            ]);
        }

        $firstRow = $rows[0];
        $hasHeader = isset($firstRow[0]) && (
            $firstRow[0] === 'ID'
            || str_contains($firstRow[0], '備品名')
            || (isset($firstRow[1]) && str_contains($firstRow[1], '備品名'))
        );

        $usesNineColumns = count($firstRow) >= 9
            || ($hasHeader && strtoupper($firstRow[0] ?? '') === 'ID');

        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        if ($dataRows === []) {
            throw ValidationException::withMessages([
                'csv_file' => '取り込み対象のデータ行がありません（ヘッダー行のみ）。',
            ]);
        }

        $rules = (new StoreEquipmentRequest)->rules();
        $attributes = [
            'name' => '備品名',
            'type' => '区分',
            'checkout_rule' => '持ち出しルール',
            'quantity' => '基準数量',
            'is_approximate_quantity' => '概数フラグ',
            'unit' => '単位',
            'storage_location' => '定常保管場所',
            'description' => '備考',
        ];

        $validatedRows = [];
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $lineNumber = $hasHeader ? $index + 2 : $index + 1;

            if ($usesNineColumns || count($row) >= 9) {
                $rawId = $row[0] ?? '';
                $offset = 1;
            } else {
                $rawId = '';
                $offset = 0;
            }

            $normalizedType = $this->normalizeType($row[$offset + 1] ?? '');
            $normalizedRule = $this->normalizeCheckoutRule($row[$offset + 2] ?? '');
            $rawQuantity = $row[$offset + 3] ?? '';
            $approx = $this->normalizeBoolean($row[$offset + 5] ?? '');
            $storageLocation = ($row[$offset + 6] ?? '') !== '' ? $row[$offset + 6] : null;
            if ($normalizedType === EquipmentType::Rental->value) {
                $storageLocation = null;
            }

            $payload = [
                'name' => $row[$offset] ?? '',
                'type' => $normalizedType,
                'checkout_rule' => $normalizedRule,
                'quantity' => is_numeric($rawQuantity) ? (int) $rawQuantity : $rawQuantity,
                'unit' => ($row[$offset + 4] ?? '') !== '' ? $row[$offset + 4] : '個',
                'is_approximate_quantity' => $approx,
                'storage_location' => $storageLocation,
                'description' => ($row[$offset + 7] ?? '') !== '' ? $row[$offset + 7] : null,
            ];

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

        return DB::transaction(function () use ($validatedRows, $userId): array {
            $existingAll = $this->equipmentRepository->getAllOrdered();
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
                    $this->equipmentService->updateEquipment($target, $row['data']);
                    $updated++;
                } else {
                    $createdEquipment = $this->equipmentService->createEquipment($row['data'], $userId);
                    $byId->put($createdEquipment->id, $createdEquipment);
                    $byName->put($createdEquipment->name, $createdEquipment);
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

    /**
     * 区分文字列（キーまたは日本語表記）をEnum値へ正規化
     */
    protected function normalizeType(string $raw): string
    {
        $val = trim($raw);
        foreach (EquipmentType::cases() as $case) {
            if (in_array($val, [$case->value, $case->label(), $case->shortLabel(), Equipment::TYPES[$case->value] ?? ''], true)) {
                return $case->value;
            }
        }

        return match ($val) {
            '実行委員会' => EquipmentType::Committee->value,
            'イベント会社', 'レンタル' => EquipmentType::Rental->value,
            default => $val,
        };
    }

    /**
     * 持ち出しルール文字列（キーまたは日本語表記）をEnum値へ正規化
     */
    protected function normalizeCheckoutRule(string $raw): string
    {
        $val = trim($raw);
        if ($val === '') {
            return EquipmentCheckoutRule::Free->value;
        }

        foreach (EquipmentCheckoutRule::cases() as $case) {
            if (in_array($val, [$case->value, $case->label(), $case->shortLabel(), Equipment::CHECKOUT_RULES[$case->value] ?? ''], true)) {
                return $case->value;
            }
        }

        return match ($val) {
            '自由持出', '自由' => EquipmentCheckoutRule::Free->value,
            '許可制' => EquipmentCheckoutRule::PermissionRequired->value,
            default => $val,
        };
    }

    /**
     * 概数フラグ文字列をboolへ変換
     */
    protected function normalizeBoolean(string $raw): bool
    {
        $val = mb_strtolower(trim($raw));

        return in_array($val, ['1', 'true', 'yes', 'はい', '概数', '○'], true);
    }
}
