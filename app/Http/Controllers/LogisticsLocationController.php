<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLogisticsLocationRequest;
use App\Http\Requests\UpdateLogisticsLocationRequest;
use App\Models\LogisticsLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogisticsLocationController extends Controller
{
    /**
     * 拠点・貸切期間一覧の表示
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', LogisticsLocation::class);

        $locations = LogisticsLocation::ordered()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'locations' => $locations,
            ]);
        }

        return view('equipments.locations_index', compact('locations'));
    }

    /**
     * 拠点の新規登録
     */
    public function store(StoreLogisticsLocationRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', LogisticsLocation::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        $location = LogisticsLocation::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$location->name}」を登録しました。",
                'location' => $location,
            ]);
        }

        return redirect()->route('equipments.index', ['tab' => 'locations'])
            ->with('status', "拠点「{$location->name}」を登録しました。");
    }

    /**
     * 拠点の更新
     */
    public function update(UpdateLogisticsLocationRequest $request, LogisticsLocation $location): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $location);

        $location->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$location->name}」を更新しました。",
                'location' => $location,
            ]);
        }

        return redirect()->route('equipments.index', ['tab' => 'locations'])
            ->with('status', "拠点「{$location->name}」を更新しました。");
    }

    /**
     * 拠点の削除
     */
    public function destroy(Request $request, LogisticsLocation $location): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $location);

        $name = $location->name;
        $location->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('equipments.index', ['tab' => 'locations'])
            ->with('status', "拠点「{$name}」を削除しました。");
    }
}
