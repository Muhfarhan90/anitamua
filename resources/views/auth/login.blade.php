<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — {{ $settings['company_name'] ?? 'Anita MUA' }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="font-body min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(rgba(26,20,18,.85), rgba(26,20,18,.92)), url('https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920') center/cover;">
    <div class="w-full max-w-[420px]">
        <div class="bg-white rounded-2xl shadow-2xl p-6 md:p-8">
            <div class="text-center mb-6">
                <h3 class="font-display text-2xl font-bold text-gray-900 mb-1">{{ $settings['company_name'] ?? 'Anita MUA' }}</h3>
                <p class="text-gray-500 text-sm">Masuk ke dashboard</p>
            </div>

            @if($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 mb-4 space-y-0.5">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Email / No. WhatsApp</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-sm text-brand pointer-events-none"></i>
                        <input type="text" name="login" value="{{ old('login') }}" required autofocus
                               placeholder="email@anda.com atau 08xxxxxxxxxx"
                               class="w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 focus:border-transparent transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-sm text-brand pointer-events-none"></i>
                        <input type="password" name="password" required
                               class="w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 focus:border-transparent transition-all">
                    </div>
                </div>
                <button type="submit"
                        class="w-full py-2.5 rounded-lg bg-brand text-white font-semibold border-2 border-brand-dark hover:bg-brand-dark transition-all">
                    Masuk
                </button>
                <a href="{{ route('booking.create') }}"
                   class="w-full py-2.5 rounded-lg inline-flex items-center justify-center gap-2 text-brand font-semibold border-2 border-brand hover:bg-brand hover:text-white transition-all no-underline">
                    <i class="fas fa-calendar-check text-sm"></i> Booking Sekarang
                </a>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('home') }}" class="text-gray-400 text-sm no-underline hover:text-brand inline-flex items-center gap-1.5"><i class="fas fa-arrow-left text-xs"></i> Kembali ke website</a>
            </div>
        </div>
    </div>
</body>
</html>
