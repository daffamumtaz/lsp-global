<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfilePageContentResource;
use App\Models\SitePage;

class ProfilePageController extends Controller
{
    public function show(): ProfilePageContentResource
    {
        $page = SitePage::query()->where('kind', 'profile')->first() ?? new SitePage(['kind' => 'profile']);

        return new ProfilePageContentResource($page);
    }
}
