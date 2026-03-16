<?php

namespace App\Http\Controllers\Api;

use App\Models\GameMaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameMakerController
{
    /**
     * メーカー名のオートコンプリート候補を返す
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function suggest(Request $request): JsonResponse
    {
        $keyword = trim($request->input('q', ''));

        if (mb_strlen($keyword) === 0) {
            return response()->json(['makers' => []]);
        }

        $makers = GameMaker::select(['id', 'name'])
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phonetic', 'like', "%{$keyword}%")
                    ->orWhereHas('synonyms', fn ($q) => $q->where('synonym', 'like', "%{$keyword}%"));
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json(['makers' => $makers]);
    }
}
