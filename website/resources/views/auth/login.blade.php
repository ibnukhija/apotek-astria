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
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        /* Menyembunyikan ikon mata bawaan dari browser (Microsoft Edge / IE) */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
    </style>
</head>

<body class="bg-off-white text-dark-slate font-sans m-0">
    <div class="h-screen flex bg-off-white">
        <!-- Panel Kiri -->
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

                    <!-- Input Password dengan Fitur Toggle Mata -->
                    <div class="mb-5">
                        <label class="block mb-2 text-sm font-medium">Password</label>
                        <div class="relative">
                            <input type="password" id="passwordInput" name="password" required placeholder="Masukkan password"
                                class="w-full p-3 pr-12 border border-border-color rounded-lg text-sm outline-none focus:border-soft-teal">

                            <!-- Tombol Ikon Mata -->
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-text hover:text-soft-teal transition">

                                <!-- Ikon Mata Terbuka (Default) -->
                                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>

                                <!-- Ikon Mata Tertutup (Hidden by default) -->
                                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full p-3.5 bg-soft-teal text-white border-none rounded-lg font-bold text-base hover:opacity-90 shadow-sm transition">
                        Login Sistem
                    </button>
                </form>

                <div class="mt-6 text-xs text-gray-text flex items-center gap-2 bg-off-white p-3 rounded-md border border-border-color">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Akses eksklusif hanya untuk karyawan terdaftar.
                </div>
            </div>
        </div>
    </div>

    <!-- Script Toggle Password -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("passwordInput");
            const eyeOpen = document.getElementById("eyeOpen");
            const eyeClosed = document.getElementById("eyeClosed");

            // Jika tipe saat ini password, ubah ke text (terlihat)
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeOpen.classList.add("hidden");
                eyeClosed.classList.remove("hidden");
            }
            // Jika tipe saat ini text, ubah kembali ke password (tersembunyi)
            else {
                passwordInput.type = "password";
                eyeOpen.classList.remove("hidden");
                eyeClosed.classList.add("hidden");
            }
        }
    </script>
</body>

</html>