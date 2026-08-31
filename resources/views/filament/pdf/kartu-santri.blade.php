{{-- Kartu santri CR80 (85,6 × 54 mm). Halaman PDF-nya SEUKURAN kartu, jadi satuan
     di sini menutup seluruh halaman — jangan tambahkan margin @page, tepi kartu
     adalah tepi kertas.

     ⚠️ Seluruh tata letak memakai <table>, termasuk kerangka luarnya, dan itu bukan
     gaya lama. DomPDF tidak mendukung flex/grid, dan `position: absolute; bottom: 0`
     untuk menempelkan kaki kartu MELUAP keluar halaman tanpa error apa pun — versi
     pertama menghasilkan dua halaman untuk satu kartu. Tabel setinggi tetap dengan
     baris atas & bawah `height: 1%` adalah satu-satunya cara yang benar-benar
     menahan kaki di dasar kotak di renderer ini. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 6.5px;
            color: #1a1a1a;
        }

        /* ⚠️ 46mm + margin 2×2.5mm = 51mm, sengaja di bawah tinggi halaman 54 mm.
           Kotak setinggi persis 54 mm di halaman 54 mm meluap ke halaman kedua di
           DomPDF (terukur: 48.8mm + margin 2.6mm menghasilkan dua halaman untuk
           satu kartu). Jangan "dirapikan" jadi angka yang pas.

           `height` di sini MINIMUM, bukan maksimum: isi yang lebih tinggi tetap
           memuaikan tabelnya. Isi terpanjang yang mungkin — semua kolom terisi dan
           semuanya panjang — terukur 46 mm (kop 12 + badan 26 + kaki 8), jadi
           cadangan sebenarnya adalah 3 mm sebelum kartu meluap ke halaman kedua.
           Menaikkan tinggi elemen mana pun di bawah ini memakannya; yang menjaga
           batas itu KartuSantriTest::test_kartu_terpanjang_pun_tetap_muat_satu_halaman. */
        table.kartu {
            width: 79.6mm;
            height: 46mm;
            margin: 2.5mm 3mm;
            border-collapse: collapse;
        }
        /* Kartu berikutnya selalu mulai di halaman baru. Tanpa ini urutannya masih
           benar (tiap kartu kebetulan setinggi satu halaman), tapi satu kartu yang
           isinya sedikit lebih pendek akan menarik kartu sesudahnya naik. */
        table.kartu + table.kartu { page-break-before: always; }

        td.baris-kop { height: 1%; vertical-align: top; }
        td.baris-badan { vertical-align: top; padding-top: 1.5mm; }
        td.baris-kaki { height: 1%; vertical-align: bottom; }

        .kop { width: 100%; border-bottom: 0.4mm solid #166534; padding-bottom: 0.8mm; }
        .kop td { vertical-align: middle; }
        .kop .logo-sel { width: 8mm; }
        .kop .logo { height: 7mm; }
        .kop .nama-pesantren { font-size: 8.5px; font-weight: bold; color: #166534; line-height: 1.1; }
        .kop .alamat-pesantren { font-size: 5.5px; color: #6b7280; line-height: 1.1; }

        .badan { width: 100%; }
        .badan td { vertical-align: top; }

        .foto-sel { width: 20mm; }
        .foto {
            width: 19mm;
            height: 21mm;
            border: 0.3mm solid #d1d5db;
        }
        /* Santri tanpa foto tetap dapat bingkai berukuran sama, supaya kolom
           identitas di sebelahnya tidak bergeser antar kartu dalam satu kelas.
           ⚠️ Dibuat tabel, bukan div ber-padding: DomPDF MENGABAIKAN `box-sizing`,
           jadi `height: 21mm; padding-top: 10mm` menghasilkan kotak 31 mm — dan
           tambahan itulah yang dulu mendorong kartu ke halaman kedua. */
        table.foto-kosong {
            width: 19mm;
            height: 21mm;
            border: 0.3mm dashed #d1d5db;
            background: #f9fafb;
            border-collapse: collapse;
        }
        table.foto-kosong td {
            text-align: center;
            vertical-align: middle;
            font-size: 5.5px;
            color: #9ca3af;
        }

        .nama-santri { font-size: 8.5px; font-weight: bold; line-height: 1.05; }
        .nis {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 6.5px;
            color: #166534;
            letter-spacing: 0.3px;
            padding-bottom: 0.4mm;
        }

        table.identitas { width: 100%; border-collapse: collapse; }
        table.identitas td { padding: 0; font-size: 6px; line-height: 1.05; }
        table.identitas td.label { width: 13mm; color: #6b7280; }
        table.identitas td.pemisah { width: 1.5mm; color: #6b7280; }

        /* QR-nya sama dengan kartu presensi (payload WSP1.{kode}), jadi kartu ini
           bisa dipindai di pintu masuk tanpa membawa kartu kedua. */
        .qr-sel { width: 15mm; text-align: center; }
        .qr-sel .qr { width: 14mm; height: 14mm; }
        .qr-sel .kode {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 4.5px;
            letter-spacing: 0.2px;
            color: #374151;
            line-height: 1.1;
        }
        .qr-sel .kode-kosong { font-size: 4.5px; color: #9ca3af; line-height: 1.1; }

        .kaki { width: 100%; border-top: 0.2mm solid #e5e7eb; padding-top: 0.8mm; }
        .kaki td { vertical-align: bottom; font-size: 6px; line-height: 1.15; }
        .kaki .berlaku { color: #6b7280; }
        .kaki .berlaku strong { color: #1a1a1a; }
        .kaki .kepala { text-align: right; }
        .kaki .kepala .jabatan { color: #6b7280; font-size: 5.5px; }
        .kaki .kepala .nama { font-weight: bold; }

        .kosong { padding: 6mm 4mm; font-size: 8px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
@if($kartu->isEmpty())
    <div class="kosong">Belum ada santri aktif di kelas ini.</div>
@else
    @foreach($kartu as $k)
        <table class="kartu">
            <tr>
                <td class="baris-kop">
                    <table class="kop">
                        <tr>
                            @if($logoPesantren)
                                <td class="logo-sel"><img src="{{ $logoPesantren }}" class="logo" alt=""></td>
                            @endif
                            <td>
                                <div class="nama-pesantren">{{ $namaPesantren ?? 'Pesantren' }}</div>
                                @if($alamatPesantren)
                                    <div class="alamat-pesantren">{{ $alamatPesantren }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td class="baris-badan">
                    <table class="badan">
                        <tr>
                            <td class="foto-sel">
                                @if($k->foto)
                                    <img src="{{ $k->foto }}" class="foto" alt="">
                                @else
                                    <table class="foto-kosong"><tr><td>Tanpa Foto</td></tr></table>
                                @endif
                            </td>
                            <td>
                                <div class="nama-santri">{{ $k->nama }}</div>
                                <div class="nis">NIS {{ $k->nis ?? '—' }}</div>

                                <table class="identitas">
                                    <tr>
                                        <td class="label">Kelas</td>
                                        <td class="pemisah">:</td>
                                        <td>{{ $k->kelas ?? '—' }}</td>
                                    </tr>
                                    @if($k->kamar)
                                        <tr>
                                            <td class="label">Kamar</td>
                                            <td class="pemisah">:</td>
                                            <td>{{ $k->kamar }}</td>
                                        </tr>
                                    @endif
                                    @if($k->jenis_kelamin)
                                        <tr>
                                            <td class="label">Jenis Kelamin</td>
                                            <td class="pemisah">:</td>
                                            <td>{{ $k->jenis_kelamin }}</td>
                                        </tr>
                                    @endif
                                    @if($k->tanggal_lahir)
                                        <tr>
                                            <td class="label">Tanggal Lahir</td>
                                            <td class="pemisah">:</td>
                                            <td>{{ $k->tanggal_lahir }}</td>
                                        </tr>
                                    @endif
                                    @if($k->alamat)
                                        <tr>
                                            <td class="label">Alamat</td>
                                            <td class="pemisah">:</td>
                                            <td>{{ $k->alamat }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </td>

                            {{-- QR di kolom ketiga, bukan di kaki kartu: kaki sudah
                                 dipakai masa berlaku dan nama kepala, dan menumpuknya
                                 di sana memaksa QR mengecil di bawah ukuran yang masih
                                 terbaca pemindai murah. --}}
                            <td class="qr-sel">
                                @if($k->qr)
                                    <img src="{{ $k->qr }}" class="qr" alt="QR presensi">
                                    <div class="kode">{{ $k->kode }}</div>
                                @else
                                    <div class="kode-kosong">Kode kartu<br>belum ada</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td class="baris-kaki">
                    <table class="kaki">
                        <tr>
                            <td class="berlaku">Berlaku sampai<br><strong>{{ $masaBerlaku }}</strong></td>
                            {{-- Blok kepala disembunyikan bila namanya belum diisi di
                                 Pengaturan Pesantren: label "Kepala Pesantren" yang
                                 menggantung tanpa nama terlihat seperti kartu cacat. --}}
                            @if($kepalaPesantren)
                                <td class="kepala">
                                    <div class="jabatan">Kepala Pesantren</div>
                                    <div class="nama">{{ $kepalaPesantren }}</div>
                                </td>
                            @endif
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endforeach
@endif
</body>
</html>
