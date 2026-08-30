<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Akun Dashboard — Anita MUA</title>
</head>
<body style="margin:0;padding:0;background:#faf6f2;font-family:'Segoe UI',Arial,sans-serif;">
    <div style="max-width:520px;margin:24px auto;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.06);">
        <div style="background:linear-gradient(135deg,#d4739a,#b85c85);padding:24px 28px;color:#ffffff;">
            <h1 style="margin:0;font-size:20px;font-family:Georgia,'Times New Roman',serif;">ANITA MUA</h1>
            <p style="margin:4px 0 0;font-size:13px;opacity:.9;">Akun Dashboard Anda Telah Aktif</p>
        </div>
        <div style="padding:28px;">
            <p style="margin:0 0 16px;font-size:14px;color:#2d2521;line-height:1.6;">
                Halo <strong>{{ $user->name }}</strong>,
            </p>
            <p style="margin:0 0 16px;font-size:14px;color:#2d2521;line-height:1.6;">
                DP1 booking Anda telah diverifikasi. Akun dashboard Anda telah dibuat otomatis
                @if($bookingCode)
                    (kode booking: <strong>{{ $bookingCode }}</strong>)
                @endif
                dan Anda bisa memantau progress acara, pembayaran, dan jadwal.
            </p>

            <div style="background:#fdf2f6;border-radius:10px;padding:16px 20px;margin-bottom:20px;">
                <p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#8a8075;">Informasi Login</p>
                <p style="margin:0 0 8px;font-size:14px;color:#2d2521;"><strong>Email / No. WhatsApp:</strong> {{ $user->email }} / {{ $user->phone }}</p>
                <p style="margin:0;font-size:14px;color:#2d2521;"><strong>Password:</strong> <span style="font-family:monospace;background:#fff;border:1px solid #f0ece8;padding:2px 8px;border-radius:4px;font-weight:700;letter-spacing:1px;">{{ $password }}</span></p>
            </div>

            <p style="margin:0 0 20px;font-size:12px;color:#8a8075;line-height:1.6;">
                Sebaiknya segera ganti password setelah login pertama Anda. Hubungi admin jika ada kendala.
            </p>

            <div style="text-align:center;">
                <a href="{{ url('/login') }}" style="display:inline-block;background:#d4739a;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:12px 32px;border-radius:50px;">
                    Masuk ke Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
