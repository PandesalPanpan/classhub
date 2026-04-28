@extends('emails.layout')

@section('title', 'Welcome to ClassHub')

@section('content')
    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Welcome to ClassHub</h1>
    @include('emails.partials.status-badge', ['status' => 'New Account', 'color' => 'blue'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $user->name }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">Welcome to the ClassHub scheduling system for PUP. Your account is ready.</p>

    @include('emails.partials.callout', [
        'title' => 'Quick start',
        'content' => '<ol style="margin: 0; padding-left: 20px;">'
            .'<li>Complete your profile details.</li>'
            .'<li>Review room availability and policies.</li>'
            .'<li>Submit your first schedule request.</li>'
            .'<li>Wait for admin approval and key instructions.</li>'
            .'</ol>',
        'accent' => '#2563eb',
        'background' => '#eff6ff',
    ])

    @include('emails.partials.callout', [
        'title' => 'Useful links',
        'content' => '<a href="'.e(config('app.url')).'" style="color: #800000; text-decoration: none; font-weight: 600;">Public Calendar</a><br>'
            .'<a href="'.e(route('policy')).'" style="color: #800000; text-decoration: none; font-weight: 600;">Terms and Conditions</a><br>'
            .'<a href="'.e(config('app.url').'/portal/request-schedule').'" style="color: #800000; text-decoration: none; font-weight: 600;">Request a Schedule</a>',
        'accent' => '#6b7280',
        'background' => '#f9fafb',
    ])

    @include('emails.partials.button', [
        'label' => 'Request Your First Schedule',
        'url' => config('app.url').'/portal/request-schedule',
    ])
@endsection
