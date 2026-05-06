@extends('emails.layout')

@section('title', 'Verify Your Email Address')

@section('content')
    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Verify Your Email Address</h1>
    @include('emails.partials.status-badge', ['status' => 'Action Required', 'color' => 'blue'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $user->name }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">Please click the button below to verify your email address and complete your registration.</p>

    @include('emails.partials.button', [
        'label' => 'Verify Email Address',
        'url' => $verificationUrl,
        'background' => '#2563eb',
    ])

    <p style="margin: 12px 0 0 0; font-size: 14px; line-height: 1.6; color: #6b7280;">If you did not create an account, no further action is required.</p>
@endsection

