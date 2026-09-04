<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('API siap menerima request.')
        ->assertSee('Koneksi database');
});

test('the application status can be requested as json', function () {
    $this->getJson('/status')
        ->assertOk()
        ->assertJsonPath('status', 'operational')
        ->assertJsonPath('checks.application', 'online')
        ->assertJsonPath('checks.database', 'online');
});
