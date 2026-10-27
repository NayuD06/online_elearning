<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EditorUploadController extends Controller
{
    /**
     * Receive an image from CKEditor's SimpleUploadAdapter.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['upload' => ['required', 'image', 'max:4096']]);

        $path = $request->file('upload')->store('editor/'.now()->format('Y/m'), 'media');

        return response()->json(['url' => Storage::disk('media')->url($path)]);
    }
}
