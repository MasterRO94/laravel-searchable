<?php

namespace MasterRO\Searchable\Tests\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * MasterRO\Searchable\Tests\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description
 * @property-read string $type
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\MasterRO\Searchable\Tests\Models\User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
	use Authenticatable, Authorizable, CanResetPassword, Notifiable, MustVerifyEmail;

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var string[]
	 */
	protected $hidden = [
		'password', 'remember_token',
	];


	/**
	 * @param string $email
	 *
	 * @return \Illuminate\Database\Eloquent\Model|null|static
	 */
	public static function findByEmail($email)
	{
		return static::where(compact('email'))->first();
	}

}
