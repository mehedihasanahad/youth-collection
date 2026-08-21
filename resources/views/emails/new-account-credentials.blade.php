@extends('emails.layout')

@section('content')

<p class="greeting">Welcome to {{ $appName }}! 👋</p>

<p class="lead">
    Your order was placed successfully and we've created an account for you
    so you can track it anytime.
</p>

<div class="card" style="margin: 24px 0;">
    <div class="card-row">
        <span class="label">Phone (your login)</span>
        <span class="value">{{ $user->phone }}</span>
    </div>
    <div class="card-row">
        <span class="label">Password</span>
        <span class="value" style="font-family:monospace;letter-spacing:1px;">{{ $password }}</span>
    </div>
</div>

<p class="help-text" style="margin-bottom:20px;">
    We recommend changing your password after your first login.
</p>

<a href="{{ $loginUrl }}" class="btn">Login to Your Account</a>

<hr class="divider">

<p class="help-text">
    From your account you can track orders, download invoices, and manage your addresses.
</p>

@endsection
