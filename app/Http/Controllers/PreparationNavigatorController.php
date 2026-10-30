<?php

namespace App\Http\Controllers;

use App\Services\PreparationNavigatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreparationNavigatorController extends Controller
{
    public function __construct(
        protected PreparationNavigatorService $navigatorService
    ) {}

    /**
     * まつり運行準備ナビゲーター画面の表示
     */
    public function index(Request $request): View
    {
        $viewData = $this->navigatorService->getNavigatorViewData();

        return view('preparation_navigator.index', $viewData);
    }

    /**
     * 推奨ステップを直接実行（リダイレクト）
     */
    public function run(Request $request): RedirectResponse
    {
        $timetableId = $request->query('timetable_id') ? (int) $request->query('timetable_id') : null;
        $targetUrl = $this->navigatorService->resolveRunUrl($timetableId);

        return redirect($targetUrl);
    }
}
