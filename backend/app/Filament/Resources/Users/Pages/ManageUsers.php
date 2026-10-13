<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Gate;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah akun')->using(function (array $data): User {
            Gate::authorize('manage-admin');
            $user = new User;
            $user->fill($data)->forceFill(['is_admin' => (bool) $data['is_admin']])->save();

            return $user;
        })];
    }
}
