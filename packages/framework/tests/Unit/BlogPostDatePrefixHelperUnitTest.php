<?php

declare(strict_types=1);

use Hyde\Testing\UnitTestCase;
use Hyde\Framework\Features\Blogging\BlogPostDatePrefixHelper;

/**
 * @covers \Hyde\Framework\Features\Blogging\BlogPostDatePrefixHelper
 *
 * @see \Hyde\Framework\Testing\Feature\BlogPostDatePrefixHelperTest
 */
class BlogPostDatePrefixHelperUnitTest extends UnitTestCase
{
    public function testHasDatePrefixWithValidDateOnly()
    {
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-my-post.md'));
    }

    public function testHasDatePrefixWithValidDateAndTime()
    {
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-10-30-my-post.md'));
    }

    public function testHasDatePrefixWithoutDatePrefix()
    {
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('my-post.md'));
    }

    public function testHasDatePrefixWithInvalidDateFormat()
    {
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('2024-123-05-my-post.md'));
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-123-my-post.md'));
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-my-post.md'));
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('202-11-05-my-post.md'));
    }

    public function testHasDatePrefixWithInvalidTimeFormat()
    {
        // These are all true, because the parser will think that the time is part of the slug, so we can't reliably detect these cases, as there is technically a *date* prefix
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-123-00-my-post.md'));
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-10-123-my-post.md'));
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-1030-my-post.md'));
    }

    public function testHasDatePrefixWithNoDatePrefixButSimilarPattern()
    {
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('hello-2024-11-05.md'));
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('2024/11/05-my-post.md'));
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('11-05-2024-my-post.md'));
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('post-2024-11-05.md'));
    }

    public function testHasDatePrefixWithExtraCharactersAroundDate()
    {
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('x2024-11-05-my-post.md'));
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-my-post-.md'));
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-my-post123.md'));
    }

    public function testExtractDateWithValidDateOnly()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-my-post.md');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('2024-11-05 00:00', $date->format('Y-m-d H:i'));
    }

    public function testExtractDateWithValidDateAndTime()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-10-30-my-post.md');
        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('2024-11-05 10:30', $date->format('Y-m-d H:i'));
    }

    public function testExtractDateWithoutDatePrefix()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given filepath does not contain a valid ISO 8601 date prefix.');

        BlogPostDatePrefixHelper::extractDate('my-post.md');
    }

    public function testExtractDateWithInvalidDatePrefixFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given filepath does not contain a valid ISO 8601 date prefix.');

        BlogPostDatePrefixHelper::extractDate('2024-11-XX-my-post.md');
    }

    public function testExtractDateWithMalformedTime()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to parse time string (2024-11-05 25:99)');

        BlogPostDatePrefixHelper::extractDate('2024-11-05-25-99-my-post.md');
    }

    public function testStripDatePrefixWithDateOnly()
    {
        $result = BlogPostDatePrefixHelper::stripDatePrefix('2024-11-05-my-post.md');
        $this->assertSame('my-post.md', $result);
    }

    public function testStripDatePrefixWithDateAndTime()
    {
        $result = BlogPostDatePrefixHelper::stripDatePrefix('2024-11-05-10-30-my-post.md');
        $this->assertSame('my-post.md', $result);
    }

    public function testStripDatePrefixWithoutDatePrefix()
    {
        $result = BlogPostDatePrefixHelper::stripDatePrefix('my-post.md');
        $this->assertSame('my-post.md', $result);
    }

    public function testExtractDateWithUnusualCharactersInFilename()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-special_chars-#post.md');
        $this->assertSame('2024-11-05 00:00', $date->format('Y-m-d H:i'));
    }

    public function testExtractDateWithAlternativeExtensions()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-my-post.markdown');
        $this->assertSame('2024-11-05 00:00', $date->format('Y-m-d H:i'));
    }

    public function testEdgeCaseWithExtraHyphens()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-extra-hyphens-in-title.md');
        $this->assertSame('2024-11-05 00:00', $date->format('Y-m-d H:i'));
    }

    public function testStripDatePrefixRetainsHyphensInTitle()
    {
        $result = BlogPostDatePrefixHelper::stripDatePrefix('2024-11-05-extra-hyphens-in-title.md');
        $this->assertSame('extra-hyphens-in-title.md', $result);
    }

    public function testInvalidSingleDigitMonthOrDay()
    {
        $this->assertFalse(BlogPostDatePrefixHelper::hasDatePrefix('2024-1-5-my-post.md'));
    }

    public function testFileWithValidDatePrefixButInvalidExtension()
    {
        $this->assertTrue(BlogPostDatePrefixHelper::hasDatePrefix('2024-11-05-my-post.txt'));
    }

    public function testTimePrefixWithLeadingZeroInHourOrMinute()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-00-30-my-post.md');
        $this->assertSame('2024-11-05 00:30', $date->format('Y-m-d H:i'));

        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-10-00-my-post.md');
        $this->assertSame('2024-11-05 10:00', $date->format('Y-m-d H:i'));
    }

    public function testFilenameWithPotentiallyMisleadingHyphens()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-11-05-extra-hyphens---title.md');
        $this->assertSame('2024-11-05 00:00', $date->format('Y-m-d H:i'));
    }

    public function testLeapYearDate()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-02-29-my-leap-year-post.md');
        $this->assertSame('2024-02-29 00:00', $date->format('Y-m-d H:i'));
    }

    public function testInvalidDates()
    {
        $date = BlogPostDatePrefixHelper::extractDate('2024-04-31-my-post.md');
        $this->assertSame('2024-05-01 00:00', $date->format('Y-m-d H:i'));
    }

    public function testStripDateWithVariousUnconventionalExtensions()
    {
        $result = BlogPostDatePrefixHelper::stripDatePrefix('2024-11-05-my-post.md');
        $this->assertSame('my-post.md', $result);

        $result = BlogPostDatePrefixHelper::stripDatePrefix('2024-11-05-my-post.markdown');
        $this->assertSame('my-post.markdown', $result);

        $result = BlogPostDatePrefixHelper::stripDatePrefix('2024-11-05-my-post.txt');
        $this->assertSame('my-post.txt', $result);
    }
}
