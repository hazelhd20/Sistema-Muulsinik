<?php

namespace App\Repositories;

use App\DTOs\UserDTO;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    /**
     * @param UserDTO $dto
     * @return User
     */
    public function save(UserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->id) {
                $user = User::findOrFail($dto->id);
                $updateData = [
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'role_id' => $dto->role_id,
                    'active' => $dto->active,
                ];

                if (!empty($dto->password)) {
                    $updateData['password'] = Hash::make($dto->password);
                }

                $user->update($updateData);
            } else {
                $user = User::create([
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'role_id' => $dto->role_id,
                    'active' => $dto->active,
                    'password' => Hash::make($dto->password),
                ]);
            }

            return $user;
        });
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            User::findOrFail($id)->delete();
        });
    }

    /**
     * Delete multiple users by ID, ensuring observers run to invalidate caches
     *
     * @param array $ids
     * @return void
     */
    public function bulkDelete(array $ids): void
    {
        if (!empty($ids)) {
            DB::transaction(function () use ($ids) {
                User::whereIn('id', $ids)->get()->each->delete();
            });
        }
    }

    /**
     * Toggle the active status of a user
     * 
     * @param int $id
     * @return User
     */
    public function toggleActive(int $id): User
    {
        return DB::transaction(function () use ($id) {
            $user = User::findOrFail($id);
            $user->update(['active' => !$user->active]);
            return $user;
        });
    }
}
