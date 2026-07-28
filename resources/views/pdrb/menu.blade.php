@extends('layouts.main')

@section('title', 'Dashboard PDRB')

@section('content')
<div class="w-full space-y-8">

    {{-- Judul --}}
    <div class="text-center">
        <h2 class="text-3xl font-bold text-blue-700">Data Resume PDRB</h2>
        <p class="text-gray-500 mt-1 text-sm">
            Pilih parameter untuk menampilkan data resume PDRB
        </p>
    </div>

    {{-- Card Filter --}}
    <div class="bg-white border rounded-xl shadow-sm p-6 max-w-4xl mx-auto">

        <form method="GET"
              action="{{ route('rekonsiliasi.redirect') }}"
              class="space-y-6">

            {{-- Baris Filter --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                {{-- Tahun --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">
                        Tahun
                    </label>
                    <select name="tahun"
                            class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200">
                        @foreach($tahunList as $t)
                            <option value="{{ $t->id_tahun }}">
                                {{ $t->tahun }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Triwulan --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">
                        Triwulan
                    </label>
                    <select name="triwulan"
                            class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200">
                        <option value="1">Triwulan I</option>
                        <option value="2">Triwulan II</option>
                        <option value="3">Triwulan III</option>
                        <option value="4">Triwulan IV</option>
                        <option value="all">Semua Triwulan</option>
                    </select>
                </div>

                {{-- Jenis PDRB --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">
                        Jenis PDRB
                    </label>
                    <select name="jenis"
                            class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-200">
                        <option value="diskrepansi">Diskrepansi PDRB</option>
                        <option value="qtoq">Q to Q</option>
                        <option value="ytoy">Y on Y</option>
                        <option value="ctoc">C to C</option>
                        <option value="indeks-implisit">Indeks Implisit</option>
                        <option value="laju-implisit">Laju Implisit</option>
                        <option value="struktur-dalam">Struktur Dalam</option>
                        <option value="struktur-antar">Struktur Antar</option>
                    </select>
                </div>
            </div>

            {{-- Tombol --}}
            <div class="flex justify-end pt-4 border-t">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg
                               hover:bg-blue-700 transition font-semibold">
                    Tampilkan Data
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
