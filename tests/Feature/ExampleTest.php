<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertRedirect('/welcome');
});

it('welcome page returns a successful response', function () {
    $response = $this->get('/welcome');

    $response->assertStatus(200);
});
