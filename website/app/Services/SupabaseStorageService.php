<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseStorageService
{
    public function terkonfigurasi(): bool
    {
        return filled(config('services.supabase.url'))
            && filled(config('services.supabase.service_key'))
            && filled(config('services.supabase.bucket'));
    }

    /**
     * Unggah file laporan (PDF/XLSX) ke Supabase Storage bucket yang dikonfigurasi.
     *
     * @return array{
     *     diunggah: bool,
     *     dikonfigurasi: bool,
     *     status: string,
     *     bucket: string,
     *     path: ?string,
     *     url: ?string,
     *     pesan: ?string
     * }
     */
    public function simpanLaporan(string $namaFile, string $konten, string $mimeType): array
    {
        $bucket = (string) config('services.supabase.bucket', 'laporan-penjualan');

        if (! $this->terkonfigurasi()) {
            return [
                'diunggah'      => false,
                'dikonfigurasi' => false,
                'status'        => 'Supabase Storage belum dikonfigurasi di .env',
                'bucket'        => $bucket,
                'path'          => null,
                'url'           => null,
                'pesan'         => 'Isi SUPABASE_URL dan SUPABASE_SERVICE_KEY di file .env server.',
            ];
        }

        $url = $this->normalisasiBaseUrl((string) config('services.supabase.url'));
        $key = (string) config('services.supabase.service_key');
        $isPublic = (bool) config('services.supabase.public', true);
        $path = 'laporan/' . Carbon::now()->format('Y/m') . '/' . ltrim($namaFile, '/');
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

        try {
            $res = Http::timeout(15)
                ->withHeaders($this->buatHeaders($key, ['x-upsert' => 'true']))
                ->withBody($konten, $mimeType)
                ->post("{$url}/storage/v1/object/{$bucket}/{$encodedPath}");

            if (! $res->successful()) {
                $err = $res->json('message') ?? $res->json('error') ?? ('HTTP ' . $res->status());
                Log::warning('Upload laporan ke Supabase Storage gagal', [
                    'status' => $res->status(),
                    'body'   => $res->body(),
                ]);

                return [
                    'diunggah'      => false,
                    'dikonfigurasi' => true,
                    'status'        => "Gagal upload ke Supabase ({$err})",
                    'bucket'        => $bucket,
                    'path'          => $path,
                    'url'           => null,
                    'pesan'         => (string) $err,
                ];
            }

            $fileUrl = $this->buatUrlAkses($url, $key, $bucket, $encodedPath, $isPublic);

            return [
                'diunggah'      => true,
                'dikonfigurasi' => true,
                'status'        => 'Tersimpan di Supabase Storage',
                'bucket'        => $bucket,
                'path'          => $path,
                'url'           => $fileUrl,
                'pesan'         => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Koneksi ke Supabase Storage gagal: ' . $e->getMessage());

            return [
                'diunggah'      => false,
                'dikonfigurasi' => true,
                'status'        => 'Gagal terhubung ke Supabase Storage',
                'bucket'        => $bucket,
                'path'          => $path,
                'url'           => null,
                'pesan'         => $e->getMessage(),
            ];
        }
    }

    private function buatUrlAkses(string $url, string $key, string $bucket, string $encodedPath, bool $isPublic): ?string
    {
        if ($isPublic) {
            return "{$url}/storage/v1/object/public/{$bucket}/{$encodedPath}";
        }

        try {
            $signRes = Http::timeout(10)
                ->withHeaders($this->buatHeaders($key))
                ->post("{$url}/storage/v1/object/sign/{$bucket}/{$encodedPath}", [
                    'expiresIn' => 604800, // 7 hari
                ]);

            if (! $signRes->successful()) {
                return null;
            }

            $signed = (string) ($signRes->json('signedURL') ?? '');
            if ($signed === '') {
                return null;
            }

            if (str_starts_with($signed, 'http')) {
                return $signed;
            }

            return $url . (str_starts_with($signed, '/storage/v1') ? '' : '/storage/v1') . $signed;
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat signed URL Supabase: ' . $e->getMessage());
            return null;
        }
    }

    private function normalisasiBaseUrl(string $url): string
    {
        $bersih = rtrim(trim($url), '/');
        return preg_replace('#/(rest|storage)/v1$#i', '', $bersih) ?? $bersih;
    }

    private function buatHeaders(string $key, array $extra = []): array
    {
        $headers = array_merge(['apikey' => $key], $extra);

        // Key baru Supabase (sb_secret_...) bukan JWT, sehingga hanya dikirim di header apikey.
        if (! str_starts_with($key, 'sb_')) {
            $headers['Authorization'] = "Bearer {$key}";
        }

        return $headers;
    }
}
