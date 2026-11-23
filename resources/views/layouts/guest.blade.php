<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · ClientFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="grid min-h-screen lg:grid-cols-2">
    <section class="hidden bg-navy-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="flex items-center gap-3">
            <div class="grid size-11 place-items-center rounded-xl bg-blue-600 font-bold">CF</div>
            <span class="text-xl font-semibold">ClientFlow</span>
        </div>
        <div>
            <p class="max-w-lg text-4xl font-semibold leading-tight">{{ __('app.auth_subtitle') }}</p>
            <p class="mt-5 text-slate-400">Clients · Projects · Invoices · Insights</p>
        </div>
        <p class="text-sm text-slate-500">© {{ date('Y') }} ClientFlow</p>
    </section>
    <section class="flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="grid size-10 place-items-center rounded-xl bg-blue-600 font-bold text-white">CF</div>
                <span class="text-xl font-semibold">ClientFlow</span>
            </div>
            @if($errors->any())
                <div class="mb-5 rounded-xl bg-rose-50 p-4 text-sm text-rose-700">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </section>
</main>
</body>
</html>
