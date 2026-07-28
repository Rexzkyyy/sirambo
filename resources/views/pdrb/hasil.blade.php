{{-- resources/views/pdrb/hasil.blade.php --}}
@extends('layouts.main')

@section('title', 'Hasil Input Nilai PDRB')

@section('content')
    <style>
        /* Force height chain for full-screen layout without page scroll */
        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        /* Target the main layout structure from layouts.main */
        main {
            overflow: hidden !important;
            display: flex !important;
            flex-direction: column !important;
        }

        /* Target the padding div and white card inside main */
        main>div {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            min-height: 0 !important;
            height: 100% !important;
            padding-bottom: 1rem !important;
        }

        main>div>div {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            min-height: 0 !important;
            height: 100% !important;
        }

        /* Ensure specific containers inside the card also fill available height */
        .hasil-content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
    </style>

    <div class="w-full flex-1 flex flex-col min-h-0 h-full gap-4 hasil-content-wrapper">
        @include('pdrb.hasil.partials.header')

        <div class="flex flex-col xl:flex-row gap-4">
            @include('pdrb.hasil.partials.summary')
            <div class="flex-1 min-w-0">
                @include('pdrb.hasil.partials.filters')
            </div>
        </div>

        <div class="flex-1 min-h-0">
            @include('pdrb.hasil.partials.table')
        </div>
    </div>

    @include('pdrb.hasil.partials.scripts')
@endsection