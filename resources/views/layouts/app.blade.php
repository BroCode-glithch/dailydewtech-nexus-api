<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>
        {{ config('app.name') }} - @yield('title', 'Welcome')
    </title>
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="container mx-auto py-8">
        @yield('content')
    </div>
</body>
</html>