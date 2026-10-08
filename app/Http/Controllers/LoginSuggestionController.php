<?php

namespace App\Http\Controllers;

use App\Support\LoginSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $name = trim((string) $request->query('name', ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            return response()->json(['suggestions' => []]);
        }

        return response()->json([
            'suggestions' => LoginSuggester::suggest($name, 5),
        ]);
    }
}
