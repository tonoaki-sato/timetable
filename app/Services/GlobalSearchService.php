<?php

namespace App\Services;

use App\DTOs\GlobalSearchResultDTO;
use App\Models\Equipment;
use App\Models\Schedule;
use App\Models\Staff;
use App\Models\TimetableTask;
use App\Models\User;
use App\Models\Vendor;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;

class GlobalSearchService
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
        protected VendorRepositoryInterface $vendorRepository,
        protected EquipmentRepositoryInterface $equipmentRepository,
        protected ScheduleRepositoryInterface $scheduleRepository,
        protected TimetableTaskRepositoryInterface $timetableTaskRepository,
    ) {}

    /**
     * システム横断クイック検索を実行
     *
     * @return array{
     *     query: string,
     *     total: int,
     *     groups: array<int, array{
     *         key: string,
     *         label: string,
     *         icon: string,
     *         items: array<int, array{
     *             category: string,
     *             category_label: string,
     *             icon: string,
     *             title: string,
     *             subtitle: string,
     *             url: string,
     *             badge: ?string
     *         }>
     *     }>
     * }
     */
    public function search(string $rawQuery, User $user, int $limitPerCategory = 5): array
    {
        $keyword = trim($rawQuery);
        if ($keyword === '') {
            return [
                'query' => '',
                'total' => 0,
                'groups' => [],
            ];
        }

        $groups = [];
        $total = 0;

        if ($user->can('viewAny', Staff::class)) {
            $staffItems = $this->staffRepository->searchQuick($keyword, $limitPerCategory)
                ->map(function (Staff $staff): array {
                    $subtitle = implode('・', array_filter([$staff->kana, $staff->affiliation]));

                    return (new GlobalSearchResultDTO(
                        category: 'staff',
                        categoryLabel: 'スタッフ',
                        icon: '👷',
                        title: $staff->name,
                        subtitle: $subtitle !== '' ? $subtitle : 'スタッフ台帳',
                        url: route('staff.show', $staff),
                        badge: $staff->is_active ? '稼働可能' : '停止中',
                    ))->toArray();
                })
                ->values()
                ->all();

            if ($staffItems !== []) {
                $total += count($staffItems);
                $groups[] = [
                    'key' => 'staff',
                    'label' => 'スタッフ',
                    'icon' => '👷',
                    'items' => $staffItems,
                ];
            }
        }

        if ($user->can('viewAny', Vendor::class)) {
            $vendorItems = $this->vendorRepository->searchQuick($keyword, $limitPerCategory)
                ->map(function (Vendor $vendor): array {
                    $subtitle = implode('・', array_filter([$vendor->contact_person, $vendor->contact]));

                    return (new GlobalSearchResultDTO(
                        category: 'vendors',
                        categoryLabel: '出展・出店者',
                        icon: '🎪',
                        title: $vendor->name,
                        subtitle: $subtitle !== '' ? $subtitle : '出展・出店者マスター',
                        url: route('vendors.index', ['search' => $vendor->name]),
                        badge: '出展・出店者',
                    ))->toArray();
                })
                ->values()
                ->all();

            if ($vendorItems !== []) {
                $total += count($vendorItems);
                $groups[] = [
                    'key' => 'vendors',
                    'label' => '出展・出店者',
                    'icon' => '🎪',
                    'items' => $vendorItems,
                ];
            }
        }

        if ($user->can('viewAny', Equipment::class)) {
            $equipmentItems = $this->equipmentRepository->searchQuick($keyword, $limitPerCategory)
                ->map(function (Equipment $equipment): array {
                    $subtitle = implode('・', array_filter([
                        $equipment->type_enum->shortLabel(),
                        $equipment->storage_location_name,
                    ]));

                    return (new GlobalSearchResultDTO(
                        category: 'equipments',
                        categoryLabel: '機材・備品',
                        icon: '📦',
                        title: $equipment->name,
                        subtitle: $subtitle !== '' ? $subtitle : '機材・備品台帳',
                        url: route('equipments.index', ['keyword' => $equipment->name]),
                        badge: $equipment->formatted_quantity,
                    ))->toArray();
                })
                ->values()
                ->all();

            if ($equipmentItems !== []) {
                $total += count($equipmentItems);
                $groups[] = [
                    'key' => 'equipments',
                    'label' => '機材・備品',
                    'icon' => '📦',
                    'items' => $equipmentItems,
                ];
            }
        }

        if ($user->can('viewAny', Schedule::class)) {
            $scheduleItems = $this->scheduleRepository->searchQuick($keyword, $limitPerCategory)
                ->map(function (Schedule $schedule): array {
                    $subtitle = implode('・', array_filter([
                        $schedule->start_date?->format('Y/m/d'),
                        $schedule->location,
                    ]));

                    return (new GlobalSearchResultDTO(
                        category: 'schedules',
                        categoryLabel: 'スケジュール',
                        icon: '📅',
                        title: $schedule->title,
                        subtitle: $subtitle !== '' ? $subtitle : '年間スケジュール',
                        url: route('schedules.show', $schedule),
                        badge: Schedule::STATUSES[$schedule->status] ?? $schedule->status,
                    ))->toArray();
                })
                ->values()
                ->all();

            if ($scheduleItems !== []) {
                $total += count($scheduleItems);
                $groups[] = [
                    'key' => 'schedules',
                    'label' => 'スケジュール',
                    'icon' => '📅',
                    'items' => $scheduleItems,
                ];
            }
        }

        if ($user->can('viewAny', TimetableTask::class)) {
            $taskItems = $this->timetableTaskRepository->searchQuick($keyword, $limitPerCategory)
                ->map(function (TimetableTask $task): array {
                    $timeRange = ($task->start_time && $task->end_time)
                        ? substr((string) $task->start_time, 0, 5).'〜'.substr((string) $task->end_time, 0, 5)
                        : null;
                    $subtitle = implode('・', array_filter([
                        $task->timetable?->name,
                        $task->timetableLane?->name,
                        $timeRange,
                    ]));
                    $url = $task->timetable_id
                        ? route('timetables.assignments', [$task->timetable_id, 'task_id' => $task->id])
                        : route('timetable-tasks.index');

                    return (new GlobalSearchResultDTO(
                        category: 'tasks',
                        categoryLabel: '作業タスク',
                        icon: '📋',
                        title: $task->title,
                        subtitle: $subtitle !== '' ? $subtitle : '作業タスク',
                        url: $url,
                        badge: TimetableTask::STATUSES[$task->status] ?? null,
                    ))->toArray();
                })
                ->values()
                ->all();

            if ($taskItems !== []) {
                $total += count($taskItems);
                $groups[] = [
                    'key' => 'tasks',
                    'label' => '作業タスク',
                    'icon' => '📋',
                    'items' => $taskItems,
                ];
            }
        }

        return [
            'query' => $keyword,
            'total' => $total,
            'groups' => $groups,
        ];
    }
}
