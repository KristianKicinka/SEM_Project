<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    


    <!-- Scripts -->
    <script src="https://kit.fontawesome.com/a2ea7766e8.js" crossorigin="anonymous"></script>
    
    @if(app()->environment('local'))
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @else
        @php
            $manifestPath = public_path('build/manifest.json');
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                $appJs = $manifest['resources/js/app.js'] ?? null;
                $appScss = $manifest['resources/sass/app.scss'] ?? null;
            }
        @endphp
        @if(isset($appJs['css']))
            @foreach($appJs['css'] as $css)
                <link rel="stylesheet" href="{{ asset('build/' . $css) }}">
            @endforeach
        @endif
        @if(isset($appScss['file']))
            <link rel="stylesheet" href="{{ asset('build/' . $appScss['file']) }}">
        @endif
        @if(isset($appJs['file']))
            <script type="module" src="{{ asset('build/' . $appJs['file']) }}"></script>
        @endif
    @endif
</head>
<body>
    <div id="Application">
    </div>
</body>
</html>