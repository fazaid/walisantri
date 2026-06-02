<?php

namespace App\Http\Controllers;

use App\Models\Pesantren;
use App\Rules\SlugNotReserved;
use App\Rules\ValidTenantSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SlugCheckController extends Controller
{
    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $validator = Validator::make(
            ['slug' => $slug],
            ['slug' => ['required', 'string', new ValidTenantSlug, new SlugNotReserved]],
        );

        if ($validator->fails()) {
            return response()->json([
                'available' => false,
                'message'   => $validator->errors()->first('slug'),
            ]);
        }

        $taken = Pesantren::where('slug', $slug)->exists();

        return response()->json([
            'available' => ! $taken,
            'message'   => $taken ? 'Slug sudah digunakan.' : 'Slug tersedia.',
        ]);
    }
}
