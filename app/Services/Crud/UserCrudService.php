<?php

namespace App\Services\Crud;

use App\Models\User;
use Exception;

class UserCrudService
{
    public function listUsers(array $filters = [], int $perPage = 15)
    {
        $query = User::withCount(['bookmarks', 'comments', 'readingHistories']);

        if (!empty($filters['q'])) {
            $keyword = trim($filters['q']);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }

    public function toggleRole(User $user, int $currentAuthId): string
    {
        if ($user->id === $currentAuthId) {
            throw new Exception('Bạn không thể tự thay đổi quyền của chính mình!');
        }

        $user->role = ($user->role === 'admin') ? 'user' : 'admin';
        $user->save();

        return $user->role;
    }

    public function deleteUser(User $user, int $currentAuthId): bool
    {
        if ($user->id === $currentAuthId) {
            throw new Exception('Bạn không thể tự xóa tài khoản của chính mình!');
        }

        return $user->delete();
    }
}
