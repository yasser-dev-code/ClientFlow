@extends('layouts.guest')
@section('title', __('app.login'))
@section('content')
<h1 class="text-3xl font-semibold text-slate-950">{{ __('app.welcome_back') }}</h1><p class="mt-2 text-slate-500">{{ __('app.auth_subtitle') }}</p>
@if(session('status'))<div class="mt-5 rounded-xl bg-blue-50 p-4 text-sm text-blue-700">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">@csrf
<div><label class="form-label" for="email">{{ __('app.email') }}</label><input class="form-input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></div>
<div><div class="flex justify-between"><label class="form-label" for="password">{{ __('app.password') }}</label><a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600">{{ __('app.forgot_password') }}</a></div><input class="form-input" id="password" type="password" name="password" required autocomplete="current-password"></div>
<label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300">{{ __('app.remember_me') }}</label>
<button class="btn-primary w-full">{{ __('app.login') }}</button><p class="text-center text-sm text-slate-500"><a class="font-semibold text-blue-600" href="{{ route('register') }}">{{ __('app.register') }}</a></p>
</form>@endsection
