@extends('auth.layouts.polos')

@section('judul', 'Lupa Kata Sandi')
@section('subjudul', 'Pemulihan akses akun')

@section('isi')
    <p class="text-sm text-gray-600 mb-4">
        Masukkan alamat email akun Anda. Kami akan mengirim tautan untuk membuat kata sandi baru.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   required autofocus
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm
                          focus:outline-none focus:ring-2 focus:ring-teal-500"
                   placeholder="email@contoh.com">
        </div>

        <button type="submit"
                class="w-full font-semibold py-2.5 rounded-xl transition-colors text-white bg-teal-700 hover:bg-teal-800">
            Kirim Tautan
        </button>
    </form>

    <p class="text-center text-sm mt-4">
        <a href="{{ route('login') }}" class="text-teal-700 hover:underline">Kembali ke halaman masuk</a>
    </p>
@endsection

@section('catatan')
    Hanya untuk admin pesantren dan ustadz.<br>
    Wali santri masuk lewat tautan portal dari pesantren.
@endsection
