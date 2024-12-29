<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Hyde;
use Hyde\Facades\Author;
use Hyde\Pages\MarkdownPost;
use Hyde\Testing\FluentTestingHelpers;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;
use Hyde\Testing\UnitTestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * @covers \Hyde\Framework\Features\Blogging\Models\PostAuthor
 * @covers \Hyde\Foundation\Concerns\HasKernelData
 */
class PostAuthorTest extends UnitTestCase
{
    use FluentTestingHelpers;

    protected static bool $needsKernel = true;
    protected static bool $needsConfig = true;

    protected function setUp(): void
    {
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
        [$username, $name, $website, $bio, $avatar, $socials] = array_values($this->exampleDataWithUsername());

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

    public function testCanCreateAuthorModelWithFullDetailsFromArgumentUnpacking()
    {
        $data = $this->exampleDataWithUsername();

        $author = new PostAuthor(...$data);

        $this->assertSame($data['username'], $author->username);
        $this->assertSame($data['name'], $author->name);
        $this->assertSame($data['website'], $author->website);
        $this->assertSame($data['bio'], $author->bio);
        $this->assertSame($data['avatar'], $author->avatar);
        $this->assertSame($data['socials'], $author->socials);
    }

    public function testCanCreateAuthorModelWithFullDetailsFromArrayUsingCreate()
    {
        $data = $this->exampleData();

        $author = PostAuthor::create($data);

        $this->assertSame($data['name'], $author->name);
        $this->assertSame($data['website'], $author->website);
        $this->assertSame($data['bio'], $author->bio);
        $this->assertSame($data['avatar'], $author->avatar);
        $this->assertSame($data['socials'], $author->socials);
    }

    public function testCanCreateAuthorModelWithSomeDetailsFromArrayUsingCreate()
    {
        $data = $this->exampleData();

        $author = PostAuthor::create([
            'name' => $data['name'],
            'website' => $data['website'],
        ]);

        $this->assertSame($data['name'], $author->name);
        $this->assertSame($data['website'], $author->website);
        $this->assertNull($author->bio);
        $this->assertNull($author->avatar);
        $this->assertEmpty($author->socials);
    }

    public function testCanCreateAuthorModelWithSomeDetailsFromArrayUsingCreateWithoutUsername()
    {
        $data = $this->exampleData();

        $author = PostAuthor::create([
            'name' => $data['name'],
            'website' => $data['website'],
        ]);

        $this->assertSame('mr_hyde', $author->username);
        $this->assertSame($data['name'], $author->name);
        $this->assertSame($data['website'], $author->website);
    }

    public function testCanCreateAuthorModelWithSomeDetailsFromArrayUsingCreateWithoutAnyNames()
    {
        $data = $this->exampleData();

        $author = PostAuthor::create([
            'website' => $data['website'],
        ]);

        $this->assertSame('guest', $author->username);
        $this->assertSame('Guest', $author->name);
        $this->assertSame($data['website'], $author->website);
    }

    public function testNameIsGeneratedFromUsernameIfNoNameIsProvided()
    {
        $author = new PostAuthor('foo');

        $this->assertSame('Foo', $author->name);
    }

    public function testFacadeCreateMethodCreatesNewPendingAuthorArray()
    {
        $author = Author::create('foo');

        $this->assertSame([
            'name' => 'foo',
            'website' => null,
            'bio' => null,
            'avatar' => null,
            'socials' => null,
        ], $author);
    }

    public function testFacadeCreateMethodAcceptsExtraParameters()
    {
        $author = Author::create('foo', 'https://example.com');

        $this->assertFalse(isset($author['username']));
        $this->assertSame('foo', $author['name']);
        $this->assertSame('https://example.com', $author['website']);
    }

    public function testFacadeCreateMethodAcceptsAllParameters()
    {
        $author = Author::create(...$this->exampleData());

        $this->assertFalse(isset($author['username']));
        $this->assertSame('Mr. Hyde', $author['name']);
        $this->assertSame('https://HydePHP.com', $author['website']);
        $this->assertSame('A mysterious figure. Is he as evil as he seems? And what did he do with Dr. Jekyll?', $author['bio']);
        $this->assertSame('mr_hyde.png', $author['avatar']);
        $this->assertSame(['twitter' => 'HydeFramework', 'github' => 'hydephp', 'custom' => 'https://example.com'], $author['socials']);
    }

    public function testGetMethodReturnsNullWhenAuthorIsNotFound()
    {
        $author = PostAuthor::get('foo');
        $this->assertNull($author);
    }

    public function testCreateMethodCreatesNewAuthorModelFromStringCanFindExistingAuthor()
    {
        Config::set('hyde.authors', [
            'foo' => Author::create('bar'),
        ]);

        $this->assertEquals(PostAuthor::get('foo'), new PostAuthor('foo', 'bar'));
    }

    public function testCreateMethodCreatesNewAuthorModelFromArray()
    {
        $author = PostAuthor::create([
            'username' => 'foo',
            'name' => 'bar',
            'website' => 'https://example.com',
        ]);

        $this->assertEquals($author, new PostAuthor('foo', 'bar', 'https://example.com'));
    }

    public function testCreateMethodCreatesNewAuthorModelFromArrayOnlyNeedsUsername()
    {
        $this->assertEquals(PostAuthor::create(['username' => 'foo']), new PostAuthor('foo'));
    }

    public function testCreateMethodWithNoUsernameUsesNameAsUsernameIfNameIsSupplied()
    {
        $author = PostAuthor::create(['name' => 'foo']);

        $this->assertEquals($author, new PostAuthor('foo', 'foo'));
    }

    public function testCreateMethodWithNoUsernameUsesNormalizedNameAsUsernameIfNameIsSupplied()
    {
        $author = PostAuthor::create(['name' => 'Foo']);

        $this->assertEquals($author, new PostAuthor('foo', 'Foo'));
    }

    public function testCreateMethodWithNoUsernameUsesGuestAsUsernameIfNoNameIsSupplied()
    {
        $author = PostAuthor::create([]);

        $this->assertEquals($author, new PostAuthor('guest'));

        $this->assertSame('guest', $author->username);
        $this->assertSame('Guest', $author->name);
    }

    public function testCanDefineAuthorWithNoDataInConfig()
    {
        Config::set('hyde.authors', [
            'foo' => Author::create(),
        ]);

        $authors = PostAuthor::all();

        $this->assertInstanceOf(Collection::class, $authors);
        $this->assertCount(1, $authors);
        $this->assertEquals(new PostAuthor('foo', 'Foo'), $authors->first());
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
            'foo' => Author::create(),
        ]);

        $authors = PostAuthor::all();

        $this->assertInstanceOf(Collection::class, $authors);
        $this->assertCount(1, $authors);
        $this->assertEquals(new PostAuthor('foo'), $authors->first());
    }

    public function testMultipleAuthorsCanBeDefinedInConfig()
    {
        Config::set('hyde.authors', [
            'foo' => Author::create(),
            'bar' => Author::create(),
        ]);

        $authors = PostAuthor::all();

        $this->assertInstanceOf(Collection::class, $authors);
        $this->assertCount(2, $authors);
        $this->assertEquals(new PostAuthor('foo'), $authors->first());
        $this->assertEquals(new PostAuthor('bar'), $authors->last());
    }

    public function testGetMethodReturnsConfigDefinedAuthorByUsername()
    {
        Config::set('hyde.authors', [
            'foo' => Author::create('bar'),
        ]);

        $author = PostAuthor::get('foo');

        $this->assertInstanceOf(PostAuthor::class, $author);
        $this->assertSame('foo', $author->username);
        $this->assertSame('bar', $author->name);
    }

    public function testGetMethodReturnsNullIfUsernameNotFoundInConfig()
    {
        Config::set('hyde.authors', []);

        $author = PostAuthor::get('foo');

        $this->assertNull($author);
    }

    public function testGetMethodNormalizesUsernamesForRetrieval()
    {
        Config::set('hyde.authors', [
            'foo_bar' => Author::create(),
        ]);

        $author = PostAuthor::get('Foo bar');

        $this->assertInstanceOf(PostAuthor::class, $author);
        $this->assertSame('foo_bar', $author->username);
        $this->assertSame('Foo Bar', $author->name);

        $this->assertAllSame(
            PostAuthor::get('foo_bar'),
            PostAuthor::get('foo-bar'),
            PostAuthor::get('foo bar'),
            PostAuthor::get('Foo Bar'),
            PostAuthor::get('FOO BAR'),
        );
    }

    public function testAuthorUsernamesAreNormalizedInTheConfig()
    {
        Config::set('hyde.authors', [
            'foo_bar' => Author::create(),
            'foo-bar' => Author::create(),
            'foo bar' => Author::create(),
            'Foo Bar' => Author::create(),
            'FOO BAR' => Author::create(),
        ]);

        $this->assertCount(1, PostAuthor::all());
    }

    public function testOnlyLastAuthorWithNormalizedUsernameIsKept()
    {
        Config::set('hyde.authors', [
            'foo_bar' => Author::create('Author 1'),
            'foo-bar' => Author::create('Author 2'),
            'foo bar' => Author::create('Author 3'),
        ]);

        $authors = PostAuthor::all();

        $this->assertCount(1, $authors);
        $this->assertEquals(new PostAuthor('foo_bar', 'Author 3'), $authors->first());
    }

    public function testUsernameIsNormalized()
    {
        $author = new PostAuthor('Foo Bar');

        $this->assertSame('foo_bar', $author->username);
    }

    public function testUsernameIsNormalizedWhenCreatedFromArray()
    {
        $author = PostAuthor::create(['username' => 'Foo Bar']);

        $this->assertSame('foo_bar', $author->username);
    }

    public function testUsernameGeneratedFromNameIsNormalized()
    {
        $author = PostAuthor::create(['name' => 'Foo Bar']);

        $this->assertSame('foo_bar', $author->username);
    }

    public function testNameIsCreatedFromUsernameIfNameIsNotSet()
    {
        $author = new PostAuthor('username');

        $this->assertSame('Username', $author->name);
    }

    public function testNameIsCreatedFromComplexUsernameIfNameIsNotSet()
    {
        $author = new PostAuthor('foo_bar');

        $this->assertSame('Foo Bar', $author->name);
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

        $this->assertSame('{"username":"username","name":"Username"}', $author->toJson());
    }

    public function testToArrayMethodSerializesAllData()
    {
        $data = $this->exampleDataWithUsername();

        $author = new PostAuthor(...$data);

        $this->assertSame($data, $author->toArray());
    }

    public function testGetPostsWithNoPosts()
    {
        $author = new PostAuthor('username');

        $this->assertSame([], $author->getPosts()->all());
    }

    public function testGetPostsReturnsAllPostsByAuthor()
    {
        Hyde::pages()->addPage(new MarkdownPost('foo', ['author' => 'username']));
        Hyde::pages()->addPage(new MarkdownPost('bar', ['author' => 'username']));
        Hyde::pages()->addPage(new MarkdownPost('baz', ['author' => 'other']));
        Hyde::pages()->addPage(new MarkdownPost('qux'));

        $author = new PostAuthor('username');

        $this->assertCount(2, $author->getPosts());
        $this->assertSame('username', $author->getPosts()->first()->author->username);
        $this->assertSame('username', $author->getPosts()->last()->author->username);

        $this->assertSame('foo', $author->getPosts()->first()->identifier);
        $this->assertSame('bar', $author->getPosts()->last()->identifier);

        $this->assertEquals($author, $author->getPosts()->first()->author);
        $this->assertEquals($author, $author->getPosts()->last()->author);
    }

    /**
     * @return array{name: string, website: string, bio: string, avatar: string, socials: array{twitter: string, github: string, custom: string}}
     */
    protected function exampleData(): array
    {
        return [
            'name' => 'Mr. Hyde',
            'website' => 'https://HydePHP.com',
            'bio' => 'A mysterious figure. Is he as evil as he seems? And what did he do with Dr. Jekyll?',
            'avatar' => 'mr_hyde.png',
            'socials' => ['twitter' => 'HydeFramework', 'github' => 'hydephp', 'custom' => 'https://example.com'],
        ];
    }

    /**
     * @return array{username: string, name: string, website: string, bio: string, avatar: string, socials: array{twitter: string, github: string, custom: string}}
     */
    protected function exampleDataWithUsername(): array
    {
        return array_merge(['username' => 'mr_hyde'], $this->exampleData());
    }
}
