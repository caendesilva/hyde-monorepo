<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Facades;

use Hyde\Hyde;
use Hyde\Facades\Author;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;
use Hyde\Testing\UnitTestCase;

/**
 * @covers \Hyde\Facades\Author
 */
class AuthorFacadeTest extends UnitTestCase
{
    protected static bool $needsKernel = true;

    protected function setUp(): void
    {
        static $config = null;

        if ($config === null) {
            $config = require Hyde::path('config/hyde.php');
        }

        self::mockConfig(['hyde' => $config]);
    }

    public function testCreate()
    {
        $author = Author::create('John Doe', 'https://johndoe.com');

        $this->assertIsArray($author);
        $this->assertFalse(isset($author['username']));
        $this->assertSame('John Doe', $author['name']);
        $this->assertSame('https://johndoe.com', $author['website']);

        $this->assertSame([
            'name' => 'John Doe',
            'website' => 'https://johndoe.com',
            'bio' => null,
            'avatar' => null,
            'socials' => null,
        ], $author);

        $this->assertEquals(Author::create('foo'), Author::create('foo'));
    }

    public function testCreateWithAllParameters()
    {
        $author = Author::create('John Doe', 'https://johndoe.com', 'A cool guy', 'https://johndoe.com/avatar.jpg', [
            'twitter' => 'johndoe',
            'github' => 'johndoe',
        ]);

        $this->assertSame([
            'name' => 'John Doe',
            'website' => 'https://johndoe.com',
            'bio' => 'A cool guy',
            'avatar' => 'https://johndoe.com/avatar.jpg',
            'socials' => [
                'twitter' => 'johndoe',
                'github' => 'johndoe',
            ],
        ], $author);
    }

    public function testGet()
    {
        $author = Author::get('mr_hyde');

        $this->assertInstanceOf(PostAuthor::class, $author);

        $this->assertSame('mr_hyde', $author->username);
        $this->assertSame('Mr. Hyde', $author->name);
        $this->assertSame('https://hydephp.com', $author->website);

        $this->assertEquals(PostAuthor::get('foo'), Author::get('foo'));
    }

    public function testAll()
    {
        $authors = Author::all();
        $this->assertCount(1, $authors);
        $this->assertContainsOnlyInstancesOf(PostAuthor::class, $authors);
    }
}
