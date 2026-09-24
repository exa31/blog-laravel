<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        $name = $this->faker->name();
        $nameParts = array_values(array_filter(explode(' ', trim($name))));
        $firstInitial = $nameParts[0][0] ?? 'U';
        $secondInitial = isset($nameParts[1][0]) ? $nameParts[1][0] : '';
        $avatar = strtoupper($firstInitial.$secondInitial);

        return [
            'name' => $name,
            'email' => $this->faker->unique()->safeEmail(),
            'avatar' => $avatar,
            'auth_provider' => 'email',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password123'),
            'remember_token' => Str::random(10),
        ];
    }
}
