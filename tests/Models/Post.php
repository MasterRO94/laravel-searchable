<?php

namespace MasterRO\Searchable\Tests\Models;

use MasterRO\Searchable\SearchableContract;

/**
 * Class Post
 *
 * @package MasterRO\Searchable\Tests\Models
 * @property int $id
 * @property string $title
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $type
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Post query()
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Post whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Post whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Post wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Post whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Post whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Post extends Model implements SearchableContract
{
    protected $fillable = ['title', 'description', 'published_at'];

    protected $dates = ['published_at'];

    /**
     * Searchable
     *
     * @return array|string[]
     */
    public static function searchable(): array
    {
        return ['title', 'description'];
    }

    /**
     * Filter Search Results
     *
     * @param $query
     *
     * @return mixed
     */
    public function filterSearchResults($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '>', '2019-12-31 23:59:59');
    }
}
