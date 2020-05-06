# Laravel simple FULLTEXT search through multiple Eloquent models 
This is a small Laravel package that allows you to make a global search though multiple Eloquent models and get ordered by relevance collection of results.
It uses MATCH AGAINST MySQL queries.

## Installation

### Step 1: Composer

From the command line, run:

```
composer require masterro/laravel-searchable
```

### Step 2: Service Provider

If you not use laravel package **auto-discovery** you need to register the service provider, open `config/app.php` and, within the `providers` array, append:

```php
MasterRO\Searchable\SearchableServiceProvider::class
```

## Usage

Register your search models in AppServiceProvider or create your custom one

```php
Searchable::registerModels([
    Post::class,
    Article::class,
    User::class,
]);
```

Then you should implement MasterRO\Searchable\SearchableContract by each registered model, or it will be skipped and define `searchable` method

```php
public static function searchable(): array
{
    return ['title', 'description'];
}
```

**Make sure you added fulltext indicies to your tables**
```php
public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->longText('description');
            $table->dateTime('published_at')->nullable();

            $table->timestamps();
        });

        DB::statement('ALTER TABLE posts ADD FULLTEXT search(title, description)');
    }
```

Now you can make search in your controller or where you want

```php
public function search(Request $request, Searchable $searchable)
{
    $query = trim($request->input('q'));

    if (mb_strlen($query) < 3) {
        return back()->withInput()->withErrors([
            'search_error' => __('messages.search_error')
        ]);
    }
    
    return view('search.index')->with('results', $searchable->search($query));
}
```

### Filtering

#### Model filter
Search results can be filtered by adding the `filterSearchResults()` in your model (like Eloquent global scope)
```php
class User extends Model implements SearchableContract
{
    public function posts() {
        return $this->hasMany(Post::class);
    }

    public function filterSearchResults($query) {
        return $query->whereHas('posts', function ($query) {
            $query->where('is_published', true);
        });
    }
}
```
> The example code above will filter the search results and will only return users which have published posts.


#### Runtime filter
Search results can be filtered by adding custom filter callback 

```php
$result = $this->searchable
    ->withFilter(function (Builder $query) {
        return $query->getModel() instanceof Post
            ? $query
            : $query->where('description', '!=', 'Doloremque iure sequi quos sequi consequatur.');
    })
    ->search('Dolorem');
```


#### Disabling model filter
Model filters can be skipped in runtime like Eloquent global scopes. 

```php
$result = $this->searchable->withoutModelFilters()->search('quia est ipsa molestiae hic');
```

You can specify models to skip filters for
```php
$result = $this->searchable
    ->withoutModelFilters(Post::class)
    ->search('quia est ipsa molestiae hic');
```

or

```php
$result = $this->searchable
    ->withoutModelFilters([Article::class, Post::class])
    ->search('quia est ipsa molestiae hic');
```