<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Framework\Factories\HydePageDataFactory;
use Hyde\Pages\InMemoryPage;
use Hyde\Testing\UnitTestCase;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Config;

/**
 * @covers \Hyde\Framework\Factories\HydePageDataFactory
 */
class HydePageDataFactoryTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::needsKernel();

        app()->bind('config', function () {
            return new Repository([
                'hyde' => [
                    //
                ],
            ]);
        });

        Config::swap(app('config'));
    }

    protected function factory(array $data = []): HydePageDataFactory
    {
        return new HydePageDataFactory((new InMemoryPage('foo', $data))->toCoreDataObject());
    }
}
