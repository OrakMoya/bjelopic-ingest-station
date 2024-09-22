<?php

use App\Http\Controllers\FileExplorerController;
use function Pest\Laravel\get;

covers(FileExplorerController::class);


test('example', function () {
    $response = get('/');

    $response->assertStatus(200);
});
