<?php

declare(strict_types=1);

namespace Hyde\Framework\Providers;

use function config;
use Illuminate\Translation\TranslationServiceProvider as IlluminateTranslationServiceProvider;
use function is_dir;
use function lang_path;

class TranslationServiceProvider extends IlluminateTranslationServiceProvider
{
    public function register(): void
    {
        parent::register();

        if (! is_dir(lang_path())) {
            $this->app->useLangPath(__DIR__.'/../../../../publications/resources/lang');
        }

        config([
            'app.locale' => config('app.locale', 'en'),
            'app.fallback_locale' => config('app.fallback_locale', 'en'),
        ]);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../../../publications/resources/lang/en/validation.php', 'validation');
    }
}
