<?php

namespace MasterRO\Searchable;

use Illuminate\Support\ServiceProvider;

class SearchableServiceProvider extends ServiceProvider
{
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
