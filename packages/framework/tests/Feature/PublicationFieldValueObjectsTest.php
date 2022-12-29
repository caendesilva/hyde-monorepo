<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use DateTime;
use Exception;
use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\DatetimeField;
use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\PublicationFieldValue;
use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\StringField;
use Hyde\Framework\Features\Publications\PublicationFieldTypes;
use Hyde\Testing\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFieldValues\PublicationFieldValue
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFieldValues\StringField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFieldValues\DatetimeField
 */
class PublicationFieldValueObjectsTest extends TestCase
{
    // Base class tests

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

    // StringField tests

    public function testStringFieldConstruct()
    {
        $value = new StringField('foo');
        $this->assertSame('foo', $value->getValue());
    }

    public function testStringFieldGetValue()
    {
        $value = new StringField('foo');
        $this->assertSame('foo', $value->getValue());
    }

    public function testStringFieldGetType()
    {
        $this->assertSame(StringField::TYPE, StringField::getType());
        $this->assertSame(PublicationFieldTypes::String, StringField::getType());
    }

    public function testStringFieldToYaml()
    {
        $value = new StringField('foo');
        $this->assertSame('foo', Yaml::dump($value->getValue()));
    }

    // DatetimeField tests

    public function testDatetimeFieldConstruct()
    {
        $value = new DatetimeField('2023-01-01');
        $this->assertEquals(new DateTime('2023-01-01'), $value->getValue());
    }

    public function testDatetimeFieldGetValue()
    {
        $value = new DatetimeField('2023-01-01');
        $this->assertEquals(new DateTime('2023-01-01'), $value->getValue());
    }

    public function testDatetimeFieldGetType()
    {
        $this->assertSame(DatetimeField::TYPE, DatetimeField::getType());
        $this->assertSame(PublicationFieldTypes::Datetime, DatetimeField::getType());
    }

    public function testDatetimeFieldWithInvalidInput()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to parse time string (foo)');
        new DatetimeField('foo');
    }

    public function testDatetimeFieldWithDynamicInput()
    {
        $value = new DatetimeField('now');
        $this->assertInstanceOf(DateTime::class, $value->getValue());
    }

    public function testDatetimeFieldToYaml()
    {
        $value = new DatetimeField('2023-01-01');
        $this->assertSame('2023-01-01T00:00:00+00:00', Yaml::dump($value->getValue()));
    }
}

class TestValue extends PublicationFieldValue
{
    public const TYPE = PublicationFieldTypes::String;

    protected static function parseInput(string $input): string
    {
        return $input;
    }

    protected static function toYamlType(mixed $input): string
    {
        return $input;
    }
}
