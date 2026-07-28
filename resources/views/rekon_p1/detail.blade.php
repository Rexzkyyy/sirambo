@extends('layouts.main')

@section('title', 'Detail Rekonsiliasi P1')

@section('content')
    @php
        $user = auth()->user();
        $isProvinsi = in_array($user->role, ['provinsi', 'provinsi_supervisor'], true);
        $isRekonFixer = $user->role === 'provinsi_supervisor';
        $isDaerah = in_array($user->role, ['kabupaten', 'kota']);
        $userWilayah = $user->id_wilayah;
        $isKategori = $isKategori ?? false;
    @endphp

    <style>
        html,
        body {
            height: 100%;
        }

        main {
            overflow: hidden !important;
        }

        main>div {
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        main>div>div {
            height: 100%;
            min-height: 0 !important;
            display: flex;
            flex-direction: column;
        }
    </style>

    <div class="flex-1 min-h-0 bg-gray-50 p-2 md:p-4 overflow-hidden flex flex-col">
        @include('rekon_p1.partials.detail.header')

        <!-- NOTIFICATION AREA -->
        <div id="notification-area" class="fixed top-4 right-4 z-50 w-[92vw] max-w-sm space-y-2"></div>

        <div class="flex-1 min-h-0">
            @include('rekon_p1.partials.detail.table')
        </div>
    </div>

    @include('rekon_p1.partials.detail.scripts')
    @include('rekon_p1.partials.detail.styles')
@endsection