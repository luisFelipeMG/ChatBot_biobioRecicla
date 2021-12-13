<?php

use Faker\Generator as Faker;

$factory->define(App\Contact::class, function (Faker $faker) {
	$name = $faker->name;
	$phone = $faker->phoneNumber;
	$mail = $faker->safeEmail;
    return [
        'name' => $name,
        'phone' => $phone,
        'mail' => $mail
    ];
});
