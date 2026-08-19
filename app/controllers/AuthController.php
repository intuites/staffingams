<?php

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        render('auth/login', ['title' => 'Sign in'], 'auth');
    }

    public function login(): void
    {
        Csrf::verify();
        if (!Turnstile::verify()) {
            flash('error', 'Human verification failed. Please try again.');
            redirect('/login');
        }
        if (Auth::tooManyAttempts()) {
            flash('error', 'Too many failed attempts. Please wait 15 minutes and try again.');
            redirect('/login');
        }
        $email = post('email') ?? '';
        $password = $_POST['password'] ?? '';
        if (Auth::attempt($email, $password)) {
            redirect('/');
        }
        flash('error', 'Invalid email or password.');
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
