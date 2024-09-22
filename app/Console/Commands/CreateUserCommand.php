<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\form;
use function Laravel\Prompts\info;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user with an email and a password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $response = form()
            ->text(
                'Enter name',
                name: 'name',
                required: true,
                validate: ['name' => ['string', 'min:3', 'max:255']]
            )
            ->text(
                'Enter email',
                name: 'email',
                required: true,
                validate: ['email' => ['email', 'unique:users', 'min:4', 'max:255']]
            )
            ->password(
                'Enter password',
                name: 'password',
                required: true,
                validate: ['password' => Password::defaults()]
            )->submit();

        $user = User::create([
            'name' => $response['name'],
            'email' => $response['email'],
            'password' => Hash::make($response['password']),
        ]);

        info('Created new user with id ' . $user->id);

        return 0;
    }
}
