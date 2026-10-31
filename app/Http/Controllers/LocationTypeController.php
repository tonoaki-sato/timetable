<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationTypeRequest;
use App\Http\Requests\UpdateLocationTypeRequest;
use App\Models\LocationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationTypeController extends Controller
{
    /**
     * タイプマスタ一覧表示
     */
    public function index(Request $request): View|JsonResponse
    {
        $types = LocationType::withCount('locations')->ordered()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'types' => $types,
            ]);
        }

        return view('locations.types.index', compact('types'));
    }

    /**
     * タイプ新規登録
     */
    public function store(StoreLocationTypeRequest $request): RedirectResponse|JsonResponse
    {
        $type = LocationType::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点タイプ「{$type->label}」を追加しました。",
                'type' => $type,
            ]);
        }

        return redirect()->route('location-types.index')
            ->with('status', "拠点タイプ「{$type->label}」を追加しました。");
    }

    /**
     * タイプ更新
     */
    public function update(UpdateLocationTypeRequest $request, LocationType $locationType): RedirectResponse|JsonResponse
    {
        $locationType->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点タイプ「{$locationType->label}」を更新しました。",
                'type' => $locationType,
            ]);
        }

        return redirect()->route('location-types.index')
            ->with('status', "拠点タイプ「{$locationType->label}」を更新しました。");
    }

    /**
     * タイプ削除
     */
    public function destroy(Request $request, LocationType $locationType): RedirectResponse|JsonResponse
    {
        if ($locationType->locations()->exists()) {
            $msg = "拠点タイプ「{$locationType->label}」は既に拠点が紐付いているため削除できません。";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->route('location-types.index')->withErrors(['error' => $msg]);
        }

        $label = $locationType->label;
        $locationType->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点タイプ「{$label}」を削除しました。",
            ]);
        }

        return redirect()->route('location-types.index')
            ->with('status', "拠点タイプ「{$label}」を削除しました。");
    }
}
