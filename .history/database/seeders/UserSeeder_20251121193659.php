<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => Hash::make('123456'), // password
        ]);
    }
        UserData::create([
            'userName'         => 'Test User',
            'userEmail'        => 'test@example.com',
            'userPassword'     => Hash::make('123456'),   // password
            'profilePic'       => null,
            'lastModification' => now(),
        ]);
    }
}
