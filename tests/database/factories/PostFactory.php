<?php

use Faker\Generator as Faker;
use MasterRO\Searchable\Tests\Models\Post;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Post::class, function (Faker $faker) {
    return [
        'title'        => $faker->text(63),
        'description'  => $faker->text(20),
        'published_at' => $faker->dateTimeBetween('-2 years', '+2 years'),
        'created_at'   => $faker->dateTime,
        'updated_at'   => $faker->dateTime,
    ];
});
