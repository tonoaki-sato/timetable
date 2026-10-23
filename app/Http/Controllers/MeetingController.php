<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Models\Meeting;
use App\Models\Staff;
use App\Services\MeetingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function __construct(
        protected MeetingService $meetingService
    ) {}

    /**
     * 会議一覧画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Meeting::class);

        $tab = $request->input('tab', 'upcoming') === 'past' ? 'past' : 'upcoming';
        $search = $request->input('search');
        $staffId = $request->filled('staff_id') ? (int) $request->input('staff_id') : null;

        $viewData = $this->meetingService->getIndexViewData($tab, $search, $staffId);

        return view('meetings.index', $viewData);
    }

    /**
     * 会議新規登録画面の表示
     */
    public function create(): View
    {
        $this->authorize('create', Meeting::class);

        return view('meetings.create', [
            'staffs' => Staff::active()->orderBy('kana')->get(),
        ]);
    }

    /**
     * 会議新規登録処理
     */
    public function store(StoreMeetingRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Meeting::class);

        $meeting = $this->meetingService->createMeeting(
            $request->validated(),
            $request->user()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "会議「{$meeting->title}」を登録しました。",
                'meeting' => $meeting->load('host'),
            ], 201);
        }

        return redirect()->route('meetings.show', $meeting)
            ->with('success', "会議「{$meeting->title}」を登録しました。LINE共有テキストを確認できます。");
    }

    /**
     * 会議詳細画面の表示（LINE共有テキストプレビュー）
     */
    public function show(Meeting $meeting): View
    {
        $this->authorize('view', $meeting);

        $meeting->load(['host', 'creator']);

        return view('meetings.show', [
            'meeting' => $meeting,
        ]);
    }

    /**
     * 会議編集画面の表示
     */
    public function edit(Meeting $meeting): View
    {
        $this->authorize('update', $meeting);

        return view('meetings.edit', [
            'meeting' => $meeting,
            'staffs' => Staff::active()->orderBy('kana')->get(),
        ]);
    }

    /**
     * 会議更新処理
     */
    public function update(UpdateMeetingRequest $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $this->meetingService->updateMeeting($meeting, $request->validated());

        return redirect()->route('meetings.show', $meeting)
            ->with('success', "会議「{$meeting->title}」を更新しました。");
    }

    /**
     * 会議削除処理
     */
    public function destroy(Meeting $meeting): RedirectResponse
    {
        $this->authorize('delete', $meeting);

        $title = $meeting->title;
        $tab = $meeting->is_upcoming ? 'upcoming' : 'past';

        $this->meetingService->deleteMeeting($meeting);

        return redirect()->route('meetings.index', ['tab' => $tab])
            ->with('success', "会議「{$title}」を削除しました。");
    }
}
