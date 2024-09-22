<?php

use App\Http\Controllers\VolumeController;
use App\Models\Volume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

covers(VolumeController::class);


beforeEach(function () {
    $this->display_name = Faker\Factory::create()->words(random_int(1, 10), true);
    $this->tmp_dir = TemporaryDirectory::make()->path();
});

afterEach(function () {
    rmdir($this->tmp_dir);
});




it('returns a list of volumes', function () {
    $response = get('/settings/volumes');
    $response->assertStatus(200)
        ->assertJsonStructure(['volumes']);
});


it('adds a new volume', function () {
    $display_name = $this->display_name;
    $absolute_path = $this->tmp_dir;

    $response = postJson(
        '/settings/volumes',
        [
            'display_name' => $display_name,
            'absolute_path' => $absolute_path,
        ]
    );

    $response->assertStatus(200);

    assertDatabaseHas('volumes', [
        'id' => $response->json()['id'],
        'display_name' => $display_name,
        'absolute_path' => $absolute_path,
    ]);
});

it('prevents adding volumes with duplicate display names', function () {
    $display_name = $this->display_name;
    $absolute_path = $this->tmp_dir;

    $response = postJson(
        '/settings/volumes',
        [
            'display_name' => $display_name,
            'absolute_path' => $absolute_path,
        ]
    );
    $response->assertStatus(200);


    $response = postJson(
        '/settings/volumes',
        [
            'display_name' => $display_name,
            'absolute_path' => $absolute_path . 'newuniquepath',
        ]
    );
    $response->assertInvalid('display_name')
        ->assertJsonStructure(['message', 'errors'])
        ->assertStatus(422);
});

it('prevents adding volumes with duplicate paths', function () {
    $display_name = $this->display_name;
    $absolute_path = $this->tmp_dir;

    $response = postJson(
        '/settings/volumes',
        [
            'display_name' => $display_name,
            'absolute_path' => $absolute_path,
        ]
    );
    $response->assertStatus(200);

    $response = postJson(
        '/settings/volumes',
        [
            'display_name' => $display_name . 'newuniquename',
            'absolute_path' => $absolute_path,
        ]
    );
    $response->assertInvalid('absolute_path')
        ->assertJsonStructure(['message', 'errors'])
        ->assertStatus(422);
});

it('prevents adding volumes with non-existent path', function () {
    $display_name = $this->display_name;
    $absolute_path = $this->tmp_dir;

    $response = postJson(
        '/settings/volumes',
        [
            'display_name' => $display_name,
            'absolute_path' => $absolute_path . 'nonexistent',
        ]
    );
    $response->assertInvalid('absolute_path')
        ->assertJsonStructure(['message', 'errors'])
        ->assertStatus(422);
});




it('can delete a volume', function () {
    $display_name = $this->display_name;
    $absolute_path = $this->tmp_dir;

    $response = postJson(
        '/settings/volumes',
        [
            'display_name' => $display_name,
            'absolute_path' => $absolute_path,
        ]
    );
    $response->assertStatus(200);
    $id = $response->json('id');

    $response = deleteJson('/settings/volumes/' . $id);
    $response->assertStatus(200);

    assertDatabaseMissing('volumes', [
        'id' => $id,
        'display_name' => $display_name,
        'absolute_path' => $absolute_path,
    ]);
});


it('warns of a non-existent volume', function () {
    $response = deleteJson('/settings/volumes/1');
    $response->assertStatus(404)
        ->assertJsonStructure(['message']);
});
