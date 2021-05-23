<?php

namespace LnBlog\Storage;

use User;

class UserRepository
{
    public function exists(string $username) {
        $user = User::get($username);
        return $user->exists();
    }

    public function createUser(User $user) {
        $user->save();
    }

    public function saveUser(User $user) {
        $user->save();
    }
}
