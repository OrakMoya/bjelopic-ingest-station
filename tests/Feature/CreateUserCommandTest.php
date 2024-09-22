<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('creates a user with valid input', function () {
    artisan('app:create-user')
        ->expectsQuestion('Enter name', 'example')
        ->expectsQuestion('Enter email', 'example@bjelopic.com')
        ->expectsQuestion('Enter password', 'examplepassword123')
        ->expectsOutputToContain('Created new user with id')
        ->assertExitCode(0);

    $user = User::select('*')
        ->where('email', 'example@bjelopic.com')
        ->first();

    expect($user->name)->toBe('example');
    expect($user->email)->toBe('example@bjelopic.com');
    expect(Hash::check('examplepassword123', $user->password))->toBe(true);
});

it('warns of an invalid username', function () {
    artisan('app:create-user')
        ->expectsQuestion('Enter name', 'ex')
        ->expectsOutputToContain('The name field must');
});

it('warns of an invalid email', function () {
    artisan('app:create-user')
        ->expectsQuestion('Enter name', 'example')
        ->expectsQuestion('Enter email', 'example')
        ->expectsOutputToContain('valid email address');
});

it('warns of an invalid password', function () {
    artisan('app:create-user')
        ->expectsQuestion('Enter name', 'example')
        ->expectsQuestion('Enter email', 'example@bjelopic.com')
        ->expectsQuestion('Enter password', '123')
        ->expectsOutputToContain('password field');
});


it('warns of required name input', function () {
    artisan('app:create-user')
        ->expectsQuestion('Enter name', '')
        ->expectsOutputToContain('Required');
});
it('warns of required email input', function () {
    artisan('app:create-user')
        ->expectsQuestion('Enter name', 'example')
        ->expectsQuestion('Enter email', '')
        ->expectsOutputToContain('Required');
});
it('warns of required password input', function () {
    artisan('app:create-user')
        ->expectsQuestion('Enter name', 'example')
        ->expectsQuestion('Enter email', 'example@bjelopic.com')
        ->expectsQuestion('Enter password', '')
        ->expectsOutputToContain('Required');
});


it('warns of too long name', function () {
    artisan('app:create-user')
        ->expectsQuestion('Enter name', str_repeat('a', 260))
        ->expectsOutputToContain('The name field must not be greater than');
});
it('warns of too long email', function () {
    artisan('app:create-user')
        ->expectsQuestion('Enter name', 'example')
        ->expectsQuestion('Enter email', 'aaaaaaaaaa@' . str_repeat('g', 260) . '.com')
        ->expectsOutputToContain('The email field must ');
});
