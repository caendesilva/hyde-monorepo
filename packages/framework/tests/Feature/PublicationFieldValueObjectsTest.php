<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\PublicationFieldValue;
use Hyde\Framework\Features\Publications\PublicationFieldTypes;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFieldValues\PublicationFieldValue
 */
class PublicationFieldValueObjectsTest extends TestCase
{
    public function testConstruct()
    {
        $value = new TestValue('foo');
        $this->assertSame('foo', $value->getValue());
    }

    public function testGetValue()
    {
        $value = new TestValue('foo');
        $this->assertSame('foo', $value->getValue());
    }

    public function testGetType()
    {
        $this->assertSame(TestValue::TYPE, TestValue::getType());
        $this->assertSame(PublicationFieldTypes::String, TestValue::getType());
    }

    public function testParseInput()
    {
        $value = TestValue::parseInput('foo');
        $this->assertSame('foo', $value);
    }

    public function testToYamlType()
    {
        $value = TestValue::toYamlType('foo');
        $this->assertSame('foo', $value);
    }
}

class TestValue extends PublicationFieldValue
{
    public const TYPE = PublicationFieldTypes::String;

    public static function parseInput(string $input): string
    {
        return $input;
    }

    public static function toYamlType(mixed $input): string
    {
        return $input;
    }
}
