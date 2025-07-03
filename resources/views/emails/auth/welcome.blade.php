{{-- FILE: resources/views/emails/auth/welcome.blade.php --}}
@extends('emails.layouts.base')

@section('title', 'Welcome to ' . config('app.name'))

@section('header-title', 'Welcome!')
@section('header-subtitle')
    <p style="margin: 0; font-size: 16px; opacity: 0.9;">Your account has been successfully verified</p>
@endsection

@section('content')
    <h2>Welcome to {{ $appName }}, {{ $user->name }}! 🎉</h2>
    
    <p>Congratulations! Your email has been successfully verified and your account is now active.</p>
    
    <p>You can now access all features of {{ $appName }}. Here's what you can do next:</p>
    
    <div class="highlight-box">
        <h3 style="margin-top: 0; color: #495057;">Getting Started:</h3>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Complete your profile setup</li>
            <li>Explore our product catalog</li>
            <li>Set up your preferences</li>
            <li>Join our community</li>
        </ul>
    </div>
    
    <div style="text-align: center;">
        <a href="{{ $dashboardUrl }}" class="btn">Go to Dashboard</a>
    </div>
    
    <p>If you have any questions or need assistance getting started, our support team is here to help!</p>
    
    <p>Thank you for choosing {{ $appName }}. We're excited to have you as part of our community!</p>
@endsection

@section('footer-content')
    <p class="small-text">
        Questions? We're here to help! Contact us at 
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
    </p>
    
    <div class="social-links">
        {{-- Add your social media links here --}}
        {{-- <a href="#" title="Facebook">📘</a>
        <a href="#" title="Twitter">🐦</a>
        <a href="#" title="Instagram">📷</a> --}}
    </div>
@endsection
