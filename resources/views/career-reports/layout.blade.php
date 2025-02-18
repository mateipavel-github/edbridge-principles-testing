<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>@yield('title')</title>
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <meta name="description" content="" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="icon" href="favicon.png">
    </head>
    <body class="text-base">
        <div class="w-full flex flex-col justify-center items-center mt-8 md:mt-24 px-4">

            @yield('content')
        </div>
    </body>
</html>

