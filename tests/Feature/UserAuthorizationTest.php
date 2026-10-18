<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_is_authorized_for_user_list(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $controller = app(UserController::class);
        $view = $controller->index(new Request);

        $this->assertSame('users.index', $view->name());
    }

    public function test_inactive_user_is_unauthorized_for_user_list(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => false,
        ]);

        $this->actingAs($user);

        $this->expectException(AuthorizationException::class);

        $controller = app(UserController::class);
        $controller->index(new Request);
    }

    public function test_admin_is_authorized_to_create_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $controller = app(UserController::class);
        $view = $controller->create();

        $this->assertSame('users.create', $view->name());
    }

    public function test_operator_is_unauthorized_to_create_user(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->actingAs($operator);

        $this->expectException(AuthorizationException::class);

        $controller = app(UserController::class);
        $controller->create();
    }
}
