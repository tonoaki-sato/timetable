<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __construct(
        protected GlobalSearchService $globalSearchService
    ) {}

    /**
     * システム横断クイック検索API
     */
    public function __invoke(GlobalSearchRequest $request): JsonResponse
    {
        $query = (string) ($request->validated('q') ?? '');

        return response()->json(
            $this->globalSearchService->search($query, $request->user())
        );
    }
}
