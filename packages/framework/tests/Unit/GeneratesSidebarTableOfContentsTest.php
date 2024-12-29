<?php

/** @noinspection HtmlUnknownAnchorTarget */

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Framework\Actions\GeneratesTableOfContents;
use Hyde\Testing\UnitTestCase;

/**
 * @covers \Hyde\Framework\Actions\GeneratesTableOfContents
 *
 * @see \Hyde\Framework\Testing\Feature\Views\SidebarTableOfContentsViewTest
 */
class GeneratesSidebarTableOfContentsTest extends UnitTestCase
{
    protected static bool $needsConfig = true;

    public function testCanGenerateTableOfContents()
    {
        $markdown = "# Level 1\n## Level 2\n## Level 2B\n### Level 3\n";
        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 2',
                'identifier' => 'level-2',
                'children' => [],
            ],
            [
                'title' => 'Level 2B',
                'identifier' => 'level-2b',
                'children' => [
                    [
                        'title' => 'Level 3',
                        'identifier' => 'level-3',
                        'children' => [],
                    ],
                ],
            ],
        ], $result);
    }

    public function testReturnStringContainsExpectedContent()
    {
        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 2',
                'identifier' => 'level-2',
                'children' => [
                    [
                        'title' => 'Level 3',
                        'identifier' => 'level-3',
                        'children' => [],
                    ],
                ],
            ],
        ], $result);
    }

    public function testCanGenerateTableOfContentsForDocumentUsingSetextHeaders()
    {
        $markdown = <<<'MARKDOWN'
        Level 1
        =======
        Level 2
        -------
        Level 2B
        --------
        MARKDOWN;

        $expected = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ## Level 2B
        MARKDOWN;

        $this->assertSame(
            (new GeneratesTableOfContents($expected))->execute(),
            (new GeneratesTableOfContents($markdown))->execute()
        );

        $this->assertSame(
            [
                [
                    'title' => 'Level 2',
                    'identifier' => 'level-2',
                    'children' => [],
                ],
                [
                    'title' => 'Level 2B',
                    'identifier' => 'level-2b',
                    'children' => [],
                ],
            ],
            (new GeneratesTableOfContents($markdown))->execute(),
        );
    }

    public function testCanGenerateTableOfContentsWithNonLogicalHeadingOrder()
    {
        $markdown = "# Level 1\n### Level 3\n#### Level 4\n";
        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'children' => [
                    [
                        'title' => 'Level 3',
                        'identifier' => 'level-3',
                        'children' => [
                            [
                                'title' => 'Level 4',
                                'identifier' => 'level-4',
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    public function testNonHeadingMarkdownIsIgnored()
    {
        $expected = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        MARKDOWN;

        $actual = <<<'MARKDOWN'
        # Level 1
        Foo bar
        ## Level 2
        Bar baz
        ### Level 3
        Baz foo
        MARKDOWN;

        $this->assertSame(
            (new GeneratesTableOfContents($expected))->execute(),
            (new GeneratesTableOfContents($actual))->execute()
        );

        $this->assertSame(
            [
                [
                    'title' => 'Level 2',
                    'identifier' => 'level-2',
                    'children' => [
                        [
                            'title' => 'Level 3',
                            'identifier' => 'level-3',
                            'children' => [],
                        ],
                    ],
                ],
            ],
            (new GeneratesTableOfContents($actual))->execute(),
        );
    }

    public function testWithNoLevelOneHeading()
    {
        $markdown = <<<'MARKDOWN'
        ## Level 2
        ### Level 3
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 2',
                'identifier' => 'level-2',
                'children' => [
                    [
                        'title' => 'Level 3',
                        'identifier' => 'level-3',
                        'children' => [],
                    ],
                ],
            ],
        ], $result);
    }

    public function testWithMultipleNestedHeadings()
    {
        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        #### Level 4
        ##### Level 5
        ###### Level 6

        ## Level 2B
        ### Level 3B
        ### Level 3C
        ## Level 2C
        ### Level 3D
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 2',
                'identifier' => 'level-2',
                'children' => [
                    [
                        'title' => 'Level 3',
                        'identifier' => 'level-3',
                        'children' => [
                            [
                                'title' => 'Level 4',
                                'identifier' => 'level-4',
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Level 2B',
                'identifier' => 'level-2b',
                'children' => [
                    [
                        'title' => 'Level 3B',
                        'identifier' => 'level-3b',
                        'children' => [],
                    ],
                    [
                        'title' => 'Level 3C',
                        'identifier' => 'level-3c',
                        'children' => [],
                    ],
                ],
            ],
            [
                'title' => 'Level 2C',
                'identifier' => 'level-2c',
                'children' => [
                    [
                        'title' => 'Level 3D',
                        'identifier' => 'level-3d',
                        'children' => [],
                    ],
                ],
            ],
        ], $result);
    }

    public function testWithMultipleLevelOneHeadings()
    {
        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        # Level 1B
        ## Level 2B
        ### Level 3B
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 2',
                'identifier' => 'level-2',
                'children' => [
                    [
                        'title' => 'Level 3',
                        'identifier' => 'level-3',
                        'children' => [],
                    ],
                ],
            ],
            [
                'title' => 'Level 2B',
                'identifier' => 'level-2b',
                'children' => [
                    [
                        'title' => 'Level 3B',
                        'identifier' => 'level-3b',
                        'children' => [],
                    ],
                ],
            ],
        ], $result);
    }

    public function testWithNoHeadings()
    {
        $this->assertSame([], (new GeneratesTableOfContents("Foo bar\nBaz foo"))->execute());
    }

    public function testWithNoContent()
    {
        $this->assertSame([], (new GeneratesTableOfContents(''))->execute());
    }

    public function testRespectsMinHeadingLevelConfig()
    {
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => 3,
        ]);

        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        #### Level 4
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 3',
                'identifier' => 'level-3',
                'children' => [
                    [
                        'title' => 'Level 4',
                        'identifier' => 'level-4',
                        'children' => [],
                    ],
                ],
            ],
        ], $result);
    }

    public function testRespectsMaxHeadingLevelConfig()
    {
        self::mockConfig([
            'docs.sidebar.table_of_contents.max_heading_level' => 2,
        ]);

        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        #### Level 4
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 2',
                'identifier' => 'level-2',
                'children' => [],
            ],
        ], $result);
    }

    public function testRespectsMinAndMaxHeadingLevelConfig()
    {
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => 2,
            'docs.sidebar.table_of_contents.max_heading_level' => 3,
        ]);

        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        #### Level 4
        ##### Level 5
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 2',
                'identifier' => 'level-2',
                'children' => [
                    [
                        'title' => 'Level 3',
                        'identifier' => 'level-3',
                        'children' => [],
                    ],
                ],
            ],
        ], $result);
    }

    public function testWithAllHeadingLevels()
    {
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => 1,
            'docs.sidebar.table_of_contents.max_heading_level' => 6,
        ]);

        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        #### Level 4
        ##### Level 5
        ###### Level 6
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 1',
                'identifier' => 'level-1',
                'children' => [
                    [
                        'title' => 'Level 2',
                        'identifier' => 'level-2',
                        'children' => [
                            [
                                'title' => 'Level 3',
                                'identifier' => 'level-3',
                                'children' => [
                                    [
                                        'title' => 'Level 4',
                                        'identifier' => 'level-4',
                                        'children' => [
                                            [
                                                'title' => 'Level 5',
                                                'identifier' => 'level-5',
                                                'children' => [
                                                    [
                                                        'title' => 'Level 6',
                                                        'identifier' => 'level-6',
                                                        'children' => [],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    public function testHandlesInvalidConfigLevels()
    {
        // Test negative levels
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => -1,
            'docs.sidebar.table_of_contents.max_heading_level' => -2,
        ]);

        $markdown = "## Level 2\n### Level 3";
        $this->assertSame([], (new GeneratesTableOfContents($markdown))->execute());

        // Test levels above 6
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => 7,
            'docs.sidebar.table_of_contents.max_heading_level' => 8,
        ]);

        $this->assertSame([], (new GeneratesTableOfContents($markdown))->execute());

        // Test swapped levels (min > max)
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => 4,
            'docs.sidebar.table_of_contents.max_heading_level' => 2,
        ]);

        $this->assertSame([], (new GeneratesTableOfContents($markdown))->execute());
    }

    public function testSetextHeadersWithDifferentConfigLevels()
    {
        // Test where both setext headers should be included
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => 1,
            'docs.sidebar.table_of_contents.max_heading_level' => 2,
        ]);

        $markdown = <<<'MARKDOWN'
        Level 1
        =======
        Level 2
        -------
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();

        $this->assertSame([
            [
                'title' => 'Level 1',
                'identifier' => 'level-1',
                'children' => [
                    [
                        'title' => 'Level 2',
                        'identifier' => 'level-2',
                        'children' => [],
                    ],
                ],
            ],
        ], $result);

        // Test where no setext headers should be included
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => 3,
            'docs.sidebar.table_of_contents.max_heading_level' => 4,
        ]);

        $this->assertSame([], (new GeneratesTableOfContents($markdown))->execute());
    }

    public function testEmptyRangeConfig()
    {
        // Test where min and max are the same but valid
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => 2,
            'docs.sidebar.table_of_contents.max_heading_level' => 2,
        ]);

        $markdown = <<<'MARKDOWN'
        # Level 1
        ## Level 2
        ### Level 3
        MARKDOWN;

        $result = (new GeneratesTableOfContents($markdown))->execute();
        $this->assertSame([
            [
                'title' => 'Level 2',
                'identifier' => 'level-2',
                'children' => [],
            ],
        ], $result);

        // Test where range results in no valid levels
        self::mockConfig([
            'docs.sidebar.table_of_contents.min_heading_level' => 3,
            'docs.sidebar.table_of_contents.max_heading_level' => 2,
        ]);

        $this->assertSame([], (new GeneratesTableOfContents($markdown))->execute());
    }
}
