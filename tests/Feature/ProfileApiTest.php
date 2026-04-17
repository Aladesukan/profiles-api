<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\Profile;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake APIs
        Http::fake([
            'https://api.genderize.io*' => Http::response([
                'gender' => 'female',
                'probability' => 0.99,
                'count' => 1000
            ], 200),

            'https://api.agify.io*' => Http::response([
                'age' => 25
            ], 200),

            'https://api.nationalize.io*' => Http::response([
                'country' => [
                    ['country_id' => 'NG', 'probability' => 0.9]
                ]
            ], 200),
        ]);
    }

    /** @test */
    public function it_creates_profile_successfully()
    {
        $response = $this->postJson('/api/profiles', [
            'name' => 'ella'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    /** @test */
    public function it_returns_existing_profile_if_duplicate()
    {
        $this->postJson('/api/profiles', ['name' => 'ella']);

        $response = $this->postJson('/api/profiles', ['name' => 'ella']);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Profile already exists'
            ]);
    }

    /** @test */
    public function it_returns_400_for_missing_name()
    {
        $response = $this->postJson('/api/profiles', []);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_handles_genderize_failure()
{
    Http::fake([
        'https://api.genderize.io*' => Http::response([
            'gender' => null,
            'probability' => 0,
            'count' => 0
        ], 200),

        'https://api.agify.io*' => Http::response([
            'age' => 30
        ], 200),

        'https://api.nationalize.io*' => Http::response([
            'country' => [
                ['country_id' => 'US', 'probability' => 0.9]
            ]
        ], 200),
    ]);

    $response = $this->postJson('/api/profiles', [
        'name' => 'John'
    ]);

    $response->assertStatus(502)
        ->assertJson([
            'status' => 'error'
        ]);
}
    /** @test */
    public function it_gets_all_profiles()
    {
        // create manually via API
        $this->postJson('/api/profiles', ['name' => 'john']);
        $this->postJson('/api/profiles', ['name' => 'mary']);

        $response = $this->getJson('/api/profiles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'count',
                'data'
            ]);
    }

    public function it_handles_agify_failure()
{
    Http::fake([
        'https://api.genderize.io*' => Http::response([
            'gender' => 'female',
            'probability' => 0.99,
            'count' => 1000
        ], 200),

        'https://api.agify.io*' => Http::response([
            'age' => null
        ], 200),

        'https://api.nationalize.io*' => Http::response([
            'country' => [
                ['country_id' => 'NG', 'probability' => 0.9]
            ]
        ], 200),
    ]);

    $response = $this->postJson('/api/profiles', [
        'name' => 'testname'
    ]);

    $response->assertStatus(502)
        ->assertJson([
            'status' => 'error'
        ]);
}

public function it_handles_nationalize_failure()
{
    Http::fake([
        'https://api.genderize.io*' => Http::response([
            'gender' => 'female',
            'probability' => 0.99,
            'count' => 1000
        ], 200),

        'https://api.agify.io*' => Http::response([
            'age' => 25
        ], 200),

        'https://api.nationalize.io*' => Http::response([
            'country' => []
        ], 200),
    ]);

    $response = $this->postJson('/api/profiles', [
        'name' => 'testname'
    ]);

    $response->assertStatus(502)
        ->assertJson([
            'status' => 'error'
        ]);
}


    /** @test */
    public function it_filters_by_gender()
    {
        $this->postJson('/api/profiles', ['name' => 'john']);
        $this->postJson('/api/profiles', ['name' => 'mary']);

        $response = $this->getJson('/api/profiles?gender=female');

        $response->assertStatus(200);

        foreach ($response->json('data') as $profile) {
            $this->assertEquals('female', strtolower($profile['gender']));
        }
    }

    /** @test */
    public function it_gets_single_profile()
    {
        $create = $this->postJson('/api/profiles', ['name' => 'emma']);

        $id = $create->json('data.id');

        $response = $this->getJson("/api/profiles/{$id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    /** @test */
    public function it_returns_404_if_profile_not_found()
    {
        $response = $this->getJson('/api/profiles/invalid-id');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_deletes_profile()
    {
        $create = $this->postJson('/api/profiles', ['name' => 'deleteuser']);

        $id = $create->json('data.id');

        $response = $this->deleteJson("/api/profiles/{$id}");

        $response->assertStatus(204);
    }
}
