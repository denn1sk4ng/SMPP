<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>SMPP</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    @include('partials.header')

    @if (
        request()->routeIs('home') ||
        request()->routeIs('dashboard') ||
        request()->routeIs('datasets.create') ||
        request()->routeIs('datasets.preview') ||
        request()->routeIs('models.index') ||
        request()->routeIs('predictions.index') ||
        request()->routeIs('predictions.show') ||
        request()->routeIs('predictions.create') ||
        request()->routeIs('models.trainingResult')
    )
        @yield('content')
    @else
        <div class="container">
            @if(session('success'))
                <div style="margin-bottom:15px; color:green;">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div style="margin-bottom:15px; color:red;">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div style="margin-bottom:15px; color:red;">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    @endif
</body>
</html>