<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserData;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        UserData::create([
            'userName'         => 'Test User',
            'userEmail'        => 'test@example.com',
            'userPassword'     => Hash::make('123456'),   // password
            'profilePic'       => null,
            'lastModification' => now(),
        ]);
    }
}
