<?php

namespace App\Http\Controllers;

use App\Models\Volume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FileExplorerController extends Controller
{
    public function index(string $path)
    {
        $exploded = explode('/', $path);

        $volumeDisplayName = $exploded[0];

        array_splice($exploded, 0, 1);
        $path = array_reduce($exploded, function ($rest, $value) {
            return $rest . '/' . $value;
        }, '');

        array_splice($exploded, count($exploded) - 1, 1);
        $parent = array_reduce($exploded, function ($rest, $value) {
            return $rest . '/' . $value;
        }, $volumeDisplayName);
        if ($parent == $volumeDisplayName .  $path) $parent = null;


        $volume = Volume::select('*')->where('display_name', '=', $volumeDisplayName)->first();

        $disk = Storage::build([
            'driver' => 'local',
            'root' => $volume->absolute_path,
        ]);
        $files = array_map(function ($value) {
            return basename($value);
        }, $disk->files($path));
        $directiories = array_map(function ($value) {
            $exploded = explode('/', $value);
            return $exploded[count($exploded) - 1];
        }, $disk->directories($path));


        if (!$disk->directoryExists($path)) {
            abort(code: 404);
        }

        return Inertia::render(
            'FileExplorer',
            [
                'files' => $files,
                'directories' => $directiories,
                'parent' => $parent
            ]
        );
    }
}
