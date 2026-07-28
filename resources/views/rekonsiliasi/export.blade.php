<table>
    <thead>
        <tr>
            <th>Kategori</th>
            <th>Provinsi</th>
            <th>Total Kab/Kota</th>
            <th>Selisih</th>
            <th>Persen</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rekonsiliasi as $row)
            <tr>
                <td>{{ $row['kategori'] }}</td>
                <td>{{ $row['provinsi'] }}</td>
                <td>{{ $row['total'] }}</td>
                <td>{{ $row['selisih'] }}</td>
                <td>{{ number_format($row['persen'],2) }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>
