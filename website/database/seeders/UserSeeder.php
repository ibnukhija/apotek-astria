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
        User::firstOrCreate(
            ['username' => 'kasir1'],
            [
                'nama'     => 'Karyawan1',
                'password' => Hash::make('kasir123'), // Password wajib di-hash
                'role'     => 'karyawan',
                'status'   => 'aktif',
            ]
        );

        // Akun Owner
        User::firstOrCreate(
            ['username' => 'owner'],
            [
                'nama'     => 'Owner Apotek',
                'password' => Hash::make('owner123'),
                'role'     => 'owner',
                'status'   => 'aktif',
            ]
        );
    }
}