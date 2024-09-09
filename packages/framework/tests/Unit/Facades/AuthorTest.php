<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Facades;

use Hyde\Facades\Author;
use Hyde\Testing\UnitTestCase;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;

/**
 * @covers \Hyde\Facades\Author
 */
class AuthorTest extends UnitTestCase
{
    protected function setUp(): void
    {
        self::mockConfig(['hyde.authors' => [
            Author::create('mr_hyde', 'Mr. Hyde', 'https://hydephp.com'),
        ]]);
    }

    public function testCreate()
    {
        $author = Author::create('john_doe', 'John Doe', 'https://johndoe.com');

        $this->assertSame('john_doe', $author->username);
        $this->assertSame('John Doe', $author->name);
        $this->assertSame('https://johndoe.com', $author->website);

        $this->assertEquals(Author::create('foo'), Author::create('foo'));
    }

    public function testGet()
    {
        $author = Author::get('mr_hyde');

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
