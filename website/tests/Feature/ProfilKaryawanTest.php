<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilKaryawanTest extends TestCase
{
    use RefreshDatabase;

    public function test_karyawan_dapat_melihat_dan_memperbarui_profil_sendiri(): void
    {
        $karyawan = User::create([
            'nama'     => 'Rina Kasir',
            'username' => 'rina_kasir',
            'password' => Hash::make('rahasia123'),
            'role'     => 'karyawan',
            'status'   => 'aktif',
        ]);

        $this->actingAs($karyawan)
            ->get('/profil')
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee('Rina Kasir')
            ->assertSee('rina_kasir');

        $this->actingAs($karyawan)
            ->put('/profil', [
                'nama'                  => 'Rina Wijaya, S.Farm',
                'username'              => 'rina_wijaya',
                'password'              => 'sandibaru123',
                'password_confirmation' => 'sandibaru123',
            ])
            ->assertRedirect('/profil');

        $karyawan->refresh();
        $this->assertSame('Rina Wijaya, S.Farm', $karyawan->nama);
        $this->assertSame('rina_wijaya', $karyawan->username);
        $this->assertTrue(Hash::check('sandibaru123', $karyawan->password));
    }

    public function test_halaman_profil_web_menolak_guest_dan_owner(): void
    {
        $this->get('/profil')->assertRedirect('/');

        $owner = User::create([
            'nama'     => 'Owner Apotek',
            'username' => 'owner_profil',
            'password' => Hash::make('rahasia123'),
            'role'     => 'owner',
            'status'   => 'aktif',
        ]);

        $this->actingAs($owner)
            ->get('/profil')
            ->assertRedirect('/');
    }
}
