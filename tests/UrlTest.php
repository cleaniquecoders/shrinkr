<?php

use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
use CleaniqueCoders\Shrinkr\Models\Url;

beforeEach(function () {
    Url::truncate(); // Clean up the table before each test.
});

it('can create a shortened URL', function () {
    $data = [
        'original_url' => 'https://example.com/some-long-url',
    ];

    $shortUrl = (new CreateShortUrlAction)->execute($data);

    expect($shortUrl)->toBeInstanceOf(Url::class)
        ->and($shortUrl->original_url)->toBe($data['original_url'])
        ->and(strlen($shortUrl->shortened_url))->toBe(6);
});

it('can create a shortened URL with a custom slug', function () {
    $data = [
        'original_url' => 'https://example.com/another-long-url',
        'custom_slug' => 'mycustomslug',
    ];

    $shortUrl = (new CreateShortUrlAction)->execute($data);

    expect($shortUrl->custom_slug)->toBe('mycustomslug')
        ->and($shortUrl->shortened_url)->toBe('mycustomslug');
});

it('throws an exception if the slug already exists', function () {
    $data = [
        'original_url' => 'https://example.com/existing-url',
        'custom_slug' => 'existing-slug',
    ];

    (new CreateShortUrlAction)->execute($data);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('The slug already exists. Please try a different one.');

    (new CreateShortUrlAction)->execute($data); // This should throw an exception.
});
