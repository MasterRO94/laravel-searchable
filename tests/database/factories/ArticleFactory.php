<?php

use Faker\Generator as Faker;
use MasterRO\Searchable\Tests\Models\Article;
use MasterRO\Searchable\Tests\Models\User;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Article::class, function (Faker $faker) {
    return [
        'title'       => $faker->text(63),
        'description' => $faker->text(20),
        'created_at'  => $faker->dateTime,
        'updated_at'  => $faker->dateTime,
        'created_by'  => factory(User::class)->create(),
    ];
});
