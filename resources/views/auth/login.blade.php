<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Karyawan - Apotek Astria 2 Farma</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'soft-teal': '#2A9D8F',
                        'off-white': '#F8F9FA',
                        'pure-white': '#FFFFFF',
                        'dark-slate': '#2B2D42',
                        'muted-coral': '#E76F51',
                        'border-color': '#E2E8F0',
                        'gray-text': '#64748B',
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-off-white text-dark-slate font-sans m-0">
    <div class="h-screen flex bg-off-white">
        <!-- Panel Kiri (Tanpa Gambar, Diganti Latar Solid Soft Teal) -->
        <div class="w-2/5 bg-soft-teal flex flex-col items-center justify-center p-10 border-r border-soft-teal text-white">
            <div class="bg-pure-white text-soft-teal p-4 rounded-xl mb-6 text-5xl shadow-sm">
                💊
            </div>
            <h1 class="text-3xl font-bold text-center mb-3">Apotek Astria 2 Farma</h1>
            <p class="text-center opacity-90 text-sm leading-relaxed max-w-xs">
                Kelola transaksi, stok obat, dan data produk dalam satu ruang kerja yang sederhana untuk seluruh tim Astria.
            </p>
        </div>
        
        <!-- Panel Kanan (Form Login) -->
        <div class="w-3/5 flex items-center justify-center">
            <div class="bg-pure-white p-10 rounded-xl w-full max-w-md shadow-sm border border-border-color">
                <h2 class="mt-0 text-2xl font-bold mb-2">Selamat datang</h2>
                <p class="text-gray-text text-sm mb-6">Masuk menggunakan akun karyawan untuk memulai shift Anda.</p>
                
                <!-- Pesan Error Login -->
                @if ($errors->any())
                    <div class="bg-[#FFEDD5] text-muted-coral p-3 rounded-md text-sm mb-5 font-semibold">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ url('/login') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium">Username</label>
                        <input type="text" name="username" required placeholder="Masukkan username" class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                    </div>
                    <div class="mb-5">
                        <label class="block mb-2 text-sm font-medium">Password</label>
                        <input type="password" name="password" required placeholder="Masukkan password" class="w-full p-3 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">
                    </div>
                    
                    <button type="submit" class="w-full p-3.5 bg-soft-teal text-white border-none rounded-lg font-bold text-base hover:opacity-90 transition">Login Sistem</button>
                </form>
                
                <div class="mt-6 text-xs text-gray-text flex items-center gap-2 bg-off-white p-3 rounded-md">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Akses eksklusif hanya untuk karyawan terdaftar.
                </div>
            </div>
        </div>
    </div>
</body>
</html>