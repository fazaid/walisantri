<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: auto; padding: 24px;">
    <h2 style="color: #0f766e;">Pengingat Perpanjangan Langganan</h2>

    <p>Yth. Admin <strong>{{ $pesantren->nama_pesantren }}</strong>,</p>

    <p>
        Langganan Anda di <strong>Walisantri.com</strong> akan berakhir dalam
        <strong>{{ $daysLeft }} hari</strong>
        ({{ $pesantren->expired_at->format('d F Y') }}).
    </p>

    <p>
        Segera perbarui langganan agar data santri dan akses portal wali tidak terganggu.
    </p>

    <p style="margin-top: 24px;">
        <a href="{{ url('/admin/billing-page') }}"
           style="background:#0f766e;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:bold;">
            Perpanjang Sekarang
        </a>
    </p>

    <hr style="margin-top:32px;border:none;border-top:1px solid #e5e7eb;">
    <p style="font-size:12px;color:#9ca3af;">
        Walisantri.com · Platform Digitalisasi Pesantren
    </p>
</body>
</html>
