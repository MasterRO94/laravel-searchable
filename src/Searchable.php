<?php

namespace MasterRO\Searchable;

use DB;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class Searchable
 *
 * Simple fulltext search through Eloquent models
 *
 * @package App\Modules
 */
class Searchable
{
	/**
	 * @var Collection
	 */
	protected $total_results;

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
	protected $per_page = 15;


	/**
	 * Gel collection of models that should be searched
	 * @return array
	 */
	public static function searchable(): array
	{
		return static::$models;
	}


	/**
	 * @param array $models
	 */
	public static function registerModels(array $models)
	{
		static::$models = $models;
	}


	/**
	 * @param $q
	 * @param int $per_page
	 * @return Searchable|LengthAwarePaginator
	 */
	public function search($q, $per_page = 15)
	{
		$this->page = request('page') ?? 1;
		$this->per_page = $per_page;
		$q = $this->clearQuery($q);

		if (mb_strlen($q) < 3) {
			throw new TooShortQueryException("Filtered search query must be at least 3 characters.");
		}

		foreach (static::searchable() as $model_class) {
			$model = new $model_class;

			if (!is_a($model, SearchableContract::class)) continue;

			$this->getResults(
				$model,
				$this->getQuery($model, $q)
			);
		}

		$this->total_results = collect($this->total_results)
			->sortByDesc('score')
			->slice(($this->page - 1) * $per_page, $per_page);

		return new LengthAwarePaginator($this->total_results, $this->total, $per_page, $this->page);
	}


	/**
	 * @param $model
	 * @param $query
	 * @return Searchable|Collection
	 */
	protected function getResults($model, $query)
	{
		$results = collect(DB::select($query))->map(function ($item) use ($model) {
			return (new $model)->forceFill((array)$item);
		});

		if ($results->count()) {
			$this->total += $results->first()->total;
			$this->total_results = $results->merge($this->total_results);
		}

		return $results;
	}


	/**
	 * @param $model
	 * @param $q
	 * @return string
	 */
	protected function getQuery($model, $q)
	{
		if (method_exists($model, 'getSearchQuery')) {
			return $model->getSearchQuery($model, $q, $this->per_page);
		}

		if (method_exists($model, 'filterSearchResults')) {
			$ids = $model->filterSearchResults($model->query())->pluck('id');
		}

		$fields = $model::searchable();
		$fields_str = implode(', ', $fields);

		$sql = "SELECT *, 
						MATCH ($fields_str) AGAINST ('$q' IN BOOLEAN MODE) as score,
						(
							SELECT COUNT(*) FROM {$model->getTable()} WHERE MATCH ($fields_str) AGAINST ('$q' IN BOOLEAN MODE)
						) as total
							FROM {$model->getTable()} 
								WHERE MATCH ($fields_str) AGAINST ('$q' IN BOOLEAN MODE)";

		if (isset($ids) && $ids->count()) {
			$sql = $this->addIdsFilter($ids, $sql);
		}

		$sql .= ' LIMIT ' . $this->per_page * count(self::$models);

		return $sql;
	}


	/**
	 * @param $ids
	 * @param $sql
	 * @return string
	 */
	protected function addIdsFilter($ids, $sql): string
	{
		$first_loop = true;
		$sql .= ' AND (id IN ';
		foreach ($ids->chunk(100) as $idSet) {
			$ids_str = implode(', ', $idSet->all());
			if ($first_loop) {
				$sql .= "($ids_str)";
				$first_loop = false;
				continue;
			}

			$sql .= " OR id IN ($ids_str)";
		}
		$sql .= ')';

		return $sql;
	}


	/**
	 * @param $q
	 * @return mixed
	 */
	protected function clearQuery($q)
	{
		// Remove duplicate special chars
		$q = preg_replace('/\-+/', '-', $q);
		$q = preg_replace('/\++/', '+', $q);
		$q = preg_replace('/\*+/', '*', $q);

		//remove special chars
		$q = preg_replace('/(((\-|\+|\*)(\-|\+|\*)+)|(\-|\+)\s)|([\~\@\"\<\>\(\)])/', ' ', $q);

		// remove duplicate spaces
		$q = preg_replace('/\s+/', ' ', $q);

		$q = trim(rtrim($q, '*-+'));

		return "$q*";
	}

}