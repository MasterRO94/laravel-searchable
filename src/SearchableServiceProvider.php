<?php

namespace MasterRO\Searchable;

use Illuminate\Support\ServiceProvider;

class SearchableServiceProvider extends ServiceProvider
{
	/**
	 * Boot the provider
	 */
	public function boot()
	{
		$this->mergeConfigFrom(
			__DIR__ . '/config/searchable.php', 'searchable'
		);
	}


	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton(Searchable::class);
	}
}
