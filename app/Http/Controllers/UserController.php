<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * ユーザー一覧・検索画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->paginateFiltered(
            $request->input('search'),
            $request->input('role'),
            $request->filled('status') ? $request->boolean('status') : null,
            15
        );

        return view('users.index', compact('users'));
    }

    /**
     * ユーザー新規登録画面の表示（admin専用）
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    /**
     * ユーザー新規登録処理（admin専用）
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $this->userService->createUser(
            $request->validated(),
            $request->boolean('is_active', true)
        );

        return redirect()->route('users.index')->with('success', 'ユーザーを新規登録しました。');
    }

    /**
     * ユーザー詳細画面の表示（admin, operator, または本人）
     */
    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('users.show', compact('user'));
    }

    /**
     * ユーザー編集画面の表示（admin または本人）
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    /**
     * ユーザー情報更新処理（admin または本人）
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->userService->updateUser(
            $user,
            $request->user(),
            $request->validated(),
            $request->filled('password'),
            $request->boolean('is_active')
        );

        return redirect()->route('users.show', $user)->with('success', 'ユーザー情報を更新しました。');
    }

    /**
     * ユーザー削除処理（admin専用）
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $userName = $user->name;
        $this->userService->deleteUser($user);

        return redirect()->route('users.index')->with('success', "ユーザー「{$userName}」を削除しました。");
    }
}
