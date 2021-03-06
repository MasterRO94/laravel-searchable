<?php

declare(strict_types=1);

namespace MasterRO\Searchable;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Simple fulltext search through Eloquent models.
 *
 * @package MasterRO\Searchable
 */
class Searchable
{
    /**
     * @var Collection
     */
    protected $totalResults;

    /**
     * @var int
     */
    protected $total = 0;

    /**
     * @var array
     */
    protected static $models = [];

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $perPage = 15;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var Closure
     */
    protected $filter;

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @var bool
     */
    protected $withoutFilters = [];

    /**
     * Searchable constructor.
     * Define configs and globals
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Gel collection of models that should be searched
     *
     * @return array
     */
    public static function searchable(): array
    {
        return static::$models;
    }

    /**
     * @param array $models
     */
    public static function registerModels(array $models): void
    {
        static::$models = $models;
    }

    /**
     * Init
     *
     */
    protected function init(): void
    {
        $this->config = config('searchable', []);
        $this->config['use_boolean_mode'] = $this->config['use_boolean_mode'] ?? true;
        $this->config['use_query_expansion'] = $this->config['use_query_expansion'] ?? true;
        $this->config['allow_operators'] = $this->config['allow_operators'] ?? true;

        $this->mode = $this->config['use_boolean_mode'] ? 'IN BOOLEAN MODE' : 'IN NATURAL LANGUAGE MODE';

        if (!$this->config['use_boolean_mode'] && $this->config['use_query_expansion']) {
            $this->mode .= ' WITH QUERY EXPANSION';
        }
    }

    /**
     * With Filter
     *
     * @param Closure|null $filter
     *
     * @return Searchable
     */
    public function withFilter(?Closure $filter): Searchable
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Without Model Filters
     *
     * @param array|string|null $models
     *
     * @return $this
     */
    public function withoutModelFilters($models = null): Searchable
    {
        $models = $models ? Arr::wrap($models) : static::searchable();

        $this->withoutFilters = $models;

        return $this;
    }

    /**
     * With
     *
     * @param array $with
     *
     * @return Searchable
     */
    public function with(array $with = []): Searchable
    {
        $this->with = $with;

        return $this;
    }

    /**
     * Clear Filters
     *
     * @return Searchable
     */
    public function clearExtras(): Searchable
    {
        $this->filter = null;
        $this->withoutFilters = [];
        $this->with = [];

        return $this;
    }

    /**
     * Search
     *
     * @param string $query
     * @param int $perPage
     * @param Request|null $request
     *
     * @return LengthAwarePaginator
     * @throws TooShortQueryException
     */
    public function search(string $query, int $perPage = 15, Request $request = null): LengthAwarePaginator
    {
        $request = $request ?? request();

        $this->page = $request->input('page') ?? 1;
        $this->perPage = $perPage;
        $this->totalResults = collect();
        $query = $this->clearQuery($query);

        if (mb_strlen($query) < 3) {
            throw new TooShortQueryException('Filtered search query must be at least 3 characters.');
        }

        foreach (static::searchable() as $modelClass) {
            $model = new $modelClass;

            if (!is_a($model, SearchableContract::class)) {
                continue;
            }

            $this->getResults(
                $model,
                $this->getQuery($model, $query)
            );
        }

        $this->clearExtras();

        if ($this->totalResults instanceof LengthAwarePaginator) {
            return $this->totalResults;
        }

        $this->totalResults = $this->totalResults
            ->sortByDesc('score')
            ->slice(($this->page - 1) * $perPage, $perPage)
            ->values();

        return new LengthAwarePaginator($this->totalResults, $this->total, $perPage, $this->page);
    }

    /**
     * Search Single Model
     *
     * @param string|string[]|Model|Model[] $model
     * @param string $query
     * @param int $perPage
     * @param Request|null $request
     *
     * @return LengthAwarePaginator
     * @throws TooShortQueryException
     */
    public function searchModel($model, string $query, int $perPage = 15, Request $request = null)
    {
        $models = collect(Arr::wrap($model))
            ->map(function ($model) {
                return is_string($model) ? $model : get_class($model);
            })
            ->all();

        $registeredModels = static::searchable();
        static::registerModels($models);

        $result = $this->search($query, $perPage, $request);

        static::registerModels($registeredModels);

        return $result;
    }

    /**
     * Get Results
     *
     * @param Model $model
     * @param Builder|null $query
     *
     * @return LengthAwarePaginator|Collection
     */
    protected function getResults(Model $model, ?Builder $query)
    {
        $results = $query
            ? (1 === count(static::searchable())
                ? $query->paginate($this->perPage)
                : $query->get())
            : collect();

        if (1 !== count(static::searchable())) {
            $this->total += $results->count();
            $this->totalResults = $results->merge($this->totalResults);
        } else {
            $this->totalResults = $results;
        }

        return $results;
    }

    /**
     * Get Query
     *
     * @param Model $model
     * @param string $q
     *
     * @return Builder|null
     */
    protected function getQuery(Model $model, string $q): ?Builder
    {
        if (method_exists($model, 'getSearchQuery')) {
            return $model->getSearchQuery($model, $q, $this->perPage);
        }

        $fields = $model::searchable();
        $fieldsStr = implode(', ', $fields);

        $builder = $model
            ->selectRaw("*, MATCH ({$fieldsStr}) AGAINST (? $this->mode) as score", ["*{$q}*"])
            ->whereRaw("MATCH ({$fieldsStr}) AGAINST (? $this->mode)", ["*{$q}*"])
            ->orderBy('score', 'desc');

        if (!empty($this->with[get_class($model)])) {
            $builder->with($this->with[get_class($model)]);
        }

        if (!in_array(get_class($model), $this->withoutFilters) &&
            method_exists($model, 'filterSearchResults')
        ) {
            $builder = $model->filterSearchResults($builder);

            if (!$builder) {
                return null;
            }
        }

        if (!empty($this->filter)) {
            $builder = call_user_func($this->filter, $builder);

            if (!$builder) {
                return null;
            }
        }

        if (1 === count(static::searchable())) {
            return $builder;
        }

        return $builder->take($this->perPage * count(static::$models) * $this->page);
    }

    /**
     * Clear Query
     *
     * @param $q
     *
     * @return string
     */
    protected function clearQuery($q): string
    {
        if ($this->config['use_boolean_mode'] && $this->config['allow_operators']) {
            // Remove duplicate special chars
            $q = preg_replace('/\-+/', '-', $q);
            $q = preg_replace('/\++/', '+', $q);
            $q = preg_replace('/\*+/', '*', $q);

            //remove special chars
            $q = preg_replace('/(((\-|\+|\*)(\-|\+|\*)+)|(\-|\+)\s)|([\~\@\"\<\>\(\)])/', ' ', $q);
        } else {
            //remove special chars
            $q = preg_replace('/[\+\-\"\<\>\(\)\~\*\@]/', ' ', $q);
        }

        // remove duplicate spaces
        $q = preg_replace('/\s+/', ' ', $q);

        $q = trim(trim($q, '*-+'));

        return $q;
    }
}
