<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The first three lines are Laravel's own messages. The rest are the sign-in, registration and
    | account-recovery screens.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'login' => [
        'title' => 'Log in to your account',
        'description' => 'Enter your email and password below to log in',
        'forgot_password' => 'Forgot your password?',
        'remember' => 'Remember me',
        'no_account' => "Don't have an account?",
        'sign_up' => 'Sign up',
    ],

    'passkey' => [
        'sign_in' => 'Sign in with a passkey',
        'signing_in' => 'Authenticating...',
        'or_email' => 'Or continue with email',
        'confirm' => 'Confirm with passkey',
        'confirming' => 'Confirming...',
        'or_password' => 'Or confirm with password',
    ],

    'register' => [
        'title' => 'Create an account',
        'description' => 'Enter your details below to create your account',
        'submit' => 'Create account',
        'have_account' => 'Already have an account?',
        'consent' => 'I agree that what I contribute is published under the CC BY-SA 4.0 licence.',
        'consent_link' => 'Read the contribution terms',
        'consent_required' => 'To create an account, please agree to the contribution terms.',
    ],

    'forgot_password' => [
        'title' => 'Forgot password',
        'description' => 'Enter your email to receive a password reset link',
        'submit' => 'Email password reset link',
        'back_to_login' => 'Return to log in',
    ],

    'reset_password' => [
        'title' => 'Reset password',
        'description' => 'Please enter your new password below',
        'email' => 'Email',
        'submit' => 'Reset password',
    ],

    'verify_email' => [
        'title' => 'Email verification',
        'description' => 'Please verify your email address by clicking on the link we just emailed to you.',
        'link_sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend' => 'Resend verification email',
    ],

    'confirm_password' => [
        'title' => 'Confirm password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'submit' => 'Confirm password',
    ],

    'two_factor' => [
        'title' => 'Two-factor authentication',
        'code_title' => 'Authentication code',
        'code_description' => 'Enter the authentication code provided by your authenticator application.',
        'use_recovery' => 'log in using a recovery code',
        'recovery_title' => 'Recovery code',
        'recovery_description' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
        'use_code' => 'log in using an authentication code',
        'recovery_placeholder' => 'Enter recovery code',
        'or' => 'or you can',
    ],

];
