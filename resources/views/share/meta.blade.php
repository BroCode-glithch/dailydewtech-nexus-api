<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>

    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="noindex, follow">

    <!-- Open Graph -->
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:locale" content="en_US">

    @if (!empty($image))
        <meta property="og:image" content="{{ $image }}">
        <meta property="og:image:alt" content="{{ $title }}">
    @endif

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if (!empty($image))
        <meta name="twitter:image" content="{{ $image }}">
    @endif

    <!-- Canonical -->
    <link rel="canonical" href="{{ $frontendUrl }}">

    <!-- Redirect -->
    <meta http-equiv="refresh" content="0;url={{ $frontendUrl }}">
    <script>
        window.location.replace(@json($frontendUrl));
    </script>
</head>

<body>
    <p>Redirecting to <a href="{{ $frontendUrl }}">{{ $frontendUrl }}</a></p>
</body>

</html>