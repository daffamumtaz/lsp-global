<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use Illuminate\Http\JsonResponse;

class InformationPageController extends Controller
{
    public function show(): JsonResponse
    {
        $page = SitePage::query()->where('kind', 'information')->first() ?? new SitePage(['kind' => 'information']);

        return response()->json(['data' => $page->informationContent()]);
    }
}
