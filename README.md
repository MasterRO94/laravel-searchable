# Laravel simple FULLTEXT search through multiple Eloquent models 
This is a small Laravel >= 5 package that allows you to make a global search though multiple Eloquent models and get ordered by relevance collection of results.

## Installation

### Step 1: Composer

From the command line, run:

```
composer require masterro/searchable
```

### Step 2: Service Provider

For your Laravel app, open `config/app.php` and, within the `providers` array, append:

```
MasterRO\Searchable\SearchableServiceProvider::class
```

## Usage

Register your search models in AppServiceProvider or create your custom one

```
Searchable::registerModels([
			Deal::class,
			Project::class,
			ContactCompany::class,
			Contact::class,
			Invoice::class,
			Estimate::class

		]);
```

Then you should implement MasterRO\Searchable\SearchableContract by each registered model or it will be skipped and defined `searchable` method

```
public static function searchable(): array
{
    return ['title', 'description'];
}
```

Now you can make search in your controller or where you want

```
public function search(Request $request, Searchable $searchable)
{
    $query = trim($request->get('q'));

    if (mb_strlen($query) < 3) {
        return back()->withInput()->withErrors([
            'search_error' => trans('messages.search_error')
        ]);
    }
    
    return view('search.index')->with('results', $searchable->search($query));
}
```