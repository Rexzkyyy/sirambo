@props([
    'itemRows',
    'tipePdrb',
    'template' => 'general',
    'headers' => []
])

@php
    $rowsId = $tipePdrb === 'berlaku' ? 'lk-rows-berlaku' : 'lk-rows-konstan';
    $totalPrefix = $tipePdrb === 'berlaku' ? 'data-total' : 'data-total-konstan';
@endphp

<div class="lk-table-scroll shadow-sm border border-slate-200 rounded-xl flex-1 bg-white">
    <table class="w-full text-xs relative lk-sticky-table lk-dynamic-table">
        <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 shadow-[0_1px_0_0_rgba(0,0,0,0.1)]">
            {{ $headerSlot }}
        </thead>
        <tbody id="{{ $rowsId }}" data-next-row="{{ collect($itemRows ?? [])->count() }}">
            {{ $slot }}
        </tbody>
        <tfoot>
            {{ $footerSlot ?? '' }}
        </tfoot>
    </table>
</div>

@push('scripts')
<script src="{{ asset('js/lk_dynamic_calculator.js') }}"></script>
@endpush
