<?php

declare(strict_types=1);

use Hyde\Facades\Features;
use Hyde\Enums\Feature;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->mockConfig();
    $this->setupKernel();
});

test('has feature method returns false when feature is disabled', function (Feature $feature) {
    $method = "has$feature->name";

    Config::set('hyde.features', []);
    \Hyde\Foundation\HydeKernel::setInstance(new \Hyde\Foundation\HydeKernel());

    $this->assertFalse(Features::$method(), "Method '$method' should return false when feature is not enabled");
})->with(Feature::cases())->covers(Hyde\Facades\Features::class);

test('has feature method returns true when feature is enabled', function (Feature $feature) {
    $method = "has$feature->name";

    Config::set('hyde.features', [$feature]);
    \Hyde\Foundation\HydeKernel::setInstance(new \Hyde\Foundation\HydeKernel());

    $this->assertTrue(Features::$method(), "Method '$method' should return true when feature is enabled");
})->with(
    collect(Feature::cases())->reject(fn (Feature $feature): bool => in_array($feature, [
        Feature::DocumentationSearch,
        Feature::Torchlight,
    ]))
)->covers(Hyde\Facades\Features::class);

test('all enum cases have a features accessor', function (Feature $feature) {
    $method = "has$feature->name";

    $this->assertTrue(method_exists(Features::class, $method), "Method '$method' should exist on Features facade");
})->with(Feature::cases())->covers(Hyde\Facades\Features::class);
