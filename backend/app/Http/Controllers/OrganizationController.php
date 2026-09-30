<?php

namespace App\Http\Controllers;

use App\Models\OrganizationPosition;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    public function show(): JsonResponse
    {
        $nodes = array_map(static function (array $node): array {
            unset($node['depth']);

            return $node;
        }, OrganizationPosition::hierarchyRows(true));

        return response()->json(['data' => $nodes])->header('Cache-Control', 'no-store');
    }
}
