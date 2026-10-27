<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffJoinRequest;
use App\Models\Staff;
use App\Services\LineAuthService;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffJoinController extends Controller
{
    public function __construct(
        protected StaffService $staffService,
        protected LineAuthService $lineAuthService
    ) {}

    /**
     * スタッフセルフ登録画面の表示
     */
    public function create(): View
    {
        return view('staff.join', [
            'liffId' => config('services.line.liff_id', ''),
        ]);
    }

    /**
     * スタッフセルフ登録処理
     */
    public function store(StoreStaffJoinRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! empty($validated['line_id_token'])) {
            $verified = $this->lineAuthService->verifyIdToken($validated['line_id_token']);
            if (! $verified) {
                throw ValidationException::withMessages([
                    'line_id_token' => ['LINEの認証検証に失敗しました。LINEアプリから再度お試しください。'],
                ]);
            }

            $validated['line_user_id'] = $verified['line_user_id'];
            $validated['line_display_name'] = $verified['line_display_name'] ?? $validated['line_display_name'] ?? null;
            $validated['line_picture_url'] = $verified['line_picture_url'] ?? $validated['line_picture_url'] ?? null;
        } else {
            $validated['line_user_id'] = null;
        }

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
