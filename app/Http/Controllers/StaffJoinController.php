<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffJoinRequest;
use App\Models\FeatureSetting;
use App\Models\Staff;
use App\Services\FeatureSettingService;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class StaffJoinController extends Controller
{
    public function __construct(
        protected StaffService $staffService,
        protected FeatureSettingService $featureSettingService
    ) {}

    /**
     * スタッフセルフ登録画面の表示
     */
    public function create(Request $request): View|RedirectResponse
    {
        // 1. 二重登録防止: 端末Cookieに有効なスタッフ登録が存在するか判定
        $token = $request->cookie('staff_token');
        if ($token) {
            $existingStaff = $this->staffService->getStaffByToken($token);
            if ($existingStaff && $existingStaff->is_active) {
                return redirect()->route('staff.join.completed')
                    ->with('registered_staff_id', $existingStaff->id)
                    ->with('info', 'すでにスタッフ登録が完了しています。');
            }
        }

        // 2. セルフ登録機能のオープン（オン・オフおよび受付日時）判定
        $status = $this->featureSettingService->getFeatureStatus(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION);
        if (! $status['isOpen']) {
            return view('staff.join-closed', [
                'status' => $status,
            ]);
        }

        return view('staff.join');
    }

    /**
     * スタッフセルフ登録処理
     */
    public function store(StoreStaffJoinRequest $request): RedirectResponse
    {
        // 1. 二重登録防止: 端末Cookieに有効なスタッフ登録が存在するか判定
        $token = $request->cookie('staff_token');
        if ($token) {
            $existingStaff = $this->staffService->getStaffByToken($token);
            if ($existingStaff && $existingStaff->is_active) {
                return redirect()->route('staff.join.completed')
                    ->with('registered_staff_id', $existingStaff->id)
                    ->with('info', 'すでにスタッフ登録が完了しています。');
            }
        }

        // 2. セルフ登録機能のオープン判定
        if (! $this->featureSettingService->isFeatureOpen(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION)) {
            return redirect()->route('staff.join')
                ->with('error', '現在、スタッフセルフ登録の受付は停止しております。');
        }

        $validated = $request->validated();

        $staff = $this->staffService->registerSelfStaff($validated);

        // 端末自動認識用の長期Cookie（30日間）をキューに追加
        Cookie::queue('staff_token', $staff->access_token, 60 * 24 * 30);

        return redirect()->route('staff.join.completed')
            ->with('registered_staff_id', $staff->id)
            ->with('success', 'スタッフ登録が完了しました！');
    }

    /**
     * スタッフセルフ登録完了画面の表示
     */
    public function completed(Request $request): View|RedirectResponse
    {
        $staffId = $request->session()->get('registered_staff_id');

        if (! $staffId) {
            // Cookieからの復帰試行
            $token = $request->cookie('staff_token');
            if ($token) {
                $staff = $this->staffService->getStaffByToken($token);
                if ($staff && $staff->is_active) {
                    return view('staff.join-completed', ['staff' => $staff]);
                }
            }

            return redirect()->route('staff.join');
        }

        $staff = Staff::find($staffId);
        if (! $staff || ! $staff->is_active) {
            return redirect()->route('staff.join');
        }

        return view('staff.join-completed', [
            'staff' => $staff,
        ]);
    }
}
