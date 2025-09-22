<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'create:super-admin';
    protected $description = 'Create a super admin user';

    public function handle()
    {
        // Check if super-admin role exists
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if (!$superAdminRole) {
            $this->error('Super admin role not found');
            return;
        }

        // Create or update user
        $user = User::updateOrCreate(
            ['email' => 'super.admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );

        // Attach role
        if (!$user->roles->contains('name', 'super-admin')) {
            $user->roles()->attach($superAdminRole->id);
        }

        $this->info('Super admin created: super.admin@example.com / password');
    }
}