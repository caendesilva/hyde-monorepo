<?php

declare(strict_types=1);

namespace Hyde\RealtimeCompiler\Routing;

use Desilva\Microserve\Request;
use Desilva\Microserve\Response;
use Hyde\Foundation\Facades\Routes;
use Hyde\Pages\Concerns\BaseMarkdownPage;
use Hyde\Framework\Actions\StaticPageBuilder;
use Hyde\RealtimeCompiler\Http\LiveEditController;
use Hyde\Framework\Exceptions\RouteNotFoundException;
use Hyde\Pages\Concerns\HydePage;
use Hyde\RealtimeCompiler\Concerns\SendsErrorResponses;
use Hyde\RealtimeCompiler\Http\DashboardController;
use Desilva\Microserve\HtmlResponse;
use Hyde\Hyde;

/**
 * Handle routing for a web page request.
 */
class PageRouter
{
    use SendsErrorResponses;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function handlePageRequest(): Response
    {
        return new HtmlResponse(200, 'OK', [
            'body' => $this->getHtml($this->getPageFromRoute()),
        ]);
    }

    protected function normalizePath(string $path): string
    {
        // If URL ends in .html, strip it
        if (str_ends_with($path, '.html')) {
            $path = substr($path, 0, -5);
        }

        // If the path is empty, serve the index file
        if (empty($path) || $path == '/') {
            $path = '/index';
        }

        return ltrim($path, '/');
    }

    protected function getHtml(HydePage $page): string
    {
        if ($page->identifier === 'index' && DashboardController::enabled()) {
            return DashboardController::renderIndexPage($page);
        }

        if (config('hyde.server.save_preview')) {
            $contents = file_get_contents(StaticPageBuilder::handle($page));
        } else {
            Hyde::shareViewData($page);

            $contents = $page->compile();
        }

        if ($page instanceof BaseMarkdownPage && LiveEditController::enabled()) {
            $contents = LiveEditController::injectLiveEditScript($contents);
        }

        return $contents;
    }

    public static function handle(Request $request): Response
    {
        return (new self($request))->handlePageRequest();
    }

    protected function getPageFromRoute(): HydePage
    {
        try {
            return Routes::getOrFail($this->normalizePath($this->request->path))->getPage();
        } catch (RouteNotFoundException $exception) {
            $index = Routes::get($this->normalizePath($this->request->path).'/index');

            if ($index) {
                return $index->getPage();
            }

            throw $exception;
        }
    }
}
