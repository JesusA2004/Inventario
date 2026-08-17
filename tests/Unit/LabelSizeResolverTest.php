<?php

use App\Services\LabelSizeResolver;

beforeEach(function () {
    $this->resolver = new LabelSizeResolver;
});

test('each preset resolves to its configured dimensions', function () {
    $dims = $this->resolver->resolve('small', 2);

    expect($dims['width'])->toBe(32.0)
        ->and($dims['height'])->toBe(26.0)
        ->and($dims['qr'])->toBe(16.0);
});

test('the large preset fits two columns but not three', function () {
    expect($this->resolver->fitsColumns(LabelSizeResolver::PRESETS['large']['width'], 2))->toBeTrue()
        ->and($this->resolver->fitsColumns(LabelSizeResolver::PRESETS['large']['width'], 3))->toBeFalse();
});

test('the small and medium presets fit both two and three columns', function () {
    foreach (['small', 'medium'] as $size) {
        $width = LabelSizeResolver::PRESETS[$size]['width'];

        expect($this->resolver->fitsColumns($width, 2))->toBeTrue()
            ->and($this->resolver->fitsColumns($width, 3))->toBeTrue();
    }
});

test('custom dimensions are clamped to the safe bounds', function () {
    $tooSmall = $this->resolver->resolve('custom', 2, 1.0, 1.0);
    expect($tooSmall['width'])->toBe(LabelSizeResolver::MIN_WIDTH_MM)
        ->and($tooSmall['height'])->toBe(LabelSizeResolver::MIN_HEIGHT_MM);

    $tooBig = $this->resolver->resolve('custom', 2, 500.0, 500.0);
    expect($tooBig['width'])->toBe(LabelSizeResolver::MAX_WIDTH_MM)
        ->and($tooBig['height'])->toBe(LabelSizeResolver::MAX_HEIGHT_MM);
});

test('the qr for a custom size never falls below the legible minimum', function () {
    $dims = $this->resolver->resolve('custom', 2, 25.0, 20.0);

    expect($dims['qr'])->toBeGreaterThanOrEqual(14.0);
});
