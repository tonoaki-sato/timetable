<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        protected StaffService $staffService
    ) {}

    /**
     * スタッフ一覧・検索画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Staff::class);

        $viewData = $this->staffService->getIndexViewData(
            search: $request->input('search'),
            status: $request->input('status')
        );

        return view('staff.index', $viewData);
    }

    /**
     * スタッフ新規登録画面の表示
     */
    public function create(): View
    {
        $this->authorize('create', Staff::class);

        return view('staff.create');
    }

    /**
     * スタッフ新規登録処理
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $this->authorize('create', Staff::class);

        $staff = $this->staffService->createStaff($request->validated());

        return redirect()->route('staff.index')
            ->with('success', "スタッフ「{$staff->name}」を登録しました。");
    }

    /**
     * スタッフ詳細画面の表示
     */
    public function show(Staff $staff): View
    {
        $this->authorize('view', $staff);

        $staff->load(['assignments.task.timetable']);

        return view('staff.show', [
            'staff' => $staff,
        ]);
    }

    /**
     * スタッフ編集画面の表示
     */
    public function edit(Staff $staff): View
    {
        $this->authorize('update', $staff);

        return view('staff.edit', [
            'staff' => $staff,
        ]);
    }

    /**
     * スタッフ情報更新処理
     */
    public function update(UpdateStaffRequest $request, Staff $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $this->staffService->updateStaff($staff, $request->validated());

        return redirect()->route('staff.show', $staff)
            ->with('success', "スタッフ「{$staff->name}」の情報を更新しました。");
    }

    /**
     * スタッフ削除処理
     */
    public function destroy(Staff $staff): RedirectResponse
    {
        $this->authorize('delete', $staff);

        $name = $staff->name;
        $this->staffService->deleteStaff($staff);

        return redirect()->route('staff.index')
            ->with('success', "スタッフ「{$name}」を削除しました。");
    }

    /**
     * スタッフのアクセストークン再生成処理
     */
    public function regenerateToken(Staff $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $this->staffService->regenerateAccessToken($staff);

        return redirect()->route('staff.show', $staff)
            ->with('reissued_success', true)
            ->with('success', "スタッフ「{$staff->name}」の名札トークンを再発行しました。以前のQRコードとURLは無効化（404）されました。新しい名札カードを印刷してください。");
    }
}
