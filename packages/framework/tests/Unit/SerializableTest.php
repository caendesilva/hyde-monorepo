<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Support\Concerns\Serializable;
use Hyde\Testing\UnitTestCase;
use Hyde\Support\Contracts\SerializableContract;

/**
 * @covers \Hyde\Support\Concerns\Serializable
 */
class SerializableTest extends UnitTestCase
{
    public function test_json_serialize()
    {
        $class = new SerializableTestClass;

        $this->assertSame(['foo' => 'bar'], $class->toArray());
        $this->assertSame(['foo' => 'bar'], $class->jsonSerialize());

        $this->assertSame('{"foo":"bar"}', json_encode($class));
    }

    public function test_to_json()
    {
        $class = new SerializableTestClass;

        $this->assertSame('{"foo":"bar"}', $class->toJson());
    }
}

class SerializableTestClass implements SerializableContract
{
    use Serializable;

    public function toArray(): array
    {
        return ['foo' => 'bar'];
    }
}
