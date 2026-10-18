<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * ユーザー一覧・検索画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::query();

        // 1. 氏名またはメールアドレスのキーワード検索
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. 権限ロールの絞り込み
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // 3. 有効・停止ステータスの絞り込み
        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        $users = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

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

        User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => $request->validated('role'),
            'is_active' => $request->boolean('is_active', true),
        ]);

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

        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ];

        // パスワード入力時のみ更新
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        // admin のみロールと有効状態の変更を反映
        if ($request->user()->isAdmin()) {
            // 自分自身のアカウント停止・ロール降格を防ぐ
            if ($user->id === $request->user()->id) {
                // 自身のロールは admin のまま維持
                $data['role'] = 'admin';
                $data['is_active'] = true;
            } else {
                $data['role'] = $request->validated('role');
                $data['is_active'] = $request->boolean('is_active');
            }
        }

        $user->update($data);

        return redirect()->route('users.show', $user)->with('success', 'ユーザー情報を更新しました。');
    }

    /**
     * ユーザー削除処理（admin専用）
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $userName = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "ユーザー「{$userName}」を削除しました。");
    }
}
