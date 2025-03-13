<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\Event;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_upload_a_roster_file()
    {
        $file = UploadedFile::fake()->create('roster.html');

        $response = $this->postJson('/api/upload-roster', [
            'roster' => $file
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Roster uploaded successfully']);
    }

    /** @test */
    public function it_can_fetch_events_between_dates()
    {
        Event::factory()->create(['date' => '2022-01-15']);
        Event::factory()->create(['date' => '2022-01-20']);

        $response = $this->getJson('/api/events-between?start=2022-01-14&end=2022-01-21');
        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    /** @test */
    public function it_can_fetch_flights_for_next_week()
    {
        Event::factory()->create(['date' => '2022-01-15', 'type' => 'FLT']);
        Event::factory()->create(['date' => '2022-01-23', 'type' => 'FLT']);

        $response = $this->getJson('/api/flights-next-week');
        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }

    /** @test */
    public function it_can_fetch_standby_events_for_next_week()
    {
        Event::factory()->create(['date' => '2022-01-15', 'type' => 'SBY']);
        Event::factory()->create(['date' => '2022-01-23', 'type' => 'SBY']);

        $response = $this->getJson('/api/standby-next-week');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }

    /** @test */
    public function it_can_fetch_flights_from_a_specific_location()
    {
        Event::factory()->create(['type' => 'FLT', 'departure' => 'JFK']);
        Event::factory()->create(['type' => 'FLT', 'departure' => 'LAX']);

        $response = $this->getJson('/api/flights/from/JFK');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }
}
