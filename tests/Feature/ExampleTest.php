<?php

test('the root redirects to API documentation', function () {
    $this->get('/')->assertRedirect('/docs/api');
});

test('the public status page is no longer available', function () {
    $this->get('/status')->assertNotFound();
});
