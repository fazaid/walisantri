{{-- File: resources/views/wali/santri/show.blade.php --}}
@extends('wali.layouts.app')

@section('title', $santri->nama_lengkap)
@section('subtitle', ($santri->kelas?->nama_kelas ?? '—') . ' · ' . ($santri->kamar?->nama_kamar ?? '—'))
@section('back_url', ($previewMode ?? false) ? route('filament.admin.santri.resources.santris.view', $santri) : route('wali.dashboard'))

@section('content')
@include('wali.partials.santri-detail')
@endsection
