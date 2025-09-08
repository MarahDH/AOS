<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserRepository
{
    public function getAll()
    {
        return User::with('role')->whereNull('deleted_at')->get();
    }

    public function find($id)
    {
        return User::whereNull('deleted_at')->findOrFail($id);
    }

    public function create(array $data)
    {
        $data['password'] = bcrypt($data['password']);
        return User::create($data);
    }

    public function update(int $id, array $data)
    {
        $user = $this->find($id);
        $user->update($data);
        return $user;
    }

    public function delete(int $id)
    {
        $user = $this->find($id);
        
        // Check if user has offers before deletion
        if ($user->offers()->count() > 0) {
            // Log the deletion for audit purposes
            Log::info("Deleting user {$user->name} (ID: {$id}) with {$user->offers()->count()} offers");
        }
        
        // This will now cascade delete all offers created by this user
        return $user->forceDelete();
    }

    public function changePassword(int $userId, array $data)
    {
        $user = User::findOrFail($userId);
        $user->password = bcrypt($data['newPassword']);
        $user->save();

        return $user;
    }
}
