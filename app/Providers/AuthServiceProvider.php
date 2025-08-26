<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('can-manage-resource', function (User $user, int $ownerId): bool {
            if ($user->id !== $ownerId) {
                abort(404, 'Resource not found');
            }

            return true;
        });
    }
}
