<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Crud\UserCrudService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserCrudService $userCrudService;

    public function __construct(UserCrudService $userCrudService)
    {
        $this->userCrudService = $userCrudService;
    }

    public function index(Request $request)
    {
        $users = $this->userCrudService->listUsers($request->all(), 15);
        return view('admin.users.index', compact('users'));
    }

    public function toggleRole(User $user)
    {
        try {
            $newRole = $this->userCrudService->toggleRole($user, auth()->id());
            return back()->with('success', "Đã chuyển quyền tài khoản '{$user->name}' thành {$newRole}!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        try {
            $name = $user->name;
            $this->userCrudService->deleteUser($user, auth()->id());
            return back()->with('success', "Đã xóa tài khoản '{$name}'!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
