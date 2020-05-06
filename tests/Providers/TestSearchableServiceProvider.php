<?php

declare(strict_types=1);

namespace MasterRO\Searchable\Tests\Providers;

use Illuminate\Support\ServiceProvider;
use MasterRO\Searchable\Searchable;
use MasterRO\Searchable\Tests\Models\Article;
use MasterRO\Searchable\Tests\Models\Post;

/**
 * Class TestSearchableServiceProvider
 *
 * @package MasterRO\Searchable\Tests\Providers
 */
class TestSearchableServiceProvider extends ServiceProvider
{
    public function register()
    {
        Searchable::registerModels([
            Post::class,
            Article::class,
        ]);
    }
}
