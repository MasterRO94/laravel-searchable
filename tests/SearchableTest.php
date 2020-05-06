<?php

declare(strict_types=1);

namespace MasterRO\Searchable\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MasterRO\Searchable\Searchable;
use MasterRO\Searchable\SearchableServiceProvider;
use MasterRO\Searchable\Tests\Models\Article;
use MasterRO\Searchable\Tests\Models\Model;
use MasterRO\Searchable\Tests\Models\Post;
use MasterRO\Searchable\Tests\Providers\TestSearchableServiceProvider;
use Orchestra\Testbench\TestCase;

/**
 * Class SearchableTest
 *
 * @package MasterRO\Searchable\Tests
 */
class SearchableTest extends TestCase
{
    /**
     * @var Searchable
     */
    protected $searchable;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->withFactories(__DIR__.'/database/factories');
        $this->createRecords();

        $this->searchable = $this->app->make(Searchable::class);
    }

    /**
     * @test
     */
    public function it_can_search_by_title()
    {
        $result = $this->searchable->search('Modi minus');
        $master = Article::whereTitle('Modi minus.')->first();

        $this->assertTrue($master->is($result->first()));
    }

    /**
     * @test
     */
    public function it_can_search_by_description()
    {
        $result = $this->searchable->search('Velit consectetur perspiciatis');
        $master = Article::whereDescription('Velit asperiores sed consectetur perspiciatis.')->first();

        $this->assertTrue($master->is($result->first()));
    }

    /**
     * @test
     */
    public function it_searches_through_all_models()
    {
        $result = $this->searchable->search('consequatur');

        $this->assertCount(
            2,
            $result->values()->map(function (Model $model) {
                return class_basename($model);
            })->unique()
        );
    }

    /**
     * @test
     */
    public function it_can_search_through_specified_model()
    {
        foreach ([Post::class, Article::class] as $model) {
            $result = $this->searchable->searchModel($model, 'consequatur');

            $this->assertEquals(
                $result->values()->filter(function (Model $m) use ($model) {
                    return get_class($m) === $model;
                })->count(),
                $result->count()
            );
        }
    }

    /**
     * @test
     */
    public function it_can_filter_results_with_predefined_query()
    {
        $result = $this->searchable->search('quia est ipsa molestiae hic');

        $this->assertEmpty(
            $result->filter(function (Model $model) {
                return $model->title === 'Quia est ipsa molestiae hic.' &&
                    Carbon::parse($model->published_at)->is('2019-01-01');
            })
        );
    }

    /**
     * @test
     */
    public function it_can_filter_results_with_custom_query()
    {
        $result = $this->searchable
            ->withFilter(function (Builder $query) {
                return $query->getModel() instanceof Post
                    ? $query
                    : $query->where('description', '!=', 'Doloremque iure sequi quos sequi consequatur.');
            })
            ->search('Dolorem');

        $this->assertEmpty($result->where('title', 'Est occaecati sit.'));
        $this->assertNotEmpty($result->where('title', 'Dolorem quos.'));

        $result = $this->searchable->search('Dolorem');

        $this->assertNotEmpty($result->where('title', 'Est occaecati sit.'));
    }

    /**
     * @test
     */
    public function it_can_skip_all_predefined_model_filter_queries()
    {
        $callback = function (Model $model) {
            return $model->title === 'Quia est ipsa molestiae hic.' &&
                Carbon::parse($model->published_at)->is('2019-01-01');
        };

        $result = $this->searchable->withoutModelFilters()->search('quia est ipsa molestiae hic');

        $this->assertNotEmpty($result->filter($callback));

        $result = $this->searchable->search('quia est ipsa molestiae hic');

        $this->assertEmpty($result->filter($callback));
    }

    /**
     * @test
     */
    public function it_can_skip_specified_predefined_model_filter_queries()
    {
        $callback = function (Model $model) {
            return $model->title === 'Quia est ipsa molestiae hic.' &&
                Carbon::parse($model->published_at)->is('2019-01-01');
        };

        $result = $this->searchable
            ->withoutModelFilters(Post::class)
            ->search('quia est ipsa molestiae hic');

        $this->assertNotEmpty($result->filter($callback));

        $result = $this->searchable
            ->withoutModelFilters(Article::class)
            ->search('quia est ipsa molestiae hic');

        $this->assertEmpty($result->filter($callback));

        $result = $this->searchable
            ->withoutModelFilters([Article::class, Post::class])
            ->search('quia est ipsa molestiae hic');

        $this->assertNotEmpty($result->filter($callback));
    }

    /**
     * @test
     */
    public function it_can_eager_load_relations()
    {
        $result = $this->searchable
            ->with([Article::class => 'author'])
            ->searchModel(Article::class, 'consequatur');

        $this->assertTrue($result->first()->relationLoaded('author'));

        $result = $this->searchable
            ->searchModel(Article::class, 'consequatur');

        $this->assertFalse($result->first()->relationLoaded('author'));

        $result = $this->searchable
            ->with([
                Article::class => [
                    'author' => function ($query) {
                        return $query->where('id', '>', 1);
                    },
                ],
            ])
            ->searchModel(Article::class, 'consequatur');

        $this->assertTrue($result->first()->relationLoaded('author'));
    }

    /**
     * Get Package Providers
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array|string[]
     */
    protected function getPackageProviders($app)
    {
        return [
            SearchableServiceProvider::class,
            TestSearchableServiceProvider::class,
        ];
    }

    protected function createRecords()
    {
        foreach ($this->dbData() as $model => $data) {
            foreach ($data as $record) {
                factory($model)->create($record);
            }
        }
    }

    /**
     * Test Db Data
     *
     * @return \string[][][]
     */
    protected function dbData()
    {
        return [
            Post::class    => [
                [
                    'title'        => 'Quia est ipsa molestiae hic.',
                    'description'  => 'Not published. Eum veniam cum ut et aut rerum.',
                    'published_at' => '2019-01-01',
                ],
                [
                    'title'        => 'Quia est ipsa molestiae hic.',
                    'description'  => 'Libero velit id.',
                    'published_at' => '2020-02-02',
                ],
                [
                    'title'        => 'Animi quo hic corrupti itaque consequatur.',
                    'description'  => 'Aspernatur adipisci.',
                    'published_at' => '2020-01-01',
                ],
                [
                    'title'        => 'Harum quam quo.',
                    'description'  => 'Perspiciatis quos et et ut. Sapiente impedit aperiam sed qui.',
                    'published_at' => '2019-02-02',
                ],
                [
                    'title'        => 'Autem optio nulla.',
                    'description'  => 'Eum quia fuga laborum. Sed sunt illum harum et quia.',
                    'published_at' => '2020-05-05',
                ],
            ],
            Article::class => [
                [
                    'title'       => 'Et pariatur ullam.',
                    'description' => 'Velit asperiores sed consectetur perspiciatis.',
                ],
                [
                    'title'       => 'Modi minus.',
                    'description' => 'Quaerat ex et aperiam dolores cum voluptatum natus.',
                ],
                [
                    'title'       => 'In quos expedita.',
                    'description' => 'Laboriosam eum unde quia perspiciatis est.',
                ],
                [
                    'title'       => 'Est occaecati sit.',
                    'description' => 'Doloremque iure sequi quos sequi consequatur.',
                ],
                [
                    'title'       => 'Dolorem quos.',
                    'description' => 'Dolorem dolorem aut occaecati repellendus rerum et.',
                ],
            ],
        ];
    }
}
