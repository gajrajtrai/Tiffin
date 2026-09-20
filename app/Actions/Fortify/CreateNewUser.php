<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered customer.
     * Public registration is customer-only — staff accounts are created by admins.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name'     => ['required', 'string', 'max:255'],
            'mobile'   => ['required', 'string', 'regex:/^\d{8}$/', 'unique:users,mobile'],
            'password' => $this->passwordRules(),
        ], [
            'mobile.required' => 'Please enter your mobile number.',
            'mobile.regex'    => 'Mobile must be 8 digits (e.g. 17123456).',
            'mobile.unique'   => 'This mobile number is already registered.',
        ])->validate();

        $user = User::create([
            'name'     => $input['name'],
            'email'    => null,
            'mobile'   => $input['mobile'],
            'password' => $input['password'],
            'status'   => 'active',
        ]);

        $user->assignRole('Customer');

        return $user;
    }
}