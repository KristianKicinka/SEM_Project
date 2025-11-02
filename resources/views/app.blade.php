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
    
    @if(Vite::isRunningHot())
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @else
        @php
            $manifestPath = public_path('build/manifest.json');
            if (file_exists($manifestPath)) {
                $manifestContent = file_get_contents($manifestPath);
                $manifest = json_decode($manifestContent, true);
                
                if ($manifest && isset($manifest['resources/js/app.js'])) {
                    $appJs = $manifest['resources/js/app.js'];
                    
                    // CSS from app.js
                    if (!empty($appJs['css']) && is_array($appJs['css'])) {
                        foreach ($appJs['css'] as $css) {
                            echo '<link rel="stylesheet" href="' . asset('build/' . $css) . '">' . "\n    ";
                        }
                    }
                    
                    // app.scss CSS
                    if (isset($manifest['resources/sass/app.scss']['file'])) {
                        echo '<link rel="stylesheet" href="' . asset('build/' . $manifest['resources/sass/app.scss']['file']) . '">' . "\n    ";
                    }
                    
                    // app.js
                    if (!empty($appJs['file'])) {
                        echo '<script type="module" src="' . asset('build/' . $appJs['file']) . '"></script>' . "\n";
                    }
                }
            }
        @endphp
    @endif
</head>
<body>
    <div id="Application">
    </div>
</body>
</html>