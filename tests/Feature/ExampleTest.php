<?php


/**
 * Ficheiro PHP: ExampleTest.php
 * PHP file: ExampleTest.php
 */
test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
