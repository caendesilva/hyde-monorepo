<?php

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Models\Author;
use Hyde\Framework\Models\FrontMatter;
use Hyde\Framework\Models\Image;
use Hyde\Framework\Models\Pages\MarkdownPost;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Framework\Models\Pages\MarkdownPost
 * @covers \Hyde\Framework\Concerns\FrontMatter\Schemas\BlogPostSchema
 */
class MarkdownPostTest extends TestCase
{
    public function test_constructor_can_create_a_new_author_instance_from_username_string()
    {
        $post = new MarkdownPost(matter: FrontMatter::fromArray([
            'author' => 'John Doe',
        ]));

        $this->assertInstanceOf(Author::class, $post->author);
        $this->assertEquals('John Doe', $post->author->username);
        $this->assertNull($post->author->name);
        $this->assertNull($post->author->website);
    }

    public function test_constructor_can_create_a_new_author_instance_from_user_array()
    {
        $post = new MarkdownPost(matter: FrontMatter::fromArray([
            'author' => [
                'username' => 'john_doe',
                'name' => 'John Doe',
                'website' => 'https://example.com',
            ]
        ]));

        $this->assertInstanceOf(Author::class, $post->author);
        $this->assertEquals('john_doe', $post->author->username);
        $this->assertEquals('John Doe', $post->author->name);
        $this->assertEquals('https://example.com', $post->author->website);
    }

    public function test_constructor_can_create_a_new_image_instance_from_a_string()
    {
        $post = new MarkdownPost(matter: FrontMatter::fromArray([
            'image' => 'https://example.com/image.jpg',
        ]));

        $this->assertInstanceOf(Image::class, $post->image);
        $this->assertEquals('https://example.com/image.jpg', $post->image->uri);
    }

    public function test_constructor_can_create_a_new_image_instance_from_an_array()
    {
        $post = new MarkdownPost(matter: FrontMatter::fromArray([
            'image' => [
                'uri' => 'https://example.com/image.jpg',
            ],
        ]));

        $this->assertInstanceOf(Image::class, $post->image);
        $this->assertEquals('https://example.com/image.jpg', $post->image->uri);
    }
}
