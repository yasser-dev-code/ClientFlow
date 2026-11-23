<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'ClientFlow') · ClientFlow</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body x-data="{ sidebar: false }">
<div class="min-h-screen lg:flex">
    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebar=false"></div>
    <aside :class="{ '!translate-x-0': sidebar }" class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-navy-950 text-white transition-transform lg:static lg:translate-x-0">
        <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6"><div class="grid size-10 place-items-center rounded-xl bg-blue-600 font-bold">CF</div><div><div class="font-semibold">ClientFlow</div><div class="text-xs text-slate-400">Business workspace</div></div></div>
        <nav class="flex-1 space-y-1 overflow-y-auto p-4">
            @foreach(['dashboard'=>'dashboard','clients'=>'clients.index','projects'=>'projects.index','tasks'=>'tasks.index','invoices'=>'invoices.index','payments'=>'payments.index'] as $key=>$route)
                <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs($route, str_replace('.index', '.*', $route)) ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"><span class="size-2 rounded-full bg-current opacity-70"></span>{{ __('app.nav.'.$key) }}</a>
            @endforeach
            @if(auth()->user()->isAdmin())<a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('users.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"><span class="size-2 rounded-full bg-current opacity-70"></span>{{ __('app.nav.users') }}</a>@endif
        </nav>
        <div class="border-t border-white/10 p-4"><div class="mb-3 flex items-center gap-3 px-2"><div class="grid size-10 place-items-center rounded-full bg-slate-700 text-sm font-semibold">{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</div><div class="min-w-0"><div class="truncate text-sm font-medium">{{ auth()->user()->name }}</div><div class="text-xs text-slate-400">{{ __('app.roles.'.auth()->user()->role->value) }}</div></div></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-xl border border-white/10 px-3 py-2 text-left text-sm text-slate-300 hover:bg-white/10">{{ __('app.logout') }}</button></form></div>
    </aside>
    <main class="min-w-0 flex-1">
        <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur md:px-8"><button class="rounded-lg p-2 text-slate-600 lg:hidden" @click="sidebar=true" aria-label="Menu">☰</button><div class="hidden text-sm text-slate-500 sm:block">{{ now()->translatedFormat('l d F Y') }}</div><div class="flex items-center gap-2"><form method="POST" action="{{ route('locale.update', app()->getLocale() === 'fr' ? 'en' : 'fr') }}">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold uppercase">{{ app()->getLocale() === 'fr' ? 'EN' : 'FR' }}</button></form></div></header>
        <div class="p-4 md:p-8">
            @if(session('success'))<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
            @if(session('status'))<div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </div>
    </main>
</div>
</body></html>
