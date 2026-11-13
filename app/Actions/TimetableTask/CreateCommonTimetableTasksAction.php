<?php

namespace App\Actions\TimetableTask;

use App\DTOs\TimetableTask\CreateCommonTimetableTaskDTO;
use App\Models\TimetableTask;
use App\Services\TimetableTaskService;
use Illuminate\Database\Eloquent\Collection;

class CreateCommonTimetableTasksAction
{
    public function __construct(
        protected TimetableTaskService $taskService,
    ) {}

    /**
     * 指定された複数担当班へ共通タスクを一括作成する
     *
     * @return Collection<int, TimetableTask>
     */
    public function execute(CreateCommonTimetableTaskDTO $dto): Collection
    {
        return $this->taskService->createCommonTasksForLanes($dto);
    }
}
