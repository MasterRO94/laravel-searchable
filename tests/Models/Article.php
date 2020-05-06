<?php

namespace MasterRO\Searchable\Tests\Models;

use MasterRO\Searchable\SearchableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Article
 *
 * @package MasterRO\Searchable\Tests\Models
 * @property int $id
 * @property string $title
 * @property string $description
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \MasterRO\Searchable\Tests\Models\User $author
 * @property-read string $type
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Article newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Article newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Article query()
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Article whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Article whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Article whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Article whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Article whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\Article whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Article extends Model implements SearchableContract
{
    protected $fillable = ['title', 'description', 'created_by'];

    /**
     * Author
     *
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Searchable
     *
     * @return array|string[]
     */
    public static function searchable(): array
    {
        return ['title', 'description'];
    }
}
