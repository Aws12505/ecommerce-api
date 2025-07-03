{{-- FILE: resources/views/emails/auth/reset-password.blade.php --}}
@extends('emails.layouts.base')

@section('title', 'Reset Your Password - ' . config('app.name'))

@section('header-title', 'Password Reset')
@section('header-subtitle')
    <p style="margin: 0; font-size: 16px; opacity: 0.9;">Reset your account password</p>
@endsection

@section('content')
    <h2>Hello {{ $user->name }}!</h2>
    
    <p>We received a request to reset the password for your {{ $appName }} account.</p>
    
    <p>Click the button below to reset your password:</p>
    
    <div style="text-align: center;">
        <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
    </div>
    
    <div class="highlight-box">
        <p style="margin: 0;"><strong>Security Notice:</strong> This password reset link will expire in {{ $expireMinutes }} minutes.</p>
    </div>
    
    <p>If the button above doesn't work, you can copy and paste the following link into your browser:</p>
    
    <p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace;">
        {{ $resetUrl }}
    </p>
    
    <p><strong>If you didn't request a password reset, please ignore this email.</strong> Your password will remain unchanged.</p>
    
    <p>For security reasons, we recommend:</p>
    <ul style="margin-left: 20px;">
        <li>Using a strong, unique password</li>
        <li>Enabling two-factor authentication if available</li>
        <li>Not sharing your login credentials</li>
    </ul>
@endsection

@section('footer-content')
    <p class="small-text">
        If you're having trouble with password reset, contact our support team at 
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
    </p>
@endsection
