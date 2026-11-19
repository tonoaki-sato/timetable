<?php

namespace App\Support;

use App\Models\FeatureSetting;
use App\Models\LaneRequest;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class NavigationCache
{
    public const string KEY_NAV_TIMETABLES = 'nav.timetables';

    public const string KEY_FEATURE_SETTINGS = 'feature.settings';

    public const string KEY_PENDING_LANE_REQUESTS = 'header.pending_lane_requests';

    public const int TTL_NAV_TIMETABLES = 600;

    public const int TTL_FEATURE_SETTING = 600;

    public const int TTL_PENDING_LANE_REQUESTS = 60;

    /** @var Collection<int, Timetable>|null */
    protected ?Collection $memoizedTimetables = null;

    /** @var array<string, FeatureSetting> */
    protected array $memoizedFeatures = [];

    /** @var array{count: int, recent: Collection<int, LaneRequest>}|null */
    protected ?array $memoizedPendingLaneRequests = null;

    /**
     * ナビゲーション・セレクタ用の未完了タイムテーブル一覧を取得（リクエスト内メモ化 + Cache::remember）
     *
     * @param  callable(): Collection<int, Timetable>  $loader
     * @return Collection<int, Timetable>
     */
    public function getNavTimetables(callable $loader): Collection
    {
        if ($this->memoizedTimetables !== null) {
            return $this->memoizedTimetables;
        }

        /** @var Collection<int, Timetable> $timetables */
        $timetables = Cache::remember(
            self::KEY_NAV_TIMETABLES,
            self::TTL_NAV_TIMETABLES,
            $loader
        );
        $this->memoizedTimetables = $timetables;

        return $timetables;
    }

    /**
     * 機能設定モデルを取得（リクエスト内メモ化 + Cache）
     *
     * @param  callable(): FeatureSetting  $loader
     */
    public function getFeatureSetting(string $featureKey, callable $loader): FeatureSetting
    {
        if (isset($this->memoizedFeatures[$featureKey])) {
            return $this->memoizedFeatures[$featureKey];
        }

        $cachedMap = Cache::get(self::KEY_FEATURE_SETTINGS);
        if (is_array($cachedMap) && isset($cachedMap[$featureKey]) && $cachedMap[$featureKey] instanceof FeatureSetting) {
            $this->memoizedFeatures[$featureKey] = $cachedMap[$featureKey];

            return $cachedMap[$featureKey];
        }

        $setting = $loader();
        $map = is_array($cachedMap) ? $cachedMap : [];
        $map[$featureKey] = $setting;
        Cache::put(self::KEY_FEATURE_SETTINGS, $map, self::TTL_FEATURE_SETTING);
        $this->memoizedFeatures[$featureKey] = $setting;

        return $setting;
    }

    /**
     * ヘッダー通知ベル用の未処理班長要請サマリー（件数＋直近一覧）を取得
     *
     * @param  callable(): array{count: int, recent: Collection<int, LaneRequest>}  $loader
     * @return array{count: int, recent: Collection<int, LaneRequest>}
     */
    public function getPendingLaneRequestsSummary(callable $loader): array
    {
        if ($this->memoizedPendingLaneRequests !== null) {
            return $this->memoizedPendingLaneRequests;
        }

        /** @var array{count: int, recent: Collection<int, LaneRequest>} $summary */
        $summary = Cache::remember(
            self::KEY_PENDING_LANE_REQUESTS,
            self::TTL_PENDING_LANE_REQUESTS,
            $loader
        );
        $this->memoizedPendingLaneRequests = $summary;

        return $summary;
    }

    public function forgetTimetables(): void
    {
        $this->memoizedTimetables = null;
        Cache::forget(self::KEY_NAV_TIMETABLES);
    }

    public function forgetFeature(?string $featureKey = null): void
    {
        if ($featureKey === null) {
            $this->memoizedFeatures = [];
        } else {
            unset($this->memoizedFeatures[$featureKey]);
        }

        Cache::forget(self::KEY_FEATURE_SETTINGS);
    }

    public function forgetPendingLaneRequests(): void
    {
        $this->memoizedPendingLaneRequests = null;
        Cache::forget(self::KEY_PENDING_LANE_REQUESTS);
    }

    /**
     * 対象モデルに対応するキャッシュをまとめて無効化
     */
    public function forgetForModel(Model $model): void
    {
        if ($model instanceof Timetable) {
            $this->forgetTimetables();
            $this->forgetPendingLaneRequests();

            return;
        }

        if ($model instanceof FeatureSetting) {
            $key = $model->getAttribute('feature_key');
            $this->forgetFeature(is_string($key) && $key !== '' ? $key : null);

            return;
        }

        if ($model instanceof LaneRequest) {
            $this->forgetPendingLaneRequests();
        }
    }

    public function clearRequestCache(): void
    {
        $this->memoizedTimetables = null;
        $this->memoizedFeatures = [];
        $this->memoizedPendingLaneRequests = null;
    }
}
