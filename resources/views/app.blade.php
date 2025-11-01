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
    
    @php
        $useVite = Vite::isRunningHot();
    @endphp
    
    @if($useVite)
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @else
        @php
            $manifestPath = public_path('build/manifest.json');
            $manifest = null;
            if (file_exists($manifestPath)) {
                $manifestContent = @file_get_contents($manifestPath);
                if ($manifestContent) {
                    $manifest = @json_decode($manifestContent, true);
                }
            }
        @endphp
        
        @if($manifest)
            @php
                $appJsEntry = $manifest['resources/js/app.js'] ?? null;
                $appScssEntry = $manifest['resources/sass/app.scss'] ?? null;
            @endphp
            
            @if($appJsEntry && isset($appJsEntry['css']))
                @foreach($appJsEntry['css'] as $cssFile)
                    <link rel="stylesheet" href="/build/{{ $cssFile }}">
                @endforeach
            @endif
            
            @if($appScssEntry && isset($appScssEntry['file']))
                <link rel="stylesheet" href="/build/{{ $appScssEntry['file'] }}">
            @endif
            
            @if($appJsEntry && isset($appJsEntry['file']))
                <script type="module" src="/build/{{ $appJsEntry['file'] }}"></script>
            @endif
        @endif
    @endif
</head>
<body>
    <div id="Application">
    </div>
</body>
</html>