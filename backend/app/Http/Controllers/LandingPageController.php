<?php

namespace App\Http\Controllers;

use App\Http\Resources\LandingPageContentResource;
use App\Models\SitePage;

class LandingPageController extends Controller
{
    public function show(): LandingPageContentResource
    {
        $page = SitePage::query()->where('kind', 'landing')->first() ?? new SitePage(['kind' => 'landing']);

        return new LandingPageContentResource($page);
    }
}
