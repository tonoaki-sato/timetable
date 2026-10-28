<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffJoinRequest;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class StaffJoinController extends Controller
{
    public function __construct(
        protected StaffService $staffService
    ) {}

    /**
     * スタッフセルフ登録画面の表示
     */
    public function create(): View
    {
        return view('staff.join');
    }

    /**
     * スタッフセルフ登録処理
     */
    public function store(StoreStaffJoinRequest $request): RedirectResponse
    {
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
                if ($staff) {
                    return view('staff.join-completed', ['staff' => $staff]);
                }
            }

            return redirect()->route('staff.join');
        }

        $staff = Staff::find($staffId);
        if (! $staff) {
            return redirect()->route('staff.join');
        }

        return view('staff.join-completed', [
            'staff' => $staff,
        ]);
    }
}
