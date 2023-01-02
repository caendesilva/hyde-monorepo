<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use DateTime;
use Exception;
use Hyde\Framework\Features\Publications\Models\PublicationFields\ArrayField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\BooleanField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\DatetimeField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\FloatField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\ImageField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\IntegerField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\PublicationField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\PublicationFieldValue;
use Hyde\Framework\Features\Publications\Models\PublicationFields\StringField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\TagField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\TextField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\UrlField;
use Hyde\Framework\Features\Publications\PublicationFieldTypes;
use Hyde\Framework\Features\Publications\Validation\BooleanRule;
use Hyde\Testing\TestCase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Hyde\Framework\Features\Publications\PublicationFieldService
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\PublicationFieldValue
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\PublicationField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\StringField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\DatetimeField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\BooleanField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\IntegerField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\FloatField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\ArrayField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\TextField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\UrlField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\ImageField
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationFields\TagField
 */
class PublicationFieldServiceTest extends TestCase
{
    // Base class tests

    public function testConstruct()
    {
        $this->assertInstanceOf(PublicationFieldTestClass::class, (new PublicationFieldTestClass('foo')));
    }

    public function testGetValue()
    {
        $this->assertSame('foo', (new PublicationFieldTestClass('foo'))->getValue());
    }

    public function testTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::String, PublicationFieldTestClass::TYPE);
    }

    // StringField tests

    public function testStringFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('string'), 'foo'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::String, $field->type);
    }

    public function testStringFieldGetValue()
    {
        $this->assertSame('foo', (new PublicationFieldValue(PublicationFieldTypes::from('string'), 'foo'))->getValue());
    }

    public function testStringFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::String, StringField::TYPE);
    }

    public function testStringFieldToYaml()
    {
        $this->assertSame('foo', $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('string'), 'foo')));
    }

    public function testStringFieldParsingOptions()
    {
        $this->assertSame('foo', (new PublicationFieldValue(PublicationFieldTypes::from('string'), 'foo'))->getValue());
        $this->assertSame('true', (new PublicationFieldValue(PublicationFieldTypes::from('string'), 'true'))->getValue());
        $this->assertSame('false', (new PublicationFieldValue(PublicationFieldTypes::from('string'), 'false'))->getValue());
        $this->assertSame('null', (new PublicationFieldValue(PublicationFieldTypes::from('string'), 'null'))->getValue());
        $this->assertSame('0', (new PublicationFieldValue(PublicationFieldTypes::from('string'), '0'))->getValue());
        $this->assertSame('1', (new PublicationFieldValue(PublicationFieldTypes::from('string'), '1'))->getValue());
        $this->assertSame('10.5', (new PublicationFieldValue(PublicationFieldTypes::from('string'), '10.5'))->getValue());
        $this->assertSame('-10', (new PublicationFieldValue(PublicationFieldTypes::from('string'), '-10'))->getValue());
    }

    // DatetimeField tests

    public function testDatetimeFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('datetime'), '2023-01-01'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::Datetime, $field->type);
    }

    public function testDatetimeFieldGetValue()
    {
        $this->assertEquals(new DateTime('2023-01-01'), (new PublicationFieldValue(PublicationFieldTypes::from('datetime'), '2023-01-01'))->getValue());
    }

    public function testDatetimeFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::Datetime, DatetimeField::TYPE);
    }

    public function testDatetimeFieldWithInvalidInput()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to parse time string (foo)');
        new PublicationFieldValue(PublicationFieldTypes::from('datetime'), 'foo');
    }

    public function testDatetimeFieldWithDynamicInput()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('datetime'), 'now'))->getValue();

        $this->assertInstanceOf(DateTime::class, $field);
    }

    public function testDatetimeFieldToYaml()
    {
        $this->assertSame('2023-01-01T00:00:00+00:00', $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('datetime'), '2023-01-01')));
    }

    // BooleanField tests

    public function testBooleanFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('boolean'), 'true'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::Boolean, $field->type);
    }

    public function testBooleanFieldGetValue()
    {
        $this->assertSame(true, (new PublicationFieldValue(PublicationFieldTypes::from('boolean'), 'true'))->getValue());
    }

    public function testBooleanFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::Boolean, BooleanField::TYPE);
    }

    public function testBooleanFieldToYaml()
    {
        $this->assertSame('true', $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('boolean'), 'true')));
    }

    public function testBooleanFieldWithInvalidInput()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanField: Unable to parse invalid boolean value \'foo\'');
        new PublicationFieldValue(PublicationFieldTypes::from('boolean'), 'foo');
    }

    public function testBooleanFieldParsingOptions()
    {
        $this->assertSame(true, (new PublicationFieldValue(PublicationFieldTypes::from('boolean'), 'true'))->getValue());
        $this->assertSame(true, (new PublicationFieldValue(PublicationFieldTypes::from('boolean'), '1'))->getValue());
        $this->assertSame(false, (new PublicationFieldValue(PublicationFieldTypes::from('boolean'), 'false'))->getValue());
        $this->assertSame(false, (new PublicationFieldValue(PublicationFieldTypes::from('boolean'), '0'))->getValue());
    }

    // IntegerField tests

      public function testIntegerFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '10'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::Integer, $field->type);
    }

    public function testIntegerFieldGetValue()
    {
        $this->assertSame(10, (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '10'))->getValue());
    }

    public function testIntegerFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::Integer, IntegerField::TYPE);
    }

    public function testIntegerFieldToYaml()
    {
        $this->assertSame('10', $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('integer'), '10')));
    }

    public function testIntegerFieldWithInvalidInput()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IntegerField: Unable to parse invalid integer value \'foo\'');
        new PublicationFieldValue(PublicationFieldTypes::from('integer'), 'foo');
    }

    public function testIntegerFieldParsingOptions()
    {
        $this->assertSame(0, (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '0'))->getValue());
        $this->assertSame(1, (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '1'))->getValue());
        $this->assertSame(10, (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '10'))->getValue());
        $this->assertSame(10, (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '10.0'))->getValue());
        $this->assertSame(10, (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '10.5'))->getValue());
        $this->assertSame(10, (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '10.9'))->getValue());
        $this->assertSame(100, (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '1E2'))->getValue());
        $this->assertSame(-10, (new PublicationFieldValue(PublicationFieldTypes::from('integer'), '-10'))->getValue());
    }

    // FloatField tests

      public function testFloatFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('float'), '10'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::Float, $field->type);
    }

    public function testFloatFieldGetValue()
    {
        $this->assertSame(10.0, (new PublicationFieldValue(PublicationFieldTypes::from('float'), '10'))->getValue());
    }

    public function testFloatFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::Float, FloatField::TYPE);
    }

    public function testFloatFieldToYaml()
    {
        $this->assertSame('10.0', $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('float'), '10')));
    }

    public function testFloatFieldWithInvalidInput()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FloatField: Unable to parse invalid float value \'foo\'');
        new PublicationFieldValue(PublicationFieldTypes::from('float'), 'foo');
    }

    public function testFloatFieldParsingOptions()
    {
        $this->assertSame(0.0, (new PublicationFieldValue(PublicationFieldTypes::from('float'), '0'))->getValue());
        $this->assertSame(1.0, (new PublicationFieldValue(PublicationFieldTypes::from('float'), '1'))->getValue());
        $this->assertSame(10.0, (new PublicationFieldValue(PublicationFieldTypes::from('float'), '10'))->getValue());
        $this->assertSame(10.0, (new PublicationFieldValue(PublicationFieldTypes::from('float'), '10.0'))->getValue());
        $this->assertSame(10.5, (new PublicationFieldValue(PublicationFieldTypes::from('float'), '10.5'))->getValue());
        $this->assertSame(10.9, (new PublicationFieldValue(PublicationFieldTypes::from('float'), '10.9'))->getValue());
        $this->assertSame(100.0, (new PublicationFieldValue(PublicationFieldTypes::from('float'), '1E2'))->getValue());
        $this->assertSame(-10.0, (new PublicationFieldValue(PublicationFieldTypes::from('float'), '-10'))->getValue());
    }

    // ArrayField tests

    public function testArrayFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('array'), 'foo'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::Array, $field->type);
    }

    public function testArrayFieldGetValue()
    {
        $this->assertSame(['foo'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), 'foo'))->getValue());
    }

    public function testArrayFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::Array, ArrayField::TYPE);
    }

    public function testArrayFieldToYaml()
    {
        $this->assertSame("- foo\n", $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('array'), 'foo')));
    }

    public function testArrayFieldWithArrayInput()
    {
        $this->assertSame(['foo'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), ['foo']))->getValue());
    }

    public function testArrayFieldParsingOptions()
    {
        $this->assertSame(['foo'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), 'foo'))->getValue());
        $this->assertSame(['true'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), 'true'))->getValue());
        $this->assertSame(['false'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), 'false'))->getValue());
        $this->assertSame(['null'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), 'null'))->getValue());
        $this->assertSame(['0'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), '0'))->getValue());
        $this->assertSame(['1'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), '1'))->getValue());
        $this->assertSame(['10.5'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), '10.5'))->getValue());
        $this->assertSame(['-10'], (new PublicationFieldValue(PublicationFieldTypes::from('array'), '-10'))->getValue());
    }

    // TextField tests

      public function testTextFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('text'), 'foo'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::Text, $field->type);
    }

    public function testTextFieldGetValue()
    {
        $this->assertSame('foo', (new PublicationFieldValue(PublicationFieldTypes::from('text'), 'foo'))->getValue());
    }

    public function testTextFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::Text, TextField::TYPE);
    }

    public function testTextFieldToYaml()
    {
        $this->assertSame('foo', $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('text'), 'foo')));
        // Note that this does not use the same flags as the creator action, because that's out of scope for this test.
    }

    public function testTextFieldParsingOptions()
    {
        $this->assertSame('foo', (new PublicationFieldValue(PublicationFieldTypes::from('text'), 'foo'))->getValue());
        $this->assertSame('true', (new PublicationFieldValue(PublicationFieldTypes::from('text'), 'true'))->getValue());
        $this->assertSame('false', (new PublicationFieldValue(PublicationFieldTypes::from('text'), 'false'))->getValue());
        $this->assertSame('null', (new PublicationFieldValue(PublicationFieldTypes::from('text'), 'null'))->getValue());
        $this->assertSame('0', (new PublicationFieldValue(PublicationFieldTypes::from('text'), '0'))->getValue());
        $this->assertSame('1', (new PublicationFieldValue(PublicationFieldTypes::from('text'), '1'))->getValue());
        $this->assertSame('10.5', (new PublicationFieldValue(PublicationFieldTypes::from('text'), '10.5'))->getValue());
        $this->assertSame('-10', (new PublicationFieldValue(PublicationFieldTypes::from('text'), '-10'))->getValue());
        $this->assertSame("foo\nbar\n", (new PublicationFieldValue(PublicationFieldTypes::from('text'), "foo\nbar"))->getValue());
        $this->assertSame("foo\nbar\n", (new PublicationFieldValue(PublicationFieldTypes::from('text'), "foo\nbar\n"))->getValue());
        $this->assertSame("foo\nbar\nbaz\n", (new PublicationFieldValue(PublicationFieldTypes::from('text'), "foo\nbar\nbaz"))->getValue());
        $this->assertSame("foo\nbar\nbaz\n", (new PublicationFieldValue(PublicationFieldTypes::from('text'), "foo\nbar\nbaz\n"))->getValue());
        $this->assertSame("foo\r\nbar\r\nbaz\n", (new PublicationFieldValue(PublicationFieldTypes::from('text'), "foo\r\nbar\r\nbaz\r\n"))->getValue());
    }

    // UrlField tests

      public function testUrlFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('url'), 'https://example.com'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::from('url'), $field->type);
    }

    public function testUrlFieldGetValue()
    {
        $this->assertSame('https://example.com', (new PublicationFieldValue(PublicationFieldTypes::from('url'), 'https://example.com'))->getValue());
    }

    public function testUrlFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::from('url'), UrlField::TYPE);
    }

    public function testUrlFieldToYaml()
    {
        $this->assertSame('\'https://example.com\'', $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('url'), 'https://example.com')));
    }

    public function testUrlFieldWithInvalidInput()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UrlField: Unable to parse invalid url value \'foo\'');
        new PublicationFieldValue(PublicationFieldTypes::from('url'), 'foo');
    }

    // ImageField tests

      public function testImageFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('image'), 'foo'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::Image, $field->type);
    }

    public function testImageFieldGetValue()
    {
        $this->assertSame('foo', (new PublicationFieldValue(PublicationFieldTypes::from('image'), 'foo'))->getValue());
    }

    public function testImageFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::Image, ImageField::TYPE);
    }

    public function testImageFieldToYaml()
    {
        $this->assertSame('foo', $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('image'), 'foo')));
    }

    // TagField tests

      public function testTagFieldConstruct()
    {
        $field = (new PublicationFieldValue(PublicationFieldTypes::from('tag'), 'foo'));

        $this->assertInstanceOf(PublicationFieldValue::class, $field);
        $this->assertSame(PublicationFieldTypes::Tag, $field->type);
    }

    public function testTagFieldGetValue()
    {
        $this->assertSame(['foo'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), 'foo'))->getValue());
    }

    public function testTagFieldTypeConstant()
    {
        $this->assertSame(PublicationFieldTypes::Tag, TagField::TYPE);
    }

    public function testTagFieldToYaml()
    {
        $this->assertSame("- foo\n", $this->getYaml(new PublicationFieldValue(PublicationFieldTypes::from('tag'), 'foo')));
    }

    public function testTagFieldWithArrayInput()
    {
        $this->assertSame(['foo'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), ['foo']))->getValue());
    }

    public function testTagFieldParsingOptions()
    {
        $this->assertSame(['foo'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), 'foo'))->getValue());
        $this->assertSame(['true'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), 'true'))->getValue());
        $this->assertSame(['false'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), 'false'))->getValue());
        $this->assertSame(['null'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), 'null'))->getValue());
        $this->assertSame(['0'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), '0'))->getValue());
        $this->assertSame(['1'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), '1'))->getValue());
        $this->assertSame(['10.5'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), '10.5'))->getValue());
        $this->assertSame(['-10'], (new PublicationFieldValue(PublicationFieldTypes::from('tag'), '-10'))->getValue());
    }

    // Additional tests

    public function testAllTypesHaveAValueClass()
    {
        $namespace = Str::beforeLast(PublicationField::class, '\\');
        foreach (PublicationFieldTypes::names() as $type) {
            $this->assertTrue(
                class_exists("$namespace\\{$type}Field"),
                "Missing value class for type $type"
            );
        }
    }

    public function testAllTypesCanBeResolvedByTheServiceContainer()
    {
        $namespace = Str::beforeLast(PublicationField::class, '\\');
        foreach (PublicationFieldTypes::names() as $type) {
            $this->assertInstanceOf(
                "$namespace\\{$type}Field",
                app()->make("$namespace\\{$type}Field")
            );
        }
    }

    public function testDefaultValidationRules()
    {
        $expected = [
            'string' => ['string'],
            'datetime' => ['date'],
            'boolean' => [new BooleanRule],
            'integer' => ['integer', 'numeric'],
            'float' => ['numeric'],
            'image' => [],
            'array' => ['array'],
            'text' => ['string'],
            'url' => ['url'],
            'tag' => [],
        ];

        foreach ($expected as $type => $rules) {
            $this->assertEquals($rules, PublicationFieldTypes::from($type)->rules());
        }
    }

    // Testing helper methods

    protected function getYaml(PublicationFieldValue $field): string
    {
        return Yaml::dump($field->getValue());
    }
}

class PublicationFieldTestClass extends PublicationField
{
    public const TYPE = PublicationFieldTypes::String;
}
