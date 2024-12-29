<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Testing\UnitTestCase;
use Hyde\Foundation\HydeCoreExtension;
use Hyde\Framework\Features\Navigation\NumericalPageOrderingHelper;

/**
 * @covers \Hyde\Framework\Features\Navigation\NumericalPageOrderingHelper
 *
 * @see \Hyde\Framework\Testing\Feature\NumericalPageOrderingHelperTest
 */
class NumericalPageOrderingHelperUnitTest extends UnitTestCase
{
    protected static bool $needsConfig = true;

    public function testIdentifiersWithNumericalPrefixesAreDetected()
    {
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('01-home.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('02-about.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('03-contact.md'));
    }

    public function testIdentifiersWithoutNumericalPrefixesAreNotDetected()
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('home.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('about.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('contact.md'));
    }

    public function testIdentifiersWithNumericalPrefixesAreDetectedWhenUsingSnakeCaseDelimiters()
    {
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('01_home.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('02_about.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('03_contact.md'));
    }

    public function testSplitNumericPrefix()
    {
        $this->assertSame([1, 'home.md'], NumericalPageOrderingHelper::splitNumericPrefix('01-home.md'));
        $this->assertSame([2, 'about.md'], NumericalPageOrderingHelper::splitNumericPrefix('02-about.md'));
        $this->assertSame([3, 'contact.md'], NumericalPageOrderingHelper::splitNumericPrefix('03-contact.md'));
    }

    public function testSplitNumericPrefixForSnakeCaseDelimiters()
    {
        $this->assertSame([1, 'home.md'], NumericalPageOrderingHelper::splitNumericPrefix('01_home.md'));
        $this->assertSame([2, 'about.md'], NumericalPageOrderingHelper::splitNumericPrefix('02_about.md'));
        $this->assertSame([3, 'contact.md'], NumericalPageOrderingHelper::splitNumericPrefix('03_contact.md'));
    }

    public function testSplitNumericPrefixWithMultipleDigits()
    {
        $this->assertSame([123, 'home.md'], NumericalPageOrderingHelper::splitNumericPrefix('123-home.md'));
        $this->assertSame([456, 'about.md'], NumericalPageOrderingHelper::splitNumericPrefix('456-about.md'));
        $this->assertSame([789, 'contact.md'], NumericalPageOrderingHelper::splitNumericPrefix('789-contact.md'));
    }

    public function testSplitNumericPrefixWithMultipleDigitsAndSnakeCaseDelimiters()
    {
        $this->assertSame([123, 'home.md'], NumericalPageOrderingHelper::splitNumericPrefix('123_home.md'));
        $this->assertSame([456, 'about.md'], NumericalPageOrderingHelper::splitNumericPrefix('456_about.md'));
        $this->assertSame([789, 'contact.md'], NumericalPageOrderingHelper::splitNumericPrefix('789_contact.md'));
    }

    public function testIdentifiersForNestedPagesWithNumericalPrefixesAreDetected()
    {
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/01-home.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/02-about.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/03-contact.md'));
    }

    public function testIdentifiersForNestedPagesWithNumericalPrefixesAreDetectedUsingSnakeCaseDelimiters()
    {
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/01_home.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/02_about.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/03_contact.md'));
    }

    public function testIdentifiersForNestedPagesWithoutNumericalPrefixesAreNotDetected()
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo/home.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo/about.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo/contact.md'));
    }

    public function testSplitNumericPrefixForNestedPages()
    {
        $this->assertSame([1, 'foo/home.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/01-home.md'));
        $this->assertSame([2, 'foo/about.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/02-about.md'));
        $this->assertSame([3, 'foo/contact.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/03-contact.md'));
    }

    public function testSplitNumericPrefixForNestedPagesWithSnakeCaseDelimiters()
    {
        $this->assertSame([1, 'foo/home.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/01_home.md'));
        $this->assertSame([2, 'foo/about.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/02_about.md'));
        $this->assertSame([3, 'foo/contact.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/03_contact.md'));
    }

    public function testIdentifiersForDeeplyNestedPagesWithNumericalPrefixesAreDetected()
    {
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/bar/01-home.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/bar/02-about.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/bar/03-contact.md'));
    }

    public function testIdentifiersForDeeplyNestedPagesWithNumericalPrefixesAreDetectedUsingSnakeCaseDelimiters()
    {
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/bar/01_home.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/bar/02_about.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('foo/bar/03_contact.md'));
    }

    public function testIdentifiersForDeeplyNestedPagesWithoutNumericalPrefixesAreNotDetected()
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo/bar/home.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo/bar/about.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo/bar/contact.md'));
    }

    /**
     * @param  class-string<\Hyde\Pages\Concerns\HydePage>  $type
     *
     * @dataProvider pageTypeProvider
     */
    public function testIdentifiersWithNumericalPrefixesAreDetectedForPageType(string $type)
    {
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('01-home.'.$type::$fileExtension));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('02-about.'.$type::$fileExtension));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('03-contact.'.$type::$fileExtension));
    }

    /**
     * @param  class-string<\Hyde\Pages\Concerns\HydePage>  $type
     *
     * @dataProvider pageTypeProvider
     */
    public function testIdentifiersWithoutNumericalPrefixesAreNotDetectedForPageType(string $type)
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('home.'.$type::$fileExtension));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('about.'.$type::$fileExtension));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('contact.'.$type::$fileExtension));
    }

    /**
     * @param  class-string<\Hyde\Pages\Concerns\HydePage>  $type
     *
     * @dataProvider pageTypeProvider
     */
    public function testIdentifiersWithNumericalPrefixesAreDetectedWhenUsingSnakeCaseDelimitersForPageType(string $type)
    {
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('01_home.'.$type::$fileExtension));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('02_about.'.$type::$fileExtension));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('03_contact.'.$type::$fileExtension));
    }

    /**
     * @param  class-string<\Hyde\Pages\Concerns\HydePage>  $type
     *
     * @dataProvider pageTypeProvider
     */
    public function testSplitNumericPrefixForDeeplyNestedPagesForPageType(string $type)
    {
        $this->assertSame([1, 'foo/bar/home.'.$type::$fileExtension], NumericalPageOrderingHelper::splitNumericPrefix('foo/bar/01-home.'.$type::$fileExtension));
        $this->assertSame([2, 'foo/bar/about.'.$type::$fileExtension], NumericalPageOrderingHelper::splitNumericPrefix('foo/bar/02-about.'.$type::$fileExtension));
        $this->assertSame([3, 'foo/bar/contact.'.$type::$fileExtension], NumericalPageOrderingHelper::splitNumericPrefix('foo/bar/03-contact.'.$type::$fileExtension));
    }

    public function testSplitNumericPrefixForDeeplyNestedPages()
    {
        $this->assertSame([1, 'foo/bar/home.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/bar/01-home.md'));
        $this->assertSame([2, 'foo/bar/about.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/bar/02-about.md'));
        $this->assertSame([3, 'foo/bar/contact.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/bar/03-contact.md'));
    }

    public function testSplitNumericPrefixForDeeplyNestedPagesWithSnakeCaseDelimiters()
    {
        $this->assertSame([1, 'foo/bar/home.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/bar/01_home.md'));
        $this->assertSame([2, 'foo/bar/about.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/bar/02_about.md'));
        $this->assertSame([3, 'foo/bar/contact.md'], NumericalPageOrderingHelper::splitNumericPrefix('foo/bar/03_contact.md'));
    }

    public function testNumericalPrefixesInSubdirectoriesAreTrimmed()
    {
        $this->assertSame([1, 'foo.md'], NumericalPageOrderingHelper::splitNumericPrefix('01-foo.md'));
        $this->assertSame([2, 'nested/foo.md'], NumericalPageOrderingHelper::splitNumericPrefix('01-nested/02-foo.md'));
        $this->assertSame([3, 'deeply/nested/foo.md'], NumericalPageOrderingHelper::splitNumericPrefix('01-deeply/02-nested/03-foo.md'));
    }

    public function testNonNumericalPartsAreNotDetected()
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo-bar.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo-bar.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo-bar.md'));

        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo-bar/home.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo-bar/about.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo-bar/contact.md'));
    }

    public function testNonNumericalPartsAreNotDetectedForSnakeCaseDelimiters()
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo_bar.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo_bar.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo_bar.md'));

        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo_bar/home.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo_bar/about.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo_bar/contact.md'));
    }

    public function testNumericallyPrefixedIdentifiersWithUnknownDelimitersAreNotDetected()
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('1.foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('01.foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('001.foo.md'));

        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('1/foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('01/foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('001/foo.md'));

        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('1—foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('01—foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('001—foo.md'));

        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('1 foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('01 foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('001 foo.md'));
    }

    public function testNumericallyPrefixedIdentifiersWithoutDelimiterAreNotDetected()
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('1foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('01foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('001foo.md'));
    }

    public function testNumericallyStringPrefixedIdentifiersWithoutDelimiterAreNotDetected()
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('one-foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('one_foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('one.foo.md'));
    }

    public function testHasNumericalPrefixOnlyReturnsTrueIfLastIdentifierPartIsNumerical()
    {
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('01-nested/foo.md'));
        $this->assertFalse(NumericalPageOrderingHelper::hasNumericalPrefix('01-deeply/02-nested/foo.md'));

        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('01-foo.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('01-nested/02-foo.md'));
        $this->assertTrue(NumericalPageOrderingHelper::hasNumericalPrefix('01-deeply/02-nested/03-foo.md'));
    }

    public static function pageTypeProvider(): array
    {
        self::setupKernel();
        self::mockConfig();

        return array_combine(
            array_map(fn ($class) => str($class)->classBasename()->snake(' ')->plural()->toString(), HydeCoreExtension::getPageClasses()),
            array_map(fn ($class) => [$class], HydeCoreExtension::getPageClasses())
        );
    }
}
