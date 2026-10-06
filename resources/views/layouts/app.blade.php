<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Web Karyawan - Apotek Astria 2 Farma')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'soft-teal': '#2A9D8F', 'off-white': '#F8F9FA', 'pure-white': '#FFFFFF',
                        'dark-slate': '#2B2D42', 'muted-coral': '#E76F51', 'border-color': '#E2E8F0', 'gray-text': '#64748B',
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-off-white text-dark-slate font-sans m-0 overflow-hidden">
    <div class="flex h-screen">
        
        <!-- Memanggil Komponen Sidebar -->
        @include('layouts.sidebar')
        
        <!-- Area Konten Utama yang akan berubah-ubah -->
        <div class="flex-1 p-6 flex flex-col h-screen overflow-hidden">
            @yield('content')
        </div>

    </div>
</body>
</html>