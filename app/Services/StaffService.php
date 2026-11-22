<?php

namespace App\Services;

use App\DTOs\Staff\StaffShowViewDataDTO;
use App\Enums\StaffAttribute;
use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class StaffService
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
        protected QRCodeService $qrCodeService,
    ) {}

    /**
     * IDによるスタッフ取得
     */
    public function findById(int $id): ?Staff
    {
        return $this->staffRepository->findById($id);
    }

    /**
     * スタッフ詳細画面用の表示データを構築して取得
     */
    public function getShowViewData(Staff $staff): StaffShowViewDataDTO
    {
        $shiftUrl = $staff->presenter()->myShiftUrl();

        return new StaffShowViewDataDTO(
            staff: $staff,
            shiftUrl: $shiftUrl,
            qrDataUri: $this->qrCodeService->generatePngDataUri($shiftUrl, 220),
        );
    }

    /**
     * スタッフ一覧画面データの取得
     *
     * @return array{
     *     staffMembers: LengthAwarePaginator,
     *     search: ?string,
     *     status: ?string,
     *     staffAttribute: ?string,
     *     staffAttributeFilterOptions: array<string, string>
     * }
     */
    public function getIndexViewData(
        ?string $search,
        ?string $status,
        ?string $staffAttribute = null,
        int $perPage = 15,
        ?string $sort = null,
        ?string $direction = null,
    ): array {
        $statusBool = $this->parseStatusFilter($status);

        $staffMembers = $this->staffRepository->paginateFiltered(
            search: $search,
            status: $statusBool,
            perPage: $perPage,
            sort: $sort,
            direction: $direction,
            staffAttribute: $staffAttribute,
        );

        return [
            'staffMembers' => $staffMembers,
            'search' => $search,
            'status' => $status,
            'staffAttribute' => $staffAttribute,
            'staffAttributeFilterOptions' => StaffAttribute::filterOptions(),
        ];
    }

    /**
     * スタッフ一覧PDF用の表示データを構築
     *
     * @return array{
     *     title: string,
     *     pages: SupportCollection<int, array{left: SupportCollection<int, Staff>, right: SupportCollection<int, Staff>, rowCount: int}>,
     *     totalCount: int,
     *     filterText: string,
     *     generatedAt: string
     * }
     */
    public function getPdfViewData(?string $search = null, ?string $status = null, ?string $staffAttribute = null): array
    {
        $statusBool = $this->parseStatusFilter($status);

        $staffs = $this->staffRepository->getFiltered($search, $statusBool, $staffAttribute);

        // 1ページあたり最大60名（30行×2段組）でページ分割し、各ページ内で左右列に均等バランス配置
        $maxRowsPerColumn = 30;
        $maxPerPage = $maxRowsPerColumn * 2;
        $pages = $staffs->chunk($maxPerPage)->map(function ($pageStaffs) {
            $count = $pageStaffs->count();
            $half = (int) ceil($count / 2);

            return [
                'left' => $pageStaffs->slice(0, $half)->values(),
                'right' => $pageStaffs->slice($half)->values(),
                'rowCount' => $half,
            ];
        });

        $filterDescriptions = [];
        if ($search !== null && $search !== '') {
            $filterDescriptions[] = "検索: 「{$search}」";
        }
        if ($status === '1') {
            $filterDescriptions[] = '状態: 稼働可能';
        } elseif ($status === '0') {
            $filterDescriptions[] = '状態: 停止中';
        }
        if ($staffAttribute === StaffAttribute::FILTER_EXECUTIVE_GROUP) {
            $filterDescriptions[] = '属性: 幹事・幹事会';
        } elseif ($staffAttribute !== null && ($attrEnum = StaffAttribute::tryFrom($staffAttribute)) !== null) {
            $filterDescriptions[] = "属性: {$attrEnum->label()}";
        }

        return [
            'title' => 'スタッフ一覧',
            'pages' => $pages,
            'totalCount' => $staffs->count(),
            'filterText' => ! empty($filterDescriptions) ? implode(' ｜ ', $filterDescriptions) : '全スタッフ',
            'generatedAt' => now()->format('Y年m月d日 H:i'),
        ];
    }

    /**
     * スタッフ新規登録
     *
     * @param  array<string, mixed>  $data
     */
    public function createStaff(array $data): Staff
    {
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;
        $data['staff_attribute'] = (isset($data['staff_attribute']) && $data['staff_attribute'] !== '')
            ? $data['staff_attribute']
            : StaffAttribute::General->value;

        return $this->staffRepository->create($data);
    }

    /**
     * スタッフ情報更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateStaff(Staff $staff, array $data): bool
    {
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : false;
        if (! isset($data['staff_attribute']) || $data['staff_attribute'] === '') {
            unset($data['staff_attribute']);
        }

        return $this->staffRepository->update($staff, $data);
    }

    /**
     * スタッフ削除
     */
    public function deleteStaff(Staff $staff): bool
    {
        return $this->staffRepository->delete($staff);
    }

    /**
     * スタッフ本人によるセルフ登録（既存台帳との自動照合対応）
     *
     * @param  array<string, mixed>  $data
     */
    public function registerSelfStaff(array $data): Staff
    {
        // 氏名・フリガナによる既存台帳照合（本部先行登録スタッフとの結合）
        $existingStaff = null;
        if (! empty($data['name']) && ! empty($data['kana'])) {
            $matched = $this->staffRepository->findByNameAndKana($data['name'], $data['kana']);
            if ($matched->count() === 1) {
                $existingStaff = $matched->first();
            }
        }

        // 既存スタッフが存在する場合は最終確認日時を更新して返却
        if ($existingStaff) {
            $this->staffRepository->update($existingStaff, [
                'last_accessed_at' => now(),
            ]);

            return $existingStaff->fresh();
        }

        // 既存に該当しない場合は新規作成
        $attributes = [
            'name' => $data['name'],
            'kana' => $data['kana'],
            'staff_attribute' => StaffAttribute::General->value,
            'registered_via' => 'self',
            'is_active' => true,
        ];

        return $this->staffRepository->create($attributes);
    }

    /**
     * トークンによるスタッフ特定および最終確認日時の更新
     */
    public function getStaffByToken(string $token): ?Staff
    {
        $staff = $this->staffRepository->findByAccessToken($token);

        if ($staff && ($staff->last_accessed_at === null || $staff->last_accessed_at->lte(now()->subMinutes(5)))) {
            $this->staffRepository->update($staff, [
                'last_accessed_at' => now(),
            ]);
        }

        return $staff;
    }

    /**
     * アクセストークンの再発行（古いトークンを無効化）
     */
    public function regenerateAccessToken(Staff $staff): string
    {
        $newToken = Str::random(32);

        $this->staffRepository->update($staff, [
            'access_token' => $newToken,
            'token_reissued_at' => now(),
            'reissue_count' => $staff->reissue_count + 1,
        ]);

        return $newToken;
    }

    /**
     * ステータス絞り込み文字列 ('1' / '0') を真偽値または null へ正規化
     */
    private function parseStatusFilter(?string $status): ?bool
    {
        if ($status === '1') {
            return true;
        }

        if ($status === '0') {
            return false;
        }

        return null;
    }
}
