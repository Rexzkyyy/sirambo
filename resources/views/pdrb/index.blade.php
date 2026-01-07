<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai PDRB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 font-sans flex">

    @include('components.navbar')

    <div class="flex-1 max-w-4xl mx-auto py-10">

        <h2 class="text-3xl font-bold text-center mb-8 text-blue-700">Input Nilai PDRB</h2>

        <div class="bg-white shadow-md rounded-lg p-6 mb-8">
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Kategori & Sub Kategori</h3>

            <form method="POST" action="/pdrb/sub" class="space-y-6">
                @csrf

                <select id="kategori-select" name="id_kategori" 
                        class="border border-gray-300 rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($kategori as $k)
                        <option value="{{ $k->id_kategori }}">{{ $k->nama_kategori }}</option>
                    @endforeach
                </select>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <select name="id_tahun" 
                            class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($tahun as $t)
                            <option value="{{ $t->id_tahun }}">{{ $t->tahun }}</option>
                        @endforeach
                    </select>

                    <select name="id_periode" 
                            class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($periode as $p)
                            <option value="{{ $p->id_periode }}">{{ $p->nama_periode }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="subkategori-container" class="mt-4 space-y-3">
                    <p class="text-gray-500 italic">Pilih kategori untuk menampilkan sub kategori.</p>
                </div>

                <button type="submit" 
                        class="w-full bg-green-600 text-white font-semibold py-2 rounded hover:bg-green-700 transition mt-4">
                    Simpan Semua Nilai Sub Kategori
                </button>
            </form>
        </div>

    </div>

    <script>
    $(document).ready(function(){

        const subKategori = [
            @foreach($sub as $s)
                {id: {{ $s->id_sub_kategori }}, nama: @json($s->nama_sub_kategori), kategori: {{ $s->id_kategori }} },
            @endforeach
        ];

        $('#kategori-select').on('change', function(){
            const selectedKategori = $(this).val();
            const container = $('#subkategori-container');
            container.empty();

            if(selectedKategori){
                const filteredSub = subKategori.filter(s => s.kategori == selectedKategori);

                if(filteredSub.length === 0){
                    container.append('<p class="text-gray-500 italic">Tidak ada sub kategori untuk kategori ini.</p>');
                } else {
                    filteredSub.forEach(sub => {
                        const html = `
                            <div class="flex flex-col md:flex-row items-center md:space-x-4 space-y-2 md:space-y-0">
                                <label class="w-full md:w-1/2 font-medium">${sub.nama}</label>
                                <input type="number" min="0" step="0.01" name="nilai[${sub.id}]" 
                                       placeholder="Nilai ${sub.nama}" 
                                       class="w-full md:w-1/2 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        `;
                        container.append(html);
                    });
                }
            } else {
                container.append('<p class="text-gray-500 italic">Pilih kategori untuk menampilkan sub kategori.</p>');
            }
        });

    });
    </script>

</body>
</html>
