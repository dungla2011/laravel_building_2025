<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;

class SimpleMediaController extends Controller
{
    public function index()
    {
        $media = Media::all();
        return response()->json(['data' => $media]);
    }

    public function show($id)
    {
        $media = Media::findOrFail($id);
        return response()->json(['data' => $media]);
    }

    public function store(Request $request)
    {
        $media = Media::create($request->all());
        return response()->json(['data' => $media], 201);
    }

    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);
        $media->update($request->all());
        return response()->json(['data' => $media]);
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        $media->delete();
        return response()->json(['message' => 'Media deleted successfully']);
    }
}