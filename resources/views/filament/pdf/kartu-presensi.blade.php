<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 14px; margin: 0 0 2mm; }
        .sub { font-size: 10px; color: #666; margin: 0 0 6mm; }
        table.grid { width: 100%; border-collapse: separate; border-spacing: 4mm; }
        td.kartu {
            width: 50%; border: 1px solid #999; border-radius: 3mm;
            padding: 4mm; vertical-align: top; text-align: center;
        }
        .nama { font-weight: bold; font-size: 11px; margin-bottom: 1mm; }
        .meta { font-size: 9px; color: #555; margin-bottom: 2mm; }
        .qr img { width: 28mm; height: 28mm; }
        .kode { font-family: DejaVu Sans Mono, monospace; font-size: 11px; letter-spacing: 1px; margin-top: 2mm; }
        .catatan { font-size: 8px; color: #888; margin-top: 1mm; }
    </style>
</head>
<body>
    <h1>Kartu Presensi — {{ $judul }}</h1>
    <p class="sub">Dipindai petugas saat masuk. Simpan baik-baik; laporkan ke pesantren bila hilang.</p>

    @if($kartu->isEmpty())
        <p>Belum ada santri aktif di kelas ini.</p>
    @else
        <table class="grid">
            @foreach($kartu->chunk(2) as $baris)
                <tr>
                    @foreach($baris as $k)
                        <td class="kartu">
                            <div class="nama">{{ $k->nama }}</div>
                            <div class="meta">NIS {{ $k->nis }} · {{ $k->kelas ?? '—' }}</div>
                            @if($k->qr)
                                <div class="qr"><img src="{{ $k->qr }}" alt="QR"></div>
                                <div class="kode">{{ $k->kode }}</div>
                                {{-- Kode dicetak sebagai teks juga: kalau QR lecek atau
                                     kamera gagal, petugas masih bisa mengetiknya. --}}
                                <div class="catatan">Ketik kode ini bila QR tidak terbaca</div>
                            @else
                                <div class="catatan">Kode belum tersedia</div>
                            @endif
                        </td>
                    @endforeach
                    @if($baris->count() === 1)<td></td>@endif
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
