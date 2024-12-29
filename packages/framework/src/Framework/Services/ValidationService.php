<?php

declare(strict_types=1);

namespace Hyde\Framework\Services;

use Hyde\Hyde;
use Hyde\Facades\Config;
use Hyde\Facades\Features;
use Hyde\Pages\BladePage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\DocumentationPage;
use Hyde\Enums\Feature;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Support\Models\ValidationResult as Result;

use function count;
use function get_class_methods;
use function array_intersect;
use function file_exists;
use function implode;
use function sprintf;
use function str_starts_with;

class ValidationService
{
    /** @return string[] */
    public static function checks(): array
    {
        $service = new self();
        $checks = [];
        foreach (get_class_methods($service) as $method) {
            if (str_starts_with($method, 'check_')) {
                $checks[] = $method;
            }
        }

        return $checks;
    }

    public function run(string $check): Result
    {
        $result = new Result;
        $this->{$check}($result);

        return $result;
    }

    public function check_validators_can_run(Result $result): Result
    {
        // Runs a rather useless check, but which forces the class to load, thus preventing skewed test results
        // as the first test generally takes a little longer to run.
        return $result->pass('Validators can run');
    }

    public function check_site_has_a_404_page(Result $result): Result
    {
        if (file_exists(MarkdownPage::path('404.md'))
            || file_exists(BladePage::path('404.blade.php'))
        ) {
            return $result->pass('Your site has a 404 page');
        }

        return $result->fail('Could not find an 404.md or 404.blade.php file!')
            ->withTip('You can publish the default one using `php hyde publish:views`');
    }

    public function check_site_has_an_index_page(Result $result): Result
    {
        if (file_exists(MarkdownPage::path('index.md'))
            || file_exists(BladePage::path('index.blade.php'))
        ) {
            return $result->pass('Your site has an index page');
        }

        return $result->fail('Could not find an index.md or index.blade.php file!')
            ->withTip('You can publish the one of the built in templates using `php hyde publish:homepage`');
    }

    public function check_documentation_site_has_an_index_page(Result $result): Result
    {
        if (! Features::hasDocumentationPages()) {
            return $result->skip('Does documentation site have an index page?')
                ->withTip('Skipped because: The documentation page feature is disabled in config');
        }

        if (count(DocumentationPage::files()) === 0) {
            return $result->skip('Does documentation site have an index page?')
                ->withTip('Skipped because: There are no documentation pages');
        }

        if (file_exists(DocumentationPage::path('index.md'))) {
            return $result->pass('Your documentation site has an index page');
        }

        if (file_exists(DocumentationPage::path('README.md'))) {
            return $result->fail('Could not find an index.md file in the _docs directory!')
                ->withTip('However, a _docs/readme.md file was found. A suggestion would be to copy the _docs/readme.md to _docs/index.md.');
        }

        return $result->fail('Could not find an index.md file in the _docs directory!');
    }

    public function check_site_has_an_app_css_stylesheet(Result $result): Result
    {
        if (file_exists(MediaFile::outputPath('/app.css')) || file_exists(MediaFile::sourcePath('app.css'))) {
            return $result->pass('Your site has an app.css stylesheet');
        }

        return $result->fail(sprintf('Could not find an app.css file in the %s or %s directory!',
            Hyde::pathToRelative(MediaFile::outputPath()), Hyde::getMediaDirectory()
        ))->withTip('You may need to run `npm run dev`.`');
    }

    public function check_site_has_a_base_url_set(Result $result): Result
    {
        if (Hyde::hasSiteUrl()) {
            return $result->pass('Your site has a base URL set')
                ->withTip('This will allow Hyde to generate canonical URLs, sitemaps, RSS feeds, and more.');
        }

        return $result->fail('Could not find a site URL in the config or .env file!')
            ->withTip('Adding it may improve SEO as it allows Hyde to generate canonical URLs, sitemaps, and RSS feeds');
    }

    public function check_a_torchlight_api_token_is_set(Result $result): Result
    {
        if (! Features::has(Feature::Torchlight)) {
            return $result->skip('Check a Torchlight API token is set')
                ->withTip('Torchlight is an API for code syntax highlighting. You can enable it in the Hyde config.');
        }

        if (Config::getNullableString('torchlight.token') !== null) {
            return $result->pass('Your site has a Torchlight API token set');
        }

        return $result->fail('Torchlight is enabled in the config, but an API token could not be found in the .env file!')
            ->withTip('Torchlight is an API for code syntax highlighting. You can get a free token at torchlight.dev.');
    }

    public function check_for_conflicts_between_blade_and_markdown_pages(Result $result): Result
    {
        $conflicts = array_intersect(
            MarkdownPage::files(),
            BladePage::files()
        );

        if (count($conflicts)) {
            return $result->fail('Found naming conflicts between Markdown and Blade files: '.implode(', ', $conflicts))
                ->withTip('This may cause on of them being immediately overwritten by the other.');
        }

        return $result->pass('No naming conflicts found between Blade and Markdown pages');
    }
}
