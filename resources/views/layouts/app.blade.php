<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | Insurance</title>
	@routes
    @vite(['resources/js/app.js', 'resources/css/app.css'])

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body>
    <main>
        @yield('content')
    </main>
</body>

</html>
