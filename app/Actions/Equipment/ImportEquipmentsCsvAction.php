<?php

namespace App\Actions\Equipment;

use App\Enums\EquipmentCheckoutRule;
use App\Enums\EquipmentType;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Services\EquipmentService;
use App\Support\CsvSanitizer;
use App\Support\Validation\EquipmentValidationRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportEquipmentsCsvAction
{
    public function __construct(
        protected EquipmentRepositoryInterface $equipmentRepository,
        protected LocationRepositoryInterface $locationRepository,
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

        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        if ($dataRows === []) {
            throw ValidationException::withMessages([
                'csv_file' => '取り込み対象のデータ行がありません（ヘッダー行のみ）。',
            ]);
        }

        $rules = EquipmentValidationRules::rules();
        $attributes = EquipmentValidationRules::attributes();
        $locationIdByName = $this->locationRepository->getNameIdMap();

        $validatedRows = [];
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $lineNumber = $hasHeader ? $index + 2 : $index + 1;

            if (count($row) < 9) {
                $errors[] = "{$lineNumber}行目: 列数が不足しています（9列形式のCSVを指定してください）。";

                continue;
            }

            $rawId = trim((string) ($row[0] ?? ''));
            $normalizedType = $this->normalizeType((string) ($row[2] ?? ''));
            $normalizedRule = $this->normalizeCheckoutRule((string) ($row[3] ?? ''));
            $rawQuantity = $row[4] ?? '';
            $unit = ($row[5] ?? '') !== '' ? (string) $row[5] : '個';
            $approx = $this->normalizeBoolean((string) ($row[6] ?? ''));
            $rawStorage = trim((string) ($row[7] ?? ''));
            $description = ($row[8] ?? '') !== '' ? (string) $row[8] : null;

            $storageError = null;
            if ($normalizedType === EquipmentType::Rental->value || $rawStorage === '') {
                $storageLocationId = null;
            } elseif (array_key_exists($rawStorage, $locationIdByName)) {
                $storageLocationId = $locationIdByName[$rawStorage];
            } else {
                $storageLocationId = null;
                $storageError = "{$lineNumber}行目: 定常保管場所「{$rawStorage}」は拠点マスターに登録されていません。";
            }

            $payload = [
                'name' => $row[1] ?? '',
                'type' => $normalizedType,
                'checkout_rule' => $normalizedRule,
                'quantity' => is_numeric($rawQuantity) ? (int) $rawQuantity : $rawQuantity,
                'unit' => $unit,
                'is_approximate_quantity' => $approx,
                'storage_location_id' => $storageLocationId,
                'description' => $description,
            ];

            $validator = Validator::make($payload, $rules, [], $attributes);
            if ($storageError !== null || $validator->fails()) {
                if ($storageError !== null) {
                    $errors[] = $storageError;
                }
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
            $nextDisplayOrder = $this->equipmentRepository->getMaxDisplayOrder() + 10;

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
                    $createData = $row['data'];
                    if (! isset($createData['display_order']) || $createData['display_order'] === null) {
                        $createData['display_order'] = $nextDisplayOrder;
                        $nextDisplayOrder += 10;
                    }
                    $createdEquipment = $this->equipmentService->createEquipment($createData, $userId);
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
            if (in_array($val, [$case->value, $case->label(), $case->shortLabel()], true)) {
                return $case->value;
            }
        }

        return match ($val) {
            '実行委員会', '実行委員会管理' => EquipmentType::Committee->value,
            'イベント会社', 'イベント会社レンタル', 'レンタル' => EquipmentType::Rental->value,
            '私物' => EquipmentType::Personal->value,
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
            if (in_array($val, [$case->value, $case->label(), $case->shortLabel()], true)) {
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
