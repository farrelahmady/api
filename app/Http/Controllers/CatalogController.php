<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\ManagementAccess\Catalog;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $req)
    {
        try {
            $tailor_uuid = $req->input('tailor');

            $catalog = Catalog::with('item');

            if ($tailor_uuid) {
                $catalog = $catalog->where('user_tailor_id', $tailor_uuid);
            }

            $catalog = $catalog->orderBy("name")->get();

            if ($catalog->count() <= 0) {
                return ResponseFormatter::error(null, 'Catalog not found', 404);
            }

            return ResponseFormatter::success($catalog, "Catalog retrieved successfully");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Something went wrong", 500);
        }
    }
}
