<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new user.';

    /**
     * Validation rules collection.
     *
     * @var Collection
     */
    protected Collection $rules;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->loadRules();
        $user = User::create([
            'name' => $this->getField('name'),
            'email' => $this->getField('email'),
            'password' => Hash::make($this->getField('password', true)),
            'is_admin' => $this->confirm('Is this user an admin?'),
        ]);

        $this->info("The user $user->name (id:$user->id) has been created successfully.");
    }

    /**
     * Get the value of the field by prompting to the user.
     *
     * @param string $field
     * @param bool $isSecret
     * @return mixed
     */
    protected function getField(string $field, bool $isSecret = false): mixed
    {
        $question = "Enter the $field of the user";
        $value = $isSecret ? $this->secret($question) : $this->ask($question);
        $validator = Validator::make([$field => $value], $this->rules->only($field)->toArray());
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return $this->getField($field, $isSecret);
        }

        return $value;
    }

    /**
     * Load the validation rules.
     *
     * @return void
     */
    public function loadRules(): void
    {
        $this->rules = collect([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|alpha_num',
        ]);
    }
}
