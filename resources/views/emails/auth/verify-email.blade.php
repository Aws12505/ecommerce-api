{{-- FILE: resources/views/emails/auth/verify-email.blade.php --}}
@extends('emails.layouts.base')

@section('title', 'Verify Your Email - ' . config('app.name'))

@section('header-title', 'Email Verification')
@section('header-subtitle')
    <p style="margin: 0; font-size: 16px; opacity: 0.9;">Please verify your email address to continue</p>
@endsection

@section('content')
    <h2>Hello {{ $user->name }}!</h2>
    
    <p>Welcome to {{ $appName }}! We're excited to have you on board.</p>
    
    <p>To complete your registration and start using your account, please verify your email address by clicking the button below:</p>
    
    <div style="text-align: center;">
        <a href="{{ $verificationUrl }}" class="btn">Verify Email Address</a>
    </div>
    
    <div class="highlight-box">
        <p style="margin: 0;"><strong>Important:</strong> This verification link will expire in 60 minutes for security reasons.</p>
    </div>
    
    <p>If the button above doesn't work, you can copy and paste the following link into your browser:</p>
    
    <p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace;">
        {{ $verificationUrl }}
    </p>
    
    <p>If you didn't create an account with {{ $appName }}, please ignore this email.</p>
@endsection

@section('footer-content')
    <p class="small-text">
        Need help? Contact our support team at 
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
    </p>
@endsection
