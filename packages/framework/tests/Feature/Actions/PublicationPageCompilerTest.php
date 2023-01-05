<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Actions;

use function file_get_contents;
use function file_put_contents;
use Hyde\Framework\Actions\PublicationPageCompiler;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Hyde;
use Hyde\Pages\PublicationPage;
use Hyde\Support\Facades\Render;
use Hyde\Testing\TestCase;
use function json_decode;
use function json_encode;

/**
 * @covers \Hyde\Framework\Actions\PublicationPageCompiler
 */
class PublicationPageCompilerTest extends TestCase
{
    public function test_can_compile_publication_pages()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();

        file_put_contents(Hyde::path('test-publication/detail.blade.php'), 'Detail: {{ $publication->title }}');

        $string = PublicationPageCompiler::call(new PublicationPage('my-publication', type: PublicationType::get('test-publication')));

        $this->assertEquals('Detail: My Publication', $string);
    }

    public function test_can_compile_publication_list_pages()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();

        file_put_contents(Hyde::path('test-publication/my-publication.md'), 'Foo');
        file_put_contents(Hyde::path('test-publication/list.blade.php'), 'List: {{ $publications->first()->title }}');

        $string = PublicationPageCompiler::call(PublicationType::get('test-publication')->getListPage());

        $this->assertEquals('List: My Publication', $string);
    }

    public function test_can_compile_publication_pages_with_registered_view()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();

        $schema = json_decode(file_get_contents(Hyde::path('test-publication/schema.json')));
        $schema->detailTemplate = 'foo';
        file_put_contents(Hyde::path('test-publication/schema.json'), json_encode($schema));
        $this->directory('resources/views');
        $this->file('resources/views/foo.blade.php', 'Registered detail view');

        $publicationPage = new PublicationPage('my-publication', type: PublicationType::get('test-publication'));
        Render::setPage($publicationPage);
        $this->assertEquals('Registered detail view', PublicationPageCompiler::call($publicationPage));
    }

    public function test_can_compile_publication_list_pages_with_registered_view()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();

        $schema = json_decode(file_get_contents(Hyde::path('test-publication/schema.json')));
        $schema->listTemplate = 'foo';
        file_put_contents(Hyde::path('test-publication/schema.json'), json_encode($schema));
        $this->directory('resources/views');
        $this->file('resources/views/foo.blade.php', 'Registered list view');

        $publicationType = PublicationType::get('test-publication');
        $publicationPage = $publicationType->getListPage();
        $this->assertEquals('Registered list view', PublicationPageCompiler::call($publicationPage));
    }

    public function test_can_compile_publication_pages_with_registered_namespaced_view()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();

        $schema = json_decode(file_get_contents(Hyde::path('test-publication/schema.json')));
        $schema->detailTemplate = 'hyde::layouts.publication';
        file_put_contents(Hyde::path('test-publication/schema.json'), json_encode($schema));

        $publicationPage = new PublicationPage('my-publication', type: PublicationType::get('test-publication'));
        Render::setPage($publicationPage);
        $this->assertStringContainsString('My Publication', PublicationPageCompiler::call($publicationPage));
    }

    public function test_can_compile_publication_list_pages_with_registered_namespaced_view()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();
        $this->file('vendor/hyde/framework/resources/views/layouts/test.blade.php', 'Registered list view');

        $schema = json_decode(file_get_contents(Hyde::path('test-publication/schema.json')));
        $schema->listTemplate = 'hyde::layouts.test';
        file_put_contents(Hyde::path('test-publication/schema.json'), json_encode($schema));
        file_put_contents(Hyde::path('test-publication/my-publication.md'), 'Foo');

        $publicationType = PublicationType::get('test-publication');
        $publicationPage = $publicationType->getListPage();
        $this->assertEquals("Registered list view", PublicationPageCompiler::call($publicationPage));
    }

    public function test_with_missing_detail_blade_view()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File [test-publication/detail.blade.php] not found.');

        PublicationPageCompiler::call(new PublicationPage('my-publication', type: PublicationType::get('test-publication')));
    }

    public function test_with_missing_list_blade_view()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File [test-publication/list.blade.php] not found.');

        PublicationPageCompiler::call(PublicationType::get('test-publication')->getListPage());
    }
}
