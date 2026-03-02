<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>
        {{ config('app.name') }} - @yield('title', 'Welcome')
    </title>

    <!-- If you're using Tailwind via Vite in Laravel, keep your normal includes -->
    <!-- @vite(['resources/css/app.css', 'resources/js/app.js']) -->
</head>

<body class="min-h-screen text-slate-900 antialiased">
    <!-- Background: colorful gradient + soft blobs -->
    <div class="relative min-h-screen overflow-hidden">
        <!-- Base gradient -->
        <div class="absolute inset-0 bg-gradient-to-br from-fuchsia-500 via-indigo-500 to-cyan-400"></div>

        <!-- Decorative blobs -->
        <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-white/20 blur-3xl"></div>
        <div class="absolute top-40 -right-24 h-96 w-96 rounded-full bg-black/10 blur-3xl"></div>
        <div class="absolute -bottom-24 left-1/3 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>

        <!-- Subtle pattern overlay -->
        <div class="absolute inset-0 opacity-[0.08]"
             style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 18px 18px;">
        </div>

        <!-- Content wrapper -->
        <div class="relative">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <!-- Top bar / brand (purely visual, safe) -->
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-white/20 ring-1 ring-white/30 backdrop-blur-md flex items-center justify-center">
                            <span class="text-white font-black tracking-tight">
                                {{ strtoupper(substr(config('app.name'), 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <div class="text-white font-semibold leading-tight">
                                {{ config('app.name') }}
                            </div>
                            <div class="text-white/70 text-sm leading-tight">
                                @yield('title', 'Welcome')
                            </div>
                        </div>
                    </div>

                    <!-- Optional right-side pill (visual only) -->
                    <div class="hidden sm:inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm text-white/90 ring-1 ring-white/25 backdrop-blur-md">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-300"></span>
                        <span>Live</span>
                    </div>
                </div>

                <!-- Main glass card -->
                <div class="mx-auto max-w-5xl">
                    <div class="rounded-3xl bg-white/80 ring-1 ring-white/40 shadow-2xl backdrop-blur-xl">
                        <!-- Card accent bar -->
                        <div class="h-2 w-full rounded-t-3xl bg-gradient-to-r from-fuchsia-500 via-indigo-500 to-cyan-400"></div>

                        <div class="p-6 sm:p-10">
                            @yield('content')
                        </div>
                    </div>

                    <!-- Footer hint (visual only) -->
                    <div class="mt-6 text-center text-white/80 text-sm">
                        <span class="font-medium text-white">{{ config('app.name') }}</span>
                        <span class="text-white/60"> • </span>
                        <span class="text-white/70">Designed with color & clarity</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>