<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'type' => $this->faker->randomElement(['DO', 'SBY', 'FLT', 'CI', 'CO', 'UNK']),
            'flight_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{2,4}'),
            'departure' => $this->faker->city(),
            'arrival' => $this->faker->city(),
            'std_utc' => $this->faker->dateTime(),
            'sta_utc' => $this->faker->dateTime(),
            'check_in_utc' => $this->faker->optional()->dateTime(),
            'check_out_utc' => $this->faker->optional()->dateTime(),
        ];
    }
}
