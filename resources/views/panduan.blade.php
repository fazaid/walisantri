<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Panduan penggunaan Walisantri untuk Admin Pesantren dan Ustadz — cara pakai tiap menu, langkah demi langkah.">
    <meta name="robots" content="noindex">
    <title>Panduan Penggunaan — Walisantri</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <style>
      :root{
        --bg:#F1F0E8;
        --bg-elevated:#FBFAF6;
        --bg-sidebar:#EAE8DD;
        --ink:#1C2521;
        --ink-soft:#4B564F;
        --ink-faint:#7C8579;
        --border:#DAD6C8;
        --border-strong:#C7C2AF;
        --accent:#1F5C52;
        --accent-ink:#123B34;
        --accent-soft:#DCE8E3;
        --gold:#A97A2E;
        --gold-soft:#F1E6CE;
        --ustadz:#4C6B85;
        --ustadz-soft:#DDE6EC;
        --danger:#A8432F;
        --danger-soft:#F3E0DA;
        --shadow: 0 1px 2px rgba(28,37,33,0.06), 0 8px 24px rgba(28,37,33,0.05);
        --font-display: "Iowan Old Style","Palatino Linotype",Palatino,"Book Antiqua",Georgia,"Noto Serif",serif;
        --font-body: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
        --font-mono: ui-monospace,"SF Mono","Cascadia Code","Roboto Mono","Liberation Mono",monospace;
        color-scheme: light dark;
      }
      @media (prefers-color-scheme: dark){
        :root{
          --bg:#101613;
          --bg-elevated:#172420;
          --bg-sidebar:#141E1A;
          --ink:#ECE8DC;
          --ink-soft:#AEB6AC;
          --ink-faint:#79847C;
          --border:#2B3733;
          --border-strong:#3A4841;
          --accent:#5FB9A6;
          --accent-ink:#BFE9DE;
          --accent-soft:#1E3733;
          --gold:#E0AE64;
          --gold-soft:#3A2E17;
          --ustadz:#8FB0C9;
          --ustadz-soft:#223140;
          --danger:#E08265;
          --danger-soft:#3B2019;
          --shadow: 0 1px 2px rgba(0,0,0,0.3), 0 8px 24px rgba(0,0,0,0.35);
        }
      }

      *{box-sizing:border-box;}
      html{-webkit-text-size-adjust:100%;}
      body{
        margin:0;
        background:var(--bg);
        color:var(--ink);
        font-family:var(--font-body);
        font-size:16px;
        line-height:1.6;
        -webkit-font-smoothing:antialiased;
      }
      @media (prefers-reduced-motion: reduce){
        *{scroll-behavior:auto !important; transition:none !important;}
      }
      html{scroll-behavior:smooth;}
      a{color:var(--accent);}
      a:focus-visible, button:focus-visible, summary:focus-visible{
        outline:2px solid var(--accent);
        outline-offset:2px;
        border-radius:2px;
      }

      /* ---------- Layout shell ---------- */
      .page{
        display:grid;
        grid-template-columns: 272px minmax(0,1fr);
        max-width:1240px;
        margin:0 auto;
        min-height:100vh;
      }
      @media (max-width: 900px){
        .page{ grid-template-columns: 1fr; }
      }

      /* ---------- Sidebar ---------- */
      .sidebar{
        background:var(--bg-sidebar);
        border-right:1px solid var(--border);
        padding:28px 20px 40px;
        position:sticky;
        top:0;
        align-self:start;
        height:100vh;
        overflow-y:auto;
      }
      .sidebar-brand{
        display:flex;
        align-items:baseline;
        gap:8px;
        margin-bottom:4px;
      }
      .sidebar-brand .mark{
        font-family:var(--font-display);
        font-weight:700;
        font-size:1.2rem;
        color:var(--accent-ink);
      }
      .sidebar-tag{
        font-size:0.72rem;
        letter-spacing:0.06em;
        text-transform:uppercase;
        color:var(--ink-faint);
        margin:0 0 22px;
      }
      .toc{list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:1px;}
      .toc a{
        display:flex;
        gap:10px;
        align-items:baseline;
        text-decoration:none;
        color:var(--ink-soft);
        font-size:0.92rem;
        padding:7px 8px;
        border-radius:5px;
      }
      .toc a:hover{ background:var(--accent-soft); color:var(--accent-ink); }
      .toc .num{
        font-family:var(--font-mono);
        font-size:0.75rem;
        color:var(--ink-faint);
        min-width:1.4em;
      }

      .nav-toggle{ display:none; }
      @media (max-width: 900px){
        .sidebar{
          position:fixed;
          inset:0 20% 0 0;
          z-index:40;
          transform:translateX(-100%);
          transition:transform 0.22s ease;
          box-shadow:var(--shadow);
          height:100dvh;
        }
        body:has(#nav-toggle:checked) .sidebar{ transform:translateX(0); }
        .nav-toggle{
          display:flex;
          align-items:center;
          gap:8px;
          position:sticky;
          top:0;
          z-index:30;
          background:var(--bg-elevated);
          border-bottom:1px solid var(--border);
          padding:12px 20px;
          font-family:var(--font-display);
          font-weight:700;
          color:var(--accent-ink);
        }
        .nav-toggle label{
          cursor:pointer;
          display:inline-flex;
          flex-direction:column;
          gap:3px;
          padding:6px;
          margin-right:2px;
        }
        .nav-toggle label span{
          width:18px; height:2px; background:var(--ink); display:block; border-radius:1px;
        }
        #nav-toggle{ position:absolute; opacity:0; pointer-events:none; }
        .scrim{
          display:none;
          position:fixed; inset:0; background:rgba(10,14,12,0.45); z-index:35;
          cursor:pointer;
        }
        body:has(#nav-toggle:checked) .scrim{ display:block; }
        .sidebar-close{
          display:flex;
          align-items:center;
          justify-content:center;
          position:absolute;
          top:14px;
          right:14px;
          width:32px;
          height:32px;
          border-radius:50%;
          background:var(--bg-elevated);
          border:1px solid var(--border);
          color:var(--ink-soft);
          font-size:1rem;
          line-height:1;
          cursor:pointer;
        }
      }
      .sidebar-close{ display:none; }

      /* ---------- Content ---------- */
      .content{ padding: 48px clamp(20px, 4vw, 64px) 96px; min-width:0; }
      .doc-header{ max-width:70ch; margin-bottom:44px; }
      .eyebrow{
        font-family:var(--font-mono);
        font-size:0.76rem;
        letter-spacing:0.08em;
        text-transform:uppercase;
        color:var(--gold);
        margin:0 0 10px;
      }
      h1.doc-title{
        font-family:var(--font-display);
        font-size:clamp(2rem, 3.4vw, 2.6rem);
        line-height:1.15;
        margin:0 0 14px;
        color:var(--accent-ink);
        text-wrap:balance;
      }
      .doc-lede{
        font-size:1.08rem;
        color:var(--ink-soft);
        max-width:62ch;
        margin:0 0 20px;
      }
      .role-legend{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        padding:14px 16px;
        background:var(--bg-elevated);
        border:1px solid var(--border);
        border-radius:8px;
      }
      .role-legend .item{ display:flex; align-items:center; gap:8px; font-size:0.88rem; color:var(--ink-soft); }

      section.module{
        max-width:70ch;
        padding-top:40px;
        margin-top:8px;
        border-top:1px solid var(--border);
        scroll-margin-top:24px;
      }
      section.module:first-of-type{ border-top:none; }
      .module-eyebrow{
        font-family:var(--font-mono);
        font-size:0.76rem;
        color:var(--ink-faint);
        margin:0 0 6px;
        letter-spacing:0.03em;
      }
      h2{
        font-family:var(--font-display);
        font-size:1.62rem;
        color:var(--accent-ink);
        margin:0 0 10px;
        text-wrap:balance;
      }
      h3{
        font-family:var(--font-display);
        font-size:1.22rem;
        color:var(--ink);
        margin:30px 0 8px;
      }
      h4{
        font-family:var(--font-body);
        font-weight:700;
        font-size:0.98rem;
        margin:20px 0 6px;
        color:var(--ink);
      }
      p{ margin:0 0 14px; }
      .content section p, .content section li{ color:var(--ink); }
      ul, ol{ padding-left:1.35em; margin:0 0 16px; }
      li{ margin-bottom:6px; }
      ol.steps{ list-style:none; padding-left:0; counter-reset:step; display:flex; flex-direction:column; gap:10px; margin:16px 0; }
      ol.steps li{
        counter-increment:step;
        display:grid;
        grid-template-columns:28px 1fr;
        gap:12px;
        margin:0;
      }
      ol.steps li::before{
        content:counter(step);
        font-family:var(--font-mono);
        font-size:0.78rem;
        color:var(--accent-ink);
        background:var(--accent-soft);
        width:28px; height:28px;
        border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-weight:600;
      }

      .access{
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        gap:8px;
        margin:0 0 18px;
        font-size:0.92rem;
      }
      .access-label{
        font-family:var(--font-mono);
        font-size:0.72rem;
        letter-spacing:0.06em;
        text-transform:uppercase;
        color:var(--ink-faint);
        margin-right:2px;
      }
      .badge{
        display:inline-flex;
        align-items:center;
        padding:3px 10px;
        border-radius:999px;
        font-size:0.78rem;
        font-weight:600;
        letter-spacing:0.01em;
        white-space:nowrap;
      }
      .b-admin{ background:var(--accent-soft); color:var(--accent-ink); }
      .b-ustadz{ background:var(--ustadz-soft); color:var(--ustadz); }
      .access-note{ color:var(--ink-soft); }

      .menu{
        font-family:var(--font-mono);
        font-size:0.86rem;
        background:var(--bg-elevated);
        border:1px solid var(--border);
        padding:2px 8px;
        border-radius:5px;
        color:var(--accent-ink);
        white-space:nowrap;
      }

      .callout{
        border-left:3px solid var(--accent);
        background:var(--bg-elevated);
        padding:12px 16px;
        border-radius:0 6px 6px 0;
        margin:16px 0 20px;
        font-size:0.94rem;
      }
      .callout p:last-child{ margin-bottom:0; }
      .callout .k{
        font-family:var(--font-mono);
        font-size:0.72rem;
        letter-spacing:0.06em;
        text-transform:uppercase;
        display:block;
        margin-bottom:5px;
      }
      .callout.tip{ border-color:var(--gold); }
      .callout.tip .k{ color:var(--gold); }
      .callout.note{ border-color:var(--accent); }
      .callout.note .k{ color:var(--accent-ink); }
      .callout.warn{ border-color:var(--danger); }
      .callout.warn .k{ color:var(--danger); }

      .table-wrap{ overflow-x:auto; margin:8px 0 22px; border:1px solid var(--border); border-radius:8px; }
      table{ border-collapse:collapse; width:100%; font-size:0.9rem; min-width:480px; }
      thead th{
        text-align:left;
        font-family:var(--font-body);
        font-weight:700;
        font-size:0.78rem;
        letter-spacing:0.03em;
        text-transform:uppercase;
        color:var(--ink-faint);
        background:var(--bg-elevated);
        padding:10px 14px;
        border-bottom:1px solid var(--border-strong);
        white-space:nowrap;
      }
      tbody td{ padding:10px 14px; border-bottom:1px solid var(--border); vertical-align:top; }
      tbody tr:last-child td{ border-bottom:none; }
      tbody tr:hover{ background:var(--bg-elevated); }
      td, th{ font-variant-numeric: tabular-nums; }

      .faq{ display:flex; flex-direction:column; gap:10px; margin:8px 0 20px; }
      .faq details{
        border:1px solid var(--border);
        border-radius:8px;
        background:var(--bg-elevated);
        padding:2px 16px;
      }
      .faq summary{
        cursor:pointer;
        padding:14px 0;
        font-weight:700;
        display:flex;
        align-items:center;
        gap:10px;
        list-style:none;
      }
      .faq summary::-webkit-details-marker{ display:none; }
      .faq summary::before{
        content:"+";
        font-family:var(--font-mono);
        color:var(--danger);
        width:1.1em;
      }
      .faq details[open] summary::before{ content:"–"; }
      .faq .a{ padding:0 0 16px 1.9em; color:var(--ink-soft); margin:0; }

      .backlink{
        display:inline-block;
        margin-top:56px;
        font-size:0.85rem;
        color:var(--ink-faint);
      }

      footer.doc-footer{
        max-width:70ch;
        margin-top:60px;
        padding-top:20px;
        border-top:1px solid var(--border);
        color:var(--ink-faint);
        font-size:0.82rem;
      }

      code{
        font-family:var(--font-mono);
        font-size:0.88em;
        background:var(--bg-elevated);
        padding:1px 5px;
        border-radius:4px;
        border:1px solid var(--border);
      }
      strong{ color:var(--ink); }
    </style>
</head>
<body>

<label for="nav-toggle" class="scrim" aria-label="Tutup daftar isi"></label>
<div class="nav-toggle">
  <label for="nav-toggle" aria-label="Buka daftar isi"><span></span><span></span><span></span></label>
  Panduan Walisantri
</div>
<input type="checkbox" id="nav-toggle" />

<div class="page">
  <nav class="sidebar" aria-label="Daftar isi">
    <label for="nav-toggle" class="sidebar-close" aria-label="Tutup daftar isi">✕</label>
    <div class="sidebar-brand"><span class="mark">Walisantri</span></div>
    <p class="sidebar-tag">Panduan Admin &amp; Ustadz</p>
    <ul class="toc">
      <li><a href="#pendahuluan"><span class="num">0</span>Pendahuluan</a></li>
      <li><a href="#dashboard"><span class="num">1</span>Dashboard</a></li>
      <li><a href="#manajemen-santri"><span class="num">2</span>Manajemen Santri</a></li>
      <li><a href="#manajemen-pengguna"><span class="num">3</span>Manajemen Pengguna</a></li>
      <li><a href="#tahfidz"><span class="num">4</span>Modul Tahfidz</a></li>
      <li><a href="#mutabaah"><span class="num">5</span>Modul Mutaba'ah</a></li>
      <li><a href="#kesantrian"><span class="num">6</span>Modul Kesantrian</a></li>
      <li><a href="#akademik"><span class="num">7</span>Modul Akademik</a></li>
      <li><a href="#rapor"><span class="num">8</span>Rapor</a></li>
      <li><a href="#keuangan"><span class="num">9</span>Keuangan</a></li>
      <li><a href="#pengumuman"><span class="num">10</span>Pengumuman</a></li>
      <li><a href="#magic-link"><span class="num">11</span>Magic Link</a></li>
      <li><a href="#preview-wali"><span class="num">12</span>Preview Portal Wali</a></li>
      <li><a href="#pengaturan"><span class="num">13</span>Pengaturan &amp; Langganan</a></li>
      <li><a href="#troubleshooting"><span class="num">14</span>Troubleshooting</a></li>
      <li><a href="#lampiran"><span class="num">15</span>Lampiran: Sisi Wali</a></li>
    </ul>
  </nav>

  <main class="content">
    <header class="doc-header">
      <p class="eyebrow">Panduan Penggunaan</p>
      <h1 class="doc-title">Walisantri, untuk Admin Pesantren &amp; Ustadz</h1>
      <p class="doc-lede">Panduan praktis per-menu — apa fungsinya, siapa yang bisa membukanya, dan langkah memakainya. Ditulis untuk dibaca sambil membuka aplikasinya di layar sebelah.</p>
      <div class="role-legend">
        <div class="item"><span class="badge b-admin">Admin</span> akses hampir penuh di pesantrennya sendiri</div>
        <div class="item"><span class="badge b-ustadz">Ustadz</span> terbatas ke santri bimbingannya, tidak bisa hapus data</div>
      </div>
    </header>

    <section class="module" id="pendahuluan">
      <p class="module-eyebrow">00 — Mulai di sini</p>
      <h2>Pendahuluan</h2>
      <p><strong>Walisantri</strong> adalah aplikasi untuk mencatat dan memantau perkembangan santri — tahfidz, mutaba'ah harian, akademik, kesehatan, karakter, keuangan (SPP &amp; uang saku), sampai laporan ke wali santri.</p>

      <h3>Cara login</h3>
      <p>Buka <code>app.walisantri.com/admin</code>, lalu login dengan email/password yang diberikan admin pesantren Anda. Panel yang tampil setelah login sama untuk semua peran — hanya menu di sisi kiri yang berbeda tergantung peran Anda.</p>

      <h3>Ringkasan peran</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Peran</th><th>Ruang lingkup akses</th></tr></thead>
          <tbody>
            <tr><td><span class="badge b-admin">Admin</span></td><td>Akses hampir penuh atas seluruh data pesantrennya sendiri: santri, kelas, kamar, semua modul pencatatan, keuangan, pengguna, pengumuman, dan pengaturan langganan.</td></tr>
            <tr><td><span class="badge b-ustadz">Ustadz</span></td><td>Akses ke santri <strong>bimbingannya sendiri</strong> (halaqah) untuk sebagian besar modul. Tidak bisa menghapus data, dan tidak bisa membuka menu Keuangan, Kelas/Kamar, atau Pengaturan.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="callout note">
        <span class="k">Aturan inti</span>
        <p>Admin bisa apa saja di pesantrennya. Ustadz hanya bisa lihat/isi data untuk santri yang dibimbingnya, dan tidak pernah bisa menghapus data.</p>
      </div>
    </section>

    <section class="module" id="dashboard">
      <p class="module-eyebrow">01</p>
      <h2>Dashboard</h2>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span><span class="access-note">— tampilan berbeda per peran</span></p>
      <p>Begitu login, Anda langsung melihat Dashboard dengan beberapa kartu statistik ringkas.</p>

      <h3>Dashboard Admin menampilkan</h3>
      <ul>
        <li>Jumlah santri aktif vs kuota paket langganan</li>
        <li>Jumlah ustadz &amp; wali santri terdaftar</li>
        <li>Jumlah santri yang sedang sakit (istirahat total / rujukan luar) hari ini</li>
        <li>Persentase rata-rata amalan mutaba'ah minggu ini (seluruh santri)</li>
        <li>Status langganan pesantren — klik kartu ini untuk langsung menuju halaman Billing</li>
      </ul>

      <h3>Dashboard Ustadz menampilkan</h3>
      <ul>
        <li>Jumlah santri binaan (halaqah)</li>
        <li>Jumlah setoran tahfidz yang sudah dicatat hari ini</li>
        <li>Jumlah santri binaan yang <strong>belum</strong> diisi mutaba'ah hari ini — jadi pengingat</li>
        <li>Jumlah santri binaan yang sedang sakit</li>
      </ul>
      <p>Di bawah kartu statistik biasanya ada grafik tren dan daftar pengumuman terbaru.</p>
    </section>

    <section class="module" id="manajemen-santri">
      <p class="module-eyebrow">02 — Menu <span class="menu">Santri</span></p>
      <h2>Manajemen Santri</h2>

      <h3>2.1 Data Santri</h3>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="access-note">lihat semua, buat, ubah, hapus</span> · <span class="badge b-ustadz">Ustadz</span><span class="access-note">lihat semua, ubah hanya santri bimbingannya</span></p>

      <h4>Menambah santri baru (Admin)</h4>
      <ol class="steps">
        <li>Buka <span class="menu">Santri → Santri</span>, klik <strong>+ New</strong> di kanan atas tabel.</li>
        <li>Isi bagian <strong>Data Santri</strong>: NIS (harus unik), Nama Lengkap, Nama Panggilan (opsional), Tanggal Lahir, Jenis Kelamin, Kelas, Kamar, status Aktif (default aktif).</li>
        <li>Isi <strong>Biodata</strong> (opsional tapi disarankan): nama ayah/ibu, alamat lengkap, jumlah saudara, cita-cita, ciri fisik.</li>
        <li>Unggah <strong>Foto Profil</strong> kalau ada (JPG/PNG, maks 2 MB).</li>
        <li>Di bagian <strong>Relasi</strong>, hubungkan santri dengan akun Wali Santri dan Ustadz Pembimbing. Tanpa Wali Santri terhubung, Magic Link portal wali tidak bisa dibuat. Satu ustadz maksimal membimbing 20 santri aktif — sistem menolak kalau sudah penuh.</li>
        <li>Klik <strong>Create</strong> untuk menyimpan.</li>
      </ol>
      <p>Untuk mengubah data santri, klik ikon pensil (Edit) pada baris santri. Ustadz hanya melihat tombol Edit untuk santri bimbingannya sendiri.</p>

      <h4>Aksi cepat per baris santri</h4>
      <ul>
        <li><strong>Link Wali</strong> — buka/salin link portal wali (lihat bagian 11)</li>
        <li><strong>Preview sebagai Wali</strong> — lihat tampilan portal wali tanpa perlu login sebagai wali (lihat bagian 12)</li>
        <li><strong>Regenerasi Link</strong> <span class="badge b-admin">Admin</span> — buat ulang link portal wali kalau link lama bocor/perlu diganti</li>
      </ul>

      <h4>Aksi massal (centang beberapa santri)</h4>
      <ul>
        <li><strong>Pindah Kelas</strong> — memindahkan sekaligus banyak santri ke kelas tujuan</li>
        <li><strong>Pindah Kamar</strong> — sama, untuk kamar</li>
        <li><strong>Hapus</strong> <span class="badge b-admin">Admin</span></li>
      </ul>
      <p>Filter tabel: jenis kelamin, kelas, kamar, status aktif/non-aktif, dan data terhapus (Trashed).</p>

      <h3>2.2 Kelas &amp; Kamar</h3>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="access-note">— menu <span class="menu">Santri → Kelas</span> dan <span class="menu">Santri → Kamar</span></span></p>
      <p>Data master — buat dulu daftar Kelas dan Kamar sebelum menambahkan santri, supaya bisa langsung dipilih di form Santri.</p>
      <ul>
        <li><strong>Kelas</strong>: isi Nama Kelas (harus unik dalam satu pesantren).</li>
        <li><strong>Kamar</strong>: isi Nama Kamar dan Kapasitas (isi <code>0</code> untuk tanpa batas kapasitas).</li>
      </ul>

      <h3>2.3 Prestasi Santri</h3>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="access-note">CRUD penuh</span> · <span class="badge b-ustadz">Ustadz</span><span class="access-note">lihat, tambah, ubah — tidak bisa hapus</span></p>
      <p>Menu <span class="menu">Santri → Prestasi</span> — mencatat pencapaian lomba/kejuaraan santri.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>, pilih Santri dan Tanggal Prestasi.</li>
        <li>Isi Judul/Nama Lomba (contoh: "Juara 1 MTQ Cabang Tilawah"), Kategori, Tingkat (internal s.d. internasional), Posisi/Peringkat (opsional), Penyelenggara, Keterangan.</li>
        <li>Unggah sertifikat/foto piala kalau ada (JPG/PNG/PDF, maks 5 MB).</li>
        <li>Simpan — prestasi otomatis muncul di portal wali santri terkait.</li>
      </ol>
    </section>

    <section class="module" id="manajemen-pengguna">
      <p class="module-eyebrow">03 — Menu <span class="menu">Pengguna</span></p>
      <h2>Manajemen Pengguna</h2>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span></p>
      <p>Buat akun <strong>Ustadz</strong> dan <strong>Wali Santri</strong> baru (juga admin pesantren lain kalau perlu).</p>
      <ol class="steps">
        <li>Buka <span class="menu">Pengguna</span>, klik <strong>+ New</strong>.</li>
        <li>Isi Nama, Email (boleh dikosongkan khusus role Wali Santri jika wali hanya punya nomor WhatsApp — magic link tetap berfungsi tanpa email), dan No. Telepon.</li>
        <li>Pilih <strong>Role</strong>: Admin Pesantren, Ustadz, atau Wali Santri.</li>
        <li>Isi Password (minimal 8 karakter) dan Konfirmasi Password.</li>
        <li>Klik <strong>Create</strong>.</li>
      </ol>
      <p>Admin hanya melihat &amp; mengelola pengguna di pesantrennya sendiri.</p>
      <div class="callout tip">
        <span class="k">Tip</span>
        <p>Setelah membuat akun ustadz baru, hubungkan ustadz tersebut ke santri yang akan dibimbingnya lewat form Edit Santri → bagian Relasi.</p>
      </div>
    </section>

    <section class="module" id="tahfidz">
      <p class="module-eyebrow">04 — Menu <span class="menu">Tahfidz</span></p>
      <h2>Modul Tahfidz</h2>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span></p>

      <h3>4.1 Setoran</h3>
      <p>Catat hafalan harian santri. Menu <span class="menu">Tahfidz → Setoran</span>.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih Santri dan Ustadz Pencatat (otomatis terisi nama Anda kalau login sebagai ustadz).</li>
        <li>Pilih Tanggal Setoran dan <strong>Tipe Setoran</strong>: Sabaq (hafalan baru), Sabqi (hafalan kemarin), atau Manzil (hafalan lama).</li>
        <li>Isi <strong>Halaman Mulai</strong> dan <strong>Halaman Selesai</strong> (capaian juz dihitung berbasis halaman mushaf, maks. halaman 600 = Juz 30). Surah terakhir bersifat opsional.</li>
        <li>Isi <strong>Nilai Kelancaran</strong>: Mumtaz, Jayyid Jiddan, Jayyid, atau Maqbul, plus Catatan Evaluasi (opsional).</li>
        <li>Simpan.</li>
      </ol>
      <p>Ustadz hanya mencatat/mengubah setoran santri bimbingannya, dan tidak bisa menghapus — hanya admin yang bisa.</p>

      <h3>4.2 Ujian</h3>
      <p>Catat hasil ujian kenaikan juz. Menu <span class="menu">Tahfidz → Ujian</span>.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih Santri, Penguji, Tanggal Ujian, Target Juz, dan Status Kelulusan (Lulus/Mengulang).</li>
        <li>Isi <strong>Periode Rapor</strong>: Tahun Ajaran, Periode (Bulanan / Semester Ganjil / Semester Genap — pilih Bulan kalau Bulanan). Ini menentukan rapor periode mana yang akan menampilkan hasil ujian ini.</li>
        <li>Isi <strong>Penilaian</strong>: Nilai Hafalan, Tilawah/Makhraj/Tajwid (skala A–D), Rekomendasi Pembimbing (wajib).</li>
        <li>Simpan.</li>
      </ol>

      <h3>4.3 Rapor Tahfidz</h3>
      <p>Menu <span class="menu">Tahfidz → Rapor</span> — ringkasan capaian per santri per periode (juga bisa diakses lewat Cluster Rapor, lihat bagian 8). Pilih santri dan periode; sistem menampilkan total setoran, total juz tercapai, hari aktif setor, distribusi nilai, dan hasil ujian. Tersedia tombol <strong>Unduh PDF</strong>.</p>
    </section>

    <section class="module" id="mutabaah">
      <p class="module-eyebrow">05 — Menu <span class="menu">Mutabaah</span></p>
      <h2>Modul Mutaba'ah</h2>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span></p>
      <p>Catatan amalan harian santri (sholat, tilawah, dsb.) — daftar amalannya bisa disesuaikan per pesantren.</p>

      <h3>5.1 Amal Master</h3>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="access-note">— menu <span class="menu">Mutabaah → Amal Master</span></span></p>
      <p>Tentukan amalan apa saja yang dicatat setiap hari.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Isi Nama Amal (contoh: "Sholat Berjamaah"), Ikon emoji (opsional), dan <strong>Tipe Penilaian</strong>: Centang (dikerjakan/tidak) untuk amalan ya/tidak, atau Hitungan (misal 0–5 waktu) untuk amalan yang dihitung — isi juga Nilai Maksimal.</li>
        <li>Isi Satuan (default "hari"), Bobot Poin (kontribusi amal ini ke skor harian), dan Urutan Tampil.</li>
        <li>Toggle <strong>Aktif</strong> — amal nonaktif tidak muncul lagi di form input harian, riwayat lama tetap tersimpan.</li>
        <li>Simpan.</li>
      </ol>

      <h3>5.2 Isi Harian</h3>
      <p>Menu <span class="menu">Mutabaah → Isi Harian</span> — cara tercepat mengisi mutaba'ah banyak santri sekaligus dalam satu hari.</p>
      <ol class="steps">
        <li>Pilih <strong>Tanggal</strong>. Sistem menampilkan daftar santri (Admin: semua santri aktif; Ustadz: hanya bimbingannya) beserta form amalan — data yang sudah pernah diisi otomatis muncul kembali dan bisa diubah.</li>
        <li>Untuk tiap santri, isi kolom <strong>Udzur</strong> (Tidak/Sakit/Haid/Izin Pulang/Tugas Pondok) dan nilai tiap amalan.</li>
        <li>Klik <strong>Simpan Semua</strong> di bawah — semua baris tersimpan sekaligus.</li>
      </ol>
      <div class="callout note">
        <span class="k">Catatan</span>
        <p>Kalau status Udzur diubah jadi "Sakit", atau ada rekam kesehatan bertipe "Istirahat Total"/"Rujukan Luar" pada tanggal yang sama, statusnya saling sinkron otomatis.</p>
      </div>

      <h3>5.3 Mutabaah (log per-santri)</h3>
      <p>Menu <span class="menu">Mutabaah → Mutabaah</span> — form alternatif untuk mengisi/mengubah satu entri per santri per tanggal, berguna untuk koreksi data satu-satu.</p>
    </section>

    <section class="module" id="kesantrian">
      <p class="module-eyebrow">06 — Menu <span class="menu">Kesantrian</span></p>
      <h2>Modul Kesantrian</h2>

      <h3>6.1 Karakter (Rapor Karakter)</h3>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span></p>
      <p>Menu <span class="menu">Kesantrian → Karakter</span> — penilaian adab &amp; kepribadian per periode.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih Santri, Tahun Ajaran, Periode, Bulan (kalau Bulanan), Tanggal Input.</li>
        <li>Isi <strong>Penilaian Adab</strong> (skala A–D): ke Ustadz, ke Tamu, Asrama, Kelas, Sholat, Al-Quran, Minum.</li>
        <li>Isi <strong>Penilaian Kepribadian</strong> (skala A–D): Tanggung Jawab, Kemandirian, Kepatuhan, Kebersihan, Mengelola Diri, Kepedulian, Empati, Kebersamaan, Kedisiplinan.</li>
        <li>Isi <strong>Log Kasus Khusus</strong> kalau ada catatan penting (opsional).</li>
        <li>Simpan — sistem menolak entri duplikat untuk kombinasi santri + periode yang sama.</li>
      </ol>

      <h3>6.2 Kesehatan</h3>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span><span class="access-note">— tersedia di semua paket</span></p>
      <p>Menu <span class="menu">Kesantrian → Kesehatan</span> — rekam medis santri.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih Santri dan Tanggal Periksa (satu santri hanya bisa punya satu rekam per tanggal).</li>
        <li>Isi Berat &amp; Tinggi Badan (opsional).</li>
        <li>Pilih <strong>Jenis Rekam</strong>: "Keluhan Sakit" atau "Pemeriksaan Rutin" — untuk Rutin, bagian Keluhan &amp; Tindakan otomatis disembunyikan.</li>
        <li>Untuk Keluhan Sakit: isi Kategori Keluhan, Detail Keluhan, Tindakan &amp; Obat, dan <strong>Status Pemulihan</strong> (Rawat Mandiri / Istirahat Total / Rujukan Luar / Sembuh).</li>
        <li>Kalau Status Pemulihan "Sembuh", isi juga Tanggal Sembuh.</li>
        <li>Simpan.</li>
      </ol>
      <div class="callout warn">
        <span class="k">Efek samping penting</span>
        <p>Status "Istirahat Total" atau "Rujukan Luar" otomatis memicu status udzur "Sakit" di mutaba'ah tanggal yang sama, dan santri tersebut muncul di kartu "Santri Sakit" pada Dashboard.</p>
      </div>

      <h3>6.3 Inventaris</h3>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span><span class="access-note">— khusus paket Maju</span></p>
      <p>Menu <span class="menu">Kesantrian → Inventaris</span> — mencatat barang milik santri yang dititipkan di pesantren.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih Santri, isi Nama Barang, Kode Unik Fisik (unik, contoh: <code>FZ-SRG-01</code>), Kuota Maks, Kondisi (Baik / Layak Pakai-Rusak Ringan / Hilang), Tanggal Sidak Terakhir (opsional).</li>
        <li>Simpan.</li>
      </ol>
    </section>

    <section class="module" id="akademik">
      <p class="module-eyebrow">07 — Menu <span class="menu">Akademik</span></p>
      <h2>Modul Akademik</h2>

      <h3>7.1 Mata Pelajaran (master)</h3>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span></p>
      <p>Menu <span class="menu">Akademik → Mata Pelajaran</span> — daftarkan mata pelajaran per kelas beserta ustadz pengampunya (satu ustadz = satu mapel tetap; ustadz hanya bisa input nilai untuk mapel yang diampunya).</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih Kelas, Ustadz Pengampu (opsional), isi Nama Mata Pelajaran.</li>
        <li>Simpan.</li>
      </ol>

      <h3>7.2 Nilai Akademik</h3>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span><span class="access-note">— ustadz dibatasi ke mapel yang ia ampu</span></p>
      <p>Menu <span class="menu">Akademik → Nilai Akademik</span>.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih <strong>Mata Pelajaran</strong> dulu — dropdown Santri berikutnya otomatis menyesuaikan kelas dari mapel yang dipilih.</li>
        <li>Pilih Santri, Tahun Ajaran, Periode, Bulan (kalau Bulanan).</li>
        <li>Isi Nilai (0–100) dan Catatan (opsional).</li>
        <li>Simpan — rapor akademik dihitung otomatis dari kumpulan nilai ini, lihat bagian 8.</li>
      </ol>

      <h3>7.3 Ekstrakurikuler</h3>
      <p><strong>Ekskul (master)</strong> <span class="badge b-admin">Admin</span> — menu <span class="menu">Akademik → Ekskul</span>: daftarkan nama ekskul, Nama Pembina, Deskripsi, status Aktif.</p>
      <p><strong>Ekskul Santri (partisipasi)</strong> <span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span> — menu <span class="menu">Akademik → Ekskul Santri</span>: catat santri mana yang ikut ekskul apa.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih Santri dan Ekskul.</li>
        <li>Pilih <strong>Level</strong>: Pemula, Menengah, atau Mahir.</li>
        <li>Isi Tanggal Mulai, toggle Aktif (nonaktifkan tanpa menghapus riwayat).</li>
        <li>Simpan.</li>
      </ol>
    </section>

    <section class="module" id="rapor">
      <p class="module-eyebrow">08 — Menu <span class="menu">Rapor</span></p>
      <h2>Rapor</h2>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span></p>
      <p>Empat halaman untuk melihat &amp; mencetak rekap per santri per periode — semuanya dihitung otomatis dari data yang diinput di modul masing-masing.</p>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Halaman</th><th>Isi</th><th>Sumber data</th></tr></thead>
          <tbody>
            <tr><td>Akademik</td><td>Rata-rata nilai per mapel, ekskul aktif</td><td>Nilai Akademik &amp; Ekskul</td></tr>
            <tr><td>Tahfidz</td><td>Total setoran, total juz tercapai, hasil ujian</td><td>Setoran &amp; Ujian Tahfidz</td></tr>
            <tr><td>Mutabaah</td><td>Rekap persentase capaian tiap amalan sebulan</td><td>Mutaba'ah harian</td></tr>
            <tr><td>Karakter</td><td>Nilai adab &amp; kepribadian per periode</td><td>Karakter Rapor</td></tr>
          </tbody>
        </table>
      </div>
      <p><strong>Cara pakai (sama di keempat halaman):</strong> pilih Santri, pilih Tahun Ajaran dan Periode (Bulanan/Semester — pilih Bulan kalau Bulanan), data tampil otomatis. Klik <strong>Unduh PDF</strong> di kanan atas. Kalau belum ada data, sistem memberi tahu "Belum ada data" alih-alih mencetak rapor kosong.</p>
      <p>Ustadz hanya bisa memilih santri bimbingannya sendiri di dropdown Santri.</p>
    </section>

    <section class="module" id="keuangan">
      <p class="module-eyebrow">09 — Menu <span class="menu">Keuangan</span></p>
      <h2>Keuangan</h2>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="access-note">— ustadz tidak punya akses ke modul ini sama sekali</span></p>

      <h3>9.1 Tarif SPP</h3>
      <p>Menu <span class="menu">Keuangan → Tarif SPP</span> — tentukan nominal SPP bulanan per kelas.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih Kelas, isi Nominal SPP (Rp), Keterangan (opsional).</li>
        <li>Simpan — nominal ini menjadi acuan saat generate tagihan.</li>
      </ol>

      <h3>9.2 Tagihan SPP</h3>
      <p>Menu <span class="menu">Keuangan → Tagihan SPP</span>.</p>
      <h4>Membuat tagihan bulanan untuk semua santri sekaligus</h4>
      <ol class="steps">
        <li>Klik <strong>Generate Tagihan Massal</strong> di atas tabel.</li>
        <li>Pilih Bulan dan Tahun, isi Jatuh Tempo (opsional) dan Keterangan (default "SPP Bulanan").</li>
        <li>Proses — sistem otomatis membuat tagihan untuk semua santri aktif berdasarkan tarif kelas masing-masing. Santri yang kelasnya belum punya Tarif SPP, atau tagihan bulan itu sudah ada, akan dilewati (jumlahnya dilaporkan di notifikasi).</li>
      </ol>
      <h4>Menandai tagihan lunas</h4>
      <p>Klik <strong>Tandai Lunas</strong> pada baris tagihan, isi Tanggal Bayar, Metode Bayar, Catatan opsional, lalu simpan. Tagihan dengan bukti transfer dari wali (status "Menunggu Konfirmasi") ditandai badge <strong>!</strong> — cek bukti transfernya di halaman Detail sebelum menandai lunas.</p>
      <h4>Menolak konfirmasi transfer</h4>
      <p>Kalau bukti transfer wali tidak valid, klik <strong>Tolak</strong> — status kembali "Belum Bayar" dan bukti yang salah dihapus dari sistem.</p>
      <p>Filter tabel: Status, Bulan, Tahun.</p>

      <h3>9.3 Uang Saku Santri</h3>
      <p>Menu <span class="menu">Keuangan → Uang Saku</span> — ledger setoran/pengambilan uang saku per santri.</p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Pilih Santri, Jenis Transaksi (Setoran/Pengambilan), Nominal (Rp), Tanggal, Keterangan (opsional).</li>
        <li>Simpan.</li>
      </ol>
      <p>Rekap saldo semua santri: menu <span class="menu">Keuangan → Saldo Santri</span> — tabel saldo (setoran − pengambilan) per santri, bisa dicari berdasarkan nama.</p>
    </section>

    <section class="module" id="pengumuman">
      <p class="module-eyebrow">10 — Menu <span class="menu">Pengumuman</span></p>
      <h2>Pengumuman</h2>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="access-note">buat, ubah, hapus</span> · <span class="badge b-ustadz">Ustadz</span><span class="access-note">hanya lihat</span></p>
      <ol class="steps">
        <li>Klik <strong>+ New</strong>. Isi Judul Pengumuman, Isi Pengumuman (editor teks kaya — bold, italic, list, heading).</li>
        <li>Pilih <strong>Tampilkan Kepada</strong>: Semua Pengguna, Admin &amp; Ustadz Pesantren saja, atau Wali Santri saja.</li>
        <li>Simpan — pengumuman langsung tampil ke audiens terpilih, termasuk feed portal wali dan dashboard.</li>
      </ol>
    </section>

    <section class="module" id="magic-link">
      <p class="module-eyebrow">11</p>
      <h2>Magic Link — Akses Portal Wali</h2>
      <p class="access"><span class="access-label">Akses</span><span class="access-note">tombol "Link Wali" untuk </span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span><span class="access-note"> · "Regenerasi Link" khusus </span><span class="badge b-admin">Admin</span></p>
      <p>Wali santri tidak login dengan email/password — mereka mengakses laporan anaknya lewat <strong>link unik tanpa password</strong> (Magic Link).</p>
      <h4>Cara memberikan akses ke wali</h4>
      <ol class="steps">
        <li>Buka <span class="menu">Santri → Santri</span>, cari santri yang dimaksud.</li>
        <li>Pastikan santri tersebut sudah terhubung ke akun Wali Santri (lihat bagian 2.1, Relasi) — kalau belum, tombol Link Wali nonaktif dengan keterangan untuk menghubungkan dulu lewat Edit.</li>
        <li>Klik tombol <strong>Link Wali</strong> pada baris santri. Modal menampilkan URL unik (<code>app.walisantri.com/report/{kode-unik}</code>).</li>
        <li>Salin/kirim link ke wali santri (misal lewat WhatsApp). Wali cukup membuka link ini untuk langsung masuk ke portal laporan anaknya, tanpa login.</li>
      </ol>
      <div class="callout warn">
        <span class="k">Kapan perlu Regenerasi Link — Admin saja</span>
        <p>Kalau link lama diduga bocor ke pihak yang tidak berhak, klik <strong>Regenerasi Link</strong> — link lama langsung tidak berlaku dan Anda perlu mengirim link baru ke wali.</p>
      </div>
    </section>

    <section class="module" id="preview-wali">
      <p class="module-eyebrow">12</p>
      <h2>Preview Portal Wali</h2>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span><span class="badge b-ustadz">Ustadz</span></p>
      <p>Kadang Anda perlu mengecek tampilan laporan santri dari sudut pandang wali — tanpa Magic Link atau logout. Klik <strong>Preview sebagai Wali</strong> pada baris santri di menu Santri — halaman laporan terbuka di tab baru persis seperti yang dilihat wali santri (read-only).</p>
      <p>Berguna untuk QA sebelum mengirim link ke wali, atau membantu wali yang bingung lewat telepon.</p>
    </section>

    <section class="module" id="pengaturan">
      <p class="module-eyebrow">13 — Menu <span class="menu">Pengaturan</span></p>
      <h2>Pengaturan Pesantren &amp; Langganan</h2>
      <p class="access"><span class="access-label">Akses</span><span class="badge b-admin">Admin</span></p>

      <h3>13.1 Pengaturan Profil Pesantren</h3>
      <p>Menu <span class="menu">Pengaturan → Pengaturan</span> — identitas dan profil publik pesantren (tampil di <code>{slug}.walisantri.com</code>):</p>
      <ul>
        <li><strong>Identitas Pesantren</strong>: Nama Pesantren, Subdomain. Mengubah slug melepas slug lama ke masa tunggu 90 hari sebelum bisa dipakai pesantren lain.</li>
        <li><strong>Logo &amp; Galeri</strong>: logo (PNG/JPG/SVG, maks 1 MB), galeri foto (maks 12, bisa diurutkan ulang).</li>
        <li><strong>Profil Publik</strong>: Nomor Telepon, Alamat, Deskripsi Singkat.</li>
        <li><strong>Program &amp; Jenjang Pendidikan</strong>: daftar program yang tampil di profil publik.</li>
        <li><strong>Statistik Ringkas</strong>: Tahun Berdiri, Akreditasi (jumlah santri dihitung otomatis).</li>
        <li><strong>Rekening Pembayaran SPP</strong>: daftar rekening yang ditampilkan ke wali saat melihat tagihan SPP — bisa lebih dari satu.</li>
      </ul>
      <p>Klik <strong>Simpan Perubahan</strong> setelah selesai.</p>

      <h3>13.2 Billing (Informasi Langganan)</h3>
      <p>Menu <span class="menu">Pengaturan → Billing</span> — status langganan (paket, kuota santri, tanggal berakhir) dan detail order aktif kalau sedang proses upgrade. Tombol <strong>Upgrade / Perpanjang Paket</strong> membawa ke halaman Upgrade.</p>

      <h3>13.3 Upgrade / Perpanjang Paket</h3>
      <ol class="steps">
        <li>Dari halaman Billing, klik <strong>Upgrade / Perpanjang Paket</strong>.</li>
        <li>Pilih <strong>Paket Tujuan</strong>. Untuk paket Maju, atur juga Kuota Santri (minimum 1.000, kelipatan 100).</li>
        <li>Pilih <strong>Durasi Langganan</strong>. Kalau sisa masa aktif langganan masih panjang (&gt; 6 atau 9 bulan), sistem mewajibkan durasi minimum lebih lama (6 atau 12 bulan) — dijelaskan otomatis di halaman.</li>
        <li>Masukkan <strong>Kode Kupon</strong> kalau ada, klik Terapkan untuk validasi diskon.</li>
        <li>Klik <strong>Lakukan Pembayaran</strong> — Anda diarahkan ke halaman Invoice.</li>
        <li>Di halaman Invoice, lihat rekening bank platform yang tersedia, transfer sesuai nominal, unggah bukti transfer (JPG/PNG/PDF, maks 5 MB), klik <strong>Kirim Bukti Transfer</strong>.</li>
        <li>Tim platform memverifikasi dalam 1×24 jam. Setelah dikonfirmasi, paket baru otomatis aktif.</li>
      </ol>
      <div class="callout note">
        <span class="k">Catatan</span>
        <p>Menurunkan paket (downgrade) tidak menghapus data — modul yang tidak tersedia di paket baru hanya dikunci sementara, datanya tetap tersimpan dan bisa diakses lagi setelah upgrade ulang.</p>
      </div>
    </section>

    <section class="module" id="troubleshooting">
      <p class="module-eyebrow">14</p>
      <h2>Troubleshooting Umum</h2>
      <div class="faq">
        <details>
          <summary>"Langganan pesantren telah berakhir" / diarahkan paksa ke halaman Billing</summary>
          <p class="a">Paket langganan pesantren sudah kadaluwarsa. Hanya Admin Pesantren yang bisa membuka halaman Billing untuk memperpanjang — kalau Anda login sebagai Ustadz dan mendapat pesan ini, hubungi admin pesantren Anda.</p>
        </details>
        <details>
          <summary>"Batas kuota paket tercapai!" saat menambah/mengimpor santri</summary>
          <p class="a">Jumlah santri aktif sudah mencapai batas maksimal paket. Nonaktifkan santri yang sudah tidak aktif (bukan dihapus) untuk melepas kuota, atau upgrade paket lewat menu Billing.</p>
        </details>
        <details>
          <summary>"Ustadz ini sudah mencapai batas maksimal 20 santri"</summary>
          <p class="a">Satu ustadz maksimal membimbing 20 santri aktif sekaligus. Pilih ustadz lain sebagai pembimbing, atau kurangi jumlah santri bimbingan ustadz tersebut.</p>
        </details>
        <details>
          <summary>Menu tertentu (misal Inventaris) tidak muncul di sidebar</summary>
          <p class="a">Beberapa modul hanya tersedia di paket tertentu (Inventaris khusus paket Maju). Cek halaman Billing untuk melihat paket aktif, atau upgrade kalau modul tersebut dibutuhkan.</p>
        </details>
        <details>
          <summary>Tombol "Link Wali" nonaktif/abu-abu</summary>
          <p class="a">Santri belum terhubung ke akun Wali Santri. Buka Edit Santri → bagian Relasi → pilih Wali Santri-nya, simpan, baru tombol Link Wali bisa dipakai.</p>
        </details>
        <details>
          <summary>Wali santri melapor tidak bisa membuka link laporan</summary>
          <p class="a">Kemungkinan link sudah pernah di-regenerasi (link lama otomatis tidak berlaku begitu link baru dibuat). Buka menu Santri, klik Link Wali untuk link terbaru, kirim ulang ke wali.</p>
        </details>
        <details>
          <summary>Ustadz tidak bisa melihat/mengubah data seorang santri</summary>
          <p class="a">Cek apakah santri sudah diset sebagai bimbingan ustadz itu (field Ustadz Pembimbing di form Santri) — ustadz hanya bisa mengelola data santri bimbingannya sendiri di sebagian besar modul.</p>
        </details>
      </div>
    </section>

    <section class="module" id="lampiran">
      <p class="module-eyebrow">15 — Referensi</p>
      <h2>Lampiran: Apa yang Dilihat Wali Santri</h2>
      <p>Wali santri mengakses portal terpisah lewat Magic Link, tampilannya read-only (tanpa bisa mengubah data), berisi:</p>
      <ul>
        <li><strong>Beranda</strong> — sapaan, ringkasan singkat, alert kalau anaknya sedang sakit atau ada tunggakan SPP</li>
        <li><strong>Detail Santri</strong> — biodata, capaian tahfidz, statistik kesehatan, prestasi, ekstrakurikuler aktif</li>
        <li><strong>Mutaba'ah</strong> — tabel amalan harian anaknya dengan filter tanggal</li>
        <li><strong>Rapor</strong> — tab Tahfidz, Akademik, Karakter, Mutabaah per periode + unduh PDF</li>
        <li><strong>SPP</strong> — daftar tagihan, info rekening bank pesantren, tombol "Saya Sudah Transfer"</li>
        <li><strong>Uang Saku</strong> — saldo dan riwayat setoran/pengambilan</li>
        <li><strong>Inventaris</strong> — daftar barang titipan anaknya (paket Maju)</li>
        <li><strong>Pengumuman</strong> — daftar pengumuman yang ditujukan ke wali/semua pengguna</li>
      </ul>
      <p>Bottom navigation di portal wali: <strong>Beranda · SPP · Pengumuman · Uang Saku · Rapor</strong>.</p>
      <p>Memahami tampilan ini membantu Anda menjelaskan ke wali santri apa yang akan mereka lihat setelah Anda menginput data — atau gunakan <a href="#preview-wali">Preview sebagai Wali</a> untuk melihat langsung dari akun Anda sendiri.</p>
      <a class="backlink" href="#pendahuluan">↑ Kembali ke Pendahuluan</a>
    </section>

    <footer class="doc-footer">
      Panduan internal Walisantri untuk Admin Pesantren &amp; Ustadz.
    </footer>
  </main>
</div>
</body>
</html>
