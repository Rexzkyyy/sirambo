<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('assets/css/jsuites.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('assets/css/jspreadsheet.min.css') }}" />

<script src="{{ asset('assets/js/jsuites.min.js') }}"></script>
<script src="{{ asset('assets/js/jspreadsheet.min.js') }}"></script>
</head>
<body class="bg-gray-100 p-3">

    <header class="mb-3">
        <h1 class="text-2xl font-bold">@yield('title', 'Dashboard')</h1>
    </header>

    <main>
        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-2 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
