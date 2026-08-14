@extends('auth.layouts.polos')

@section('judul', 'Kata Sandi Baru')
@section('subjudul', 'Buat kata sandi baru')

@section('isi')
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $email) }}"
                   required
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm
                          focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kata sandi baru</label>
            <input type="password" name="password" required autofocus
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm
                          focus:outline-none focus:ring-2 focus:ring-teal-500"
                   placeholder="••••••••">
            <p class="text-xs text-gray-500 mt-1">Minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ulangi kata sandi baru</label>
            <input type="password" name="password_confirmation" required
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm
                          focus:outline-none focus:ring-2 focus:ring-teal-500"
                   placeholder="••••••••">
        </div>

        <button type="submit"
                class="w-full font-semibold py-2.5 rounded-xl transition-colors text-white bg-teal-700 hover:bg-teal-800">
            Simpan Kata Sandi
        </button>
    </form>
@endsection

@section('catatan')
    Tautan ini hanya berlaku sekali pakai.
@endsection
