<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun Karyawan/Kasir
        User::create([
            'nama' => 'Karyawan1',
            'username' => 'kasir1',
            'password' => Hash::make('kasir123'), // Password wajib di-hash
            'role' => 'karyawan'
        ]);

        // Akun Owner
        User::create([
            'nama' => 'Owner Apotek',
            'username' => 'owner',
            'password' => Hash::make('owner123'),
            'role' => 'owner'
        ]);
    }
}