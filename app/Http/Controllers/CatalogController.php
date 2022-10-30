<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\UserTailor;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\ManagementAccess\Catalog;
use Illuminate\Support\Facades\Validator;
use App\Models\ManagementAccess\CatalogItem;

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

    public function store(Request $req)
    {
        try {
            //return $req->allFiles();
            //return $req->all();
            $user = auth()->user();
            switch ($user->currentAccessToken()->tokenable_type) {
                case UserCustomer::class:
                    return ResponseFormatter::error(null, "Anda tidak memiliki akses", 403);
                    break;

                case UserTailor::class:
                    $tailor = $user;
                    break;

                default:
                    $tailor = UserTailor::where('uuid', $req->input('tailor'))->first();

                    if (!$tailor) {
                        return ResponseFormatter::error(null, "Tailor tidak ditemukan", 404);
                    }
                    break;
            }
            $validation = Validator::make($req->all(), [
                'name' => 'required|string',
                'category' => 'required|in:LOWER,UPPER',
                'fabric' => 'required|string',
                'price' => 'required|numeric',
                'description' => 'required|string',
                'items' => 'required|array',
                "items.*" => "required|file|mimes:jpeg,png,jpg|max:2048"
            ]);


            if ($validation->fails()) {
                return ResponseFormatter::error($validation->errors(), "Terjadi kesalahan", 400);
            }

            $catalog = Catalog::create([
                'name' => $req->name,
                'category' => $req->category,
                'fabric' => $req->fabric,
                'price' => $req->price,
                'description' => $req->description,
                'user_tailor_id' => $tailor->uuid,
            ]);

            $items = $req->file("items");
            foreach ($items as $item) {
                $category = strtolower($req->category);
                $name = str_replace(' ', '-', strtolower($req->name));
                $extension = $item->getClientOriginalExtension();

                $fileName = $name . '-' . Str::random($length = 16) . "-" . now()->toDateString()  . "." . $extension;
                CatalogItem::create([
                    "catalog_id" => $catalog->id,
                    'picture' => asset('storage/' . $item->storePubliclyAs("images/tailor/catalog/$category/$name", $fileName, "public"))
                ]);
            }

            $data = $catalog->load('item');

            return ResponseFormatter::success($data, "katalog berhasil ditambahkan");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi kesalahan", 500);
        }
    }

    public function update(Request $req, $uuid)
    {
        try {
            $user = auth()->user();
            switch ($user->currentAccessToken()->tokenable_type) {
                case UserCustomer::class:
                    return ResponseFormatter::error(null, "Anda tidak memiliki akses", 403);
                    break;

                case UserTailor::class:
                    $tailor = $user;
                    break;

                default:
                    $tailor = UserTailor::where('uuid', $req->input('tailor'))->first();

                    if (!$tailor) {
                        return ResponseFormatter::error(null, "Tailor tidak ditemukan", 404);
                    }
                    break;
            }
            $validation = Validator::make(collect($req->all())->put("uuid", $uuid)->toArray(), [
                'name' => 'string',
                'category' => 'in:LOWER,UPPER',
                'fabric' => 'string',
                'price' => 'numeric',
                'description' => 'string',
                'items' => 'array',
                "items.*" => "file|mimes:jpeg,png,jpg|max:2048",
                "uuid" => "required|uuid|exists:catalogs,uuid"
            ]);

            if ($validation->fails()) {
                return ResponseFormatter::error($validation->errors(), "Terjadi kesalahan", 400);
            }

            $catalog = Catalog::where('uuid', $uuid)->first();

            if (!$catalog) {
                return ResponseFormatter::error(null, "katalog tidak ditemukan", 404);
            }

            $catalog->update([
                'name' => $req->name ?? $catalog->name,
                'category' => $req->category ?? $catalog->category,
                'fabric' => $req->fabric ?? $catalog->fabric,
                'price' => $req->price ?? $catalog->price,
                'description' => $req->description ?? $catalog->description,
            ]);

            $items = $req->file("items");
            if ($items) {
                $catalogItems = $catalog->item;

                if ($catalogItems) {
                    foreach ($catalogItems as $catalogItem) {
                        $catalogItem->delete();

                        $path = substr($catalogItem->picture, strpos($catalogItem->picture, 'images'));
                        return Storage::disk('public')->exists($path) ? Storage::disk('public')->delete($path) : "";
                    }
                }

                foreach ($items as $item) {
                    $category = strtolower($req->category);
                    $name = str_replace(' ', '-', strtolower($req->name));
                    $extension = $item->getClientOriginalExtension();

                    $fileName = $name . '-' . Str::random($length = 16) . "-" . now()->toDateString()  . "." . $extension;
                    CatalogItem::create([
                        "catalog_id" => $catalog->id,
                        'picture' => asset('storage/' . $item->storePubliclyAs("images/tailor/catalog/$category/$name", $fileName, "public"))
                    ]);
                }
            } else {
                foreach ($catalog->item as $item) {
                    $category = strtolower($catalog->category);
                    $name = str_replace(' ', '-', strtolower($catalog->name));
                    $extension = pathinfo($item->picture, PATHINFO_EXTENSION);

                    $fileName = $name . '-' . Str::random($length = 16) . "-" . now()->toDateString()  . "." . $extension;
                    //return Storage::disk('public')->allFiles("images/tailor/catalog/$category/$name");
                    //return substr($item->picture, strpos($item->picture, '/images'));
                    //return Storage::disk('public')->exists(substr($item->picture, strpos($item->picture, '/images')));
                    $moveFile = Storage::disk('public')->move(substr($item->picture, strpos($item->picture, 'images')), "images/tailor/catalog/$category/$name/$fileName");
                    if ($moveFile) {
                        $item->update([
                            'picture' => asset("storage/images/tailor/catalog/$category/$name/$fileName")
                        ]);
                    }
                }
            }

            $data = $catalog->load('item');

            return ResponseFormatter::success($data, "katalog berhasil diubah");
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage(), "Terjadi kesalahan", 500);
        }
    }
}
