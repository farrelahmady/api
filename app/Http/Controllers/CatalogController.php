<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\DB;
use App\Models\ManagementAccess\Catalog;

class CatalogController extends Controller
{
    public function index(Request $req)
    {
        try {
            $tailor_uuid = $req->input('tailor');
            $fabric = $req->input('fabric');
            $category = $req->input('category');
            $search = $req->input('search');
            $limit = $req->input('limit');

            $catalog = Catalog::with('item');

            if ($tailor_uuid) {
                $catalog = $catalog->where('user_tailor_id', $tailor_uuid);
            }

            if ($fabric) {
                $catalog = $catalog->where(DB::raw('lower(fabric)'), 'like', '%' . strtolower($fabric) . '%');
            }

            if ($category) {
                $catalog = $catalog->where(DB::raw('lower(category)'), 'like', '%' . strtolower($category) . '%');
            }

            if ($search) {
                $catalog = $catalog->where(DB::raw('lower(name)'), 'like', '%' . strtolower($search) . '%')
                    ->orWhere(DB::raw('lower(fabric)'), 'like', '%' . strtolower($search) . '%')
                    ->orWhere(DB::raw('lower(category)'), 'like', '%' . strtolower($search) . '%');
            }

            if ($limit) {
                $catalog = $catalog->limit($limit);
            }

            $catalog = $catalog->orderBy("name")->get();

            if ($catalog->count() <= 0) {
                return ResponseFormatter::error(['error' => "katalog tidak ditemukan"], 'katalog tidak ditemukan', 404);
            }

            return ResponseFormatter::success($catalog, $catalog->count() . " katalog berhasil didapatkan");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi kesalahan", 500);
        }
    }

    public function show(Request $req, $uuid)
    {
        try {
            $catalog = Catalog::with('item')->where('uuid', $uuid)->first();
            if (!$catalog) {
                return ResponseFormatter::error(null, 'katalog tidak ditemukan', 404);
            }
            return ResponseFormatter::success($catalog, "katalog berhasil didapatkan");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi kesalahan", 500);
        }
    }
}
