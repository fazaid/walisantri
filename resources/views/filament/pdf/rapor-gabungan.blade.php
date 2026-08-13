@php
    // Hanya modul berisi data yang dicetak, dan modul pertama menempel di
    // halaman kop supaya tidak ada halaman kosong di depan.
    $modulBerisi = collect($data)->filter(fn ($isi) => $isi['ada_data']);
    $isiRapor = $modulBerisi->keys()->map(fn ($key) => $modulLabels[$key] ?? $key)->implode(', ');
@endphp

@extends('filament.pdf.rapor.layout', ['isiRapor' => $isiRapor])

@section('isi')
    @foreach($modulBerisi as $key => $isi)
        <div @class(['modul', 'modul-pertama' => $loop->first])>
            <div class="modul-title">{{ $modulLabels[$key] ?? $key }}</div>
            @include('filament.pdf.rapor.' . $key, ['modul' => $isi])
        </div>
    @endforeach
@endsection
