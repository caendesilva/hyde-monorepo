<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Facades\Author;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;
use Hyde\Testing\UnitTestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * @covers \Hyde\Framework\Features\Blogging\Models\PostAuthor
 */
class PostAuthorTest extends UnitTestCase
{
    protected static bool $needsKernel = true;
    protected static bool $needsConfig = true;

    protected function setUp(): void
    {
        parent::setUp();

        self::resetKernel();
    }

    public function testCanCreateAuthorModel()
    {
        $author = new PostAuthor('foo');

        $this->assertInstanceOf(PostAuthor::class, $author);
    }

    public function testCanCreateAuthorModelWithDetails()
    {
        $author = new PostAuthor('foo', 'bar', 'https://example.com');

        $this->assertSame('foo', $author->username);
        $this->assertSame('bar', $author->name);
        $this->assertSame('https://example.com', $author->website);
    }

    public function testCanCreateAuthorModelWithFullDetails()
    {
        [$username, $name, $website, $bio, $avatar, $socials] = array_values($this->exampleData());

        $author = new PostAuthor(
            username: $username,
            name: $name,
            website: $website,
            bio: $bio,
            avatar: $avatar,
            socials: $socials
        );

        $this->assertSame($username, $author->username);
        $this->assertSame($name, $author->name);
        $this->assertSame($website, $author->website);
        $this->assertSame($bio, $author->bio);
        $this->assertSame($avatar, $author->avatar);
        $this->assertSame($socials, $author->socials);
    }

    public function testNameIsSetToUsernameIfNoNameIsProvided()
    {
        $author = new PostAuthor('foo');

        $this->assertSame('foo', $author->name);
    }

    public function testCreateMethodCreatesNewAuthorModel()
    {
        $author = Author::create('foo');

        $this->assertInstanceOf(PostAuthor::class, $author);
    }

    public function testCreateMethodAcceptsAllParameters()
    {
        $author = Author::create('foo', 'bar', 'https://example.com');

        $this->assertSame('foo', $author->username);
        $this->assertSame('bar', $author->name);
        $this->assertSame('https://example.com', $author->website);
    }

    public function testGetOrCreateMethodCreatesNewAuthorModelFromString()
    {
        $author = PostAuthor::getOrCreate('foo');
        $this->assertEquals($author, new PostAuthor('foo'));
    }

    public function testGetOrCreateMethodCreatesNewAuthorModelFromStringCanFindExistingAuthor()
    {
        Config::set('hyde.authors', [
            Author::create('foo', 'bar'),
        ]);

        $this->assertEquals(PostAuthor::getOrCreate('foo'), Author::create('foo', 'bar'));
    }

    public function testGetOrCreateMethodCreatesNewAuthorModelFromArray()
    {
        $author = PostAuthor::getOrCreate([
            'username' => 'foo',
            'name' => 'bar',
            'website' => 'https://example.com',
        ]);

        $this->assertEquals($author, Author::create('foo', 'bar', 'https://example.com'));
    }

    public function testGetOrCreateMethodCreatesNewAuthorModelFromArrayOnlyNeedsUsername()
    {
        $this->assertEquals(PostAuthor::getOrCreate(['username' => 'foo']), Author::create('foo'));
    }

    public function testAllMethodReturnsEmptyCollectionIfNoAuthorsAreSetInConfig()
    {
        Config::set('hyde.authors', []);
        $authors = PostAuthor::all();

        $this->assertInstanceOf(Collection::class, $authors);
        $this->assertCount(0, $authors);
    }

    public function testAllMethodReturnsCollectionWithAllAuthorsDefinedInConfig()
    {
        Config::set('hyde.authors', [
            Author::create('foo'),
        ]);

        $authors = PostAuthor::all();

        $this->assertInstanceOf(Collection::class, $authors);
        $this->assertCount(1, $authors);
        $this->assertEquals(Author::create('foo'), $authors->first());
    }

    public function testMultipleAuthorsCanBeDefinedInConfig()
    {
        Config::set('hyde.authors', [
            Author::create('foo'),
            Author::create('bar'),
        ]);

        $authors = PostAuthor::all();

        $this->assertInstanceOf(Collection::class, $authors);
        $this->assertCount(2, $authors);
        $this->assertEquals(Author::create('foo'), $authors->first());
        $this->assertEquals(Author::create('bar'), $authors->last());
    }

    public function testGetMethodReturnsConfigDefinedAuthorByUsername()
    {
        Config::set('hyde.authors', [
            Author::create('foo', 'bar'),
        ]);
        $author = PostAuthor::get('foo');

        $this->assertInstanceOf(PostAuthor::class, $author);
        $this->assertSame('foo', $author->username);
        $this->assertSame('bar', $author->name);
    }

    public function testGetMethodReturnsNewAuthorIfUsernameNotFoundInConfig()
    {
        Config::set('hyde.authors', []);
        $author = PostAuthor::get('foo');

        $this->assertInstanceOf(PostAuthor::class, $author);
        $this->assertSame('foo', $author->username);
    }

    public function testGetNameHelperReturnsNameIfSet()
    {
        $author = new PostAuthor('username', 'John Doe');

        $this->assertSame('John Doe', $author->getName());
    }

    public function testGetNameHelperReturnsUsernameIfNameIsNotSet()
    {
        $author = new PostAuthor('username');

        $this->assertSame('username', $author->getName());
    }

    public function testToStringHelperReturnsTheName()
    {
        $author = new PostAuthor('username', 'John Doe');

        $this->assertSame('John Doe', (string) $author);
    }

    public function testToArrayMethodReturnsArrayRepresentationOfAuthor()
    {
        $author = new PostAuthor('username', 'John Doe', 'https://example.com');

        $this->assertEquals([
            'username' => 'username',
            'name' => 'John Doe',
            'website' => 'https://example.com',
        ], $author->toArray());
    }

    public function testJsonSerializeMethodReturnsArrayRepresentationOfAuthor()
    {
        $author = new PostAuthor('username', 'John Doe', 'https://example.com');

        $this->assertEquals([
            'username' => 'username',
            'name' => 'John Doe',
            'website' => 'https://example.com',
        ], $author->jsonSerialize());
    }

    public function testArraySerializeMethodReturnsArrayRepresentationOfAuthor()
    {
        $author = new PostAuthor('username', 'John Doe', 'https://example.com');

        $this->assertEquals([
            'username' => 'username',
            'name' => 'John Doe',
            'website' => 'https://example.com',
        ], $author->arraySerialize());
    }

    public function testToJsonMethodReturnsJsonRepresentationOfAuthor()
    {
        $author = new PostAuthor('username', 'John Doe', 'https://example.com');

        $this->assertSame('{"username":"username","name":"John Doe","website":"https:\/\/example.com"}', $author->toJson());
    }

    public function testCanJsonEncodeAuthor()
    {
        $author = new PostAuthor('username', 'John Doe', 'https://example.com');

        $this->assertSame('{"username":"username","name":"John Doe","website":"https:\/\/example.com"}', json_encode($author));
    }

    public function testEmptyFieldsAreRemovedFromSerializedModel()
    {
        $author = new PostAuthor('username', null, null);

        $this->assertSame('{"username":"username","name":"username"}', $author->toJson());
    }

    protected function exampleData(): array
    {
        $username = 'mr_hyde';
        $name = 'Mr. Hyde';
        $website = 'https://HydePHP.com';
        $bio = 'A mysterious figure. Is he as evil as he seems? And what did he do with Dr. Jekyll?';
        $avatar = 'mr_hyde.png';
        $socials = ['twitter' => 'HydeFramework', 'github' => 'hydephp', 'custom' => 'https://example.com'];

        return [
            'username' => $username,
            'name' => $name,
            'website' => $website,
            'bio' => $bio,
            'avatar' => $avatar,
            'socials' => $socials
        ];
    }
}
