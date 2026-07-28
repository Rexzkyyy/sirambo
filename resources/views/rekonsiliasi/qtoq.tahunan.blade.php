@extends('layouts.main')

@section('title', 'Q to Q Pertahun')

@section('content')
<div class="space-y-6">

    <h2 class="text-2xl font-bold text-blue-700">
        Pertumbuhan Q to Q Tahun {{ $tahun }}
    </h2>

    <div class="overflow-x-auto border rounded shadow-md">
        <table class="table-auto w-full min-w-[800px] border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-3 py-2 text-left">Kategori</th>
                    <th class="border px-3 py-2 text-right">TW I</th>
                    <th class="border px-3 py-2 text-right">TW II</th>
                    <th class="border px-3 py-2 text-right">TW III</th>
                    <th class="border px-3 py-2 text-right">TW IV</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        <td class="border px-3 py-2">
                            {{ $row['kategori'] }}
                        </td>

                        @for($i = 1; $i <= 4; $i++)
                            <td class="border px-3 py-2 text-right">
                                @if($row['tw'.$i] !== null)
                                    {{ number_format($row['tw'.$i], 2, ',', '.') }}%
                                @else
                                    -
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('rekonsiliasi.qtoq') }}"
       class="inline-block mt-4 px-4 py-2 bg-gray-600 text-white rounded">
        ← Kembali
    </a>

</div>
@endsection
