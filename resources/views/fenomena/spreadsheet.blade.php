@extends('layouts.main')

@section('title', 'Input Fenomena Spreadsheet')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/jsuites.min.css') }}?v=1.0.0" />
    <link rel="stylesheet" href="{{ asset('assets/css/jspreadsheet.min.css') }}?v=1.0.0" />
@endsection

@section('scripts')
    <script src="{{ asset('assets/js/jsuites.min.js') }}?v=1.0.0" defer></script>
    <script src="{{ asset('assets/js/jspreadsheet.min.js') }}?v=1.0.0" defer></script>
@endsection

@section('content')
<div class="container py-4">
    <h1 class="mb-4 text-xl font-bold">Input Fenomena (Spreadsheet)</h1>

    <form action="{{ route('fenomena.store') }}" method="POST" id="fenomena-form">
        @csrf
        <div id="spreadsheet" style="height: 500px;"></div>

        <div class="mt-4 flex gap-2">
            <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" id="add-row-btn">
                Tambah Baris
            </button>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Simpan Semua
            </button>
        </div>
    </form>
</div>

<!-- HAPUS LOADING CSS/JS DI SINI -->

<script>
// SCRIPT TETAP SAMA, bungkus dengan DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    const kategoriList = @json($kategori->map(fn($k) => [
        'id' => $k->id_kategori,
        'nama' => $k->nama_kategori,
        'sub' => $k->subKategori->map(fn($s)=>['id'=>$s->id_sub_kategori,'nama'=>$s->nama_sub_kategori])
    ]));

    const kategoriSource = kategoriList.map(k => k.nama);
    const jenisSource = ['Pertumbuhan','Laju Implisit'];

    function getSubkategori(namaKategori){
        const k = kategoriList.find(k=>k.nama===namaKategori);
        return k ? k.sub.map(s=>s.nama) : [];
    }

    const spreadsheet = jspreadsheet(document.getElementById('spreadsheet'), {
        data: [['','','Pertumbuhan','{{ date("Y") }}','','']],
        columns: [
            { type: 'dropdown', title:'Kategori', width:120, source:kategoriSource },
            { type: 'dropdown', title:'Subkategori', width:120, source:[] },
            { type: 'dropdown', title:'Jenis Data', width:120, source:jenisSource },
            { type: 'numeric', title:'Tahun', width:80 },
            { type: 'numeric', title:'Nilai', width:100 },
            { type: 'text', title:'Fenomena', width:200 }
        ],
        minDimensions: [6,10],
        allowInsertRow:true,
        allowDeleteRow:true,
        rowResize:true,
        columnResize:true,
        onchange: function(instance, cell, x, y, value){
            if(x === 0){
                const subValues = getSubkategori(value);
                spreadsheet.setSource(1, subValues, y);
                spreadsheet.setValueFromCoords(1, y, '');
            }
        }
    });

    document.getElementById('add-row-btn').addEventListener('click', () => spreadsheet.insertRow());

    document.getElementById('fenomena-form').addEventListener('submit', function(e){
        const data = spreadsheet.getData();
        document.querySelectorAll('input[name^="kategori"]').forEach(i => i.remove());
        data.forEach(row => {
            ['kategori','subkategori','jenis_data','tahun','nilai','fenomena'].forEach((col,j)=>{
                const input = document.createElement('input');
                input.type='hidden';
                input.name=`${col}[]`;
                input.value = row[j] ?? '';
                this.appendChild(input);
            });
        });
    });
});
</script>
@endsection