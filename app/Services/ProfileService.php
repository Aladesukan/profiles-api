<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Support\Facades\Http;

class ProfileService
{
    public function createProfile(string $name): array
    {
        $name = strtolower(trim($name));

        // --- Idempotency check ---
        $existing = Profile::where('name', $name)->first();
        if ($existing) {
            return [
                'status'  => 'exists',
                'profile' => $existing,
            ];
        }

        // --- Call all 3 APIs concurrently ---
        $responses = Http::pool(fn($pool) => [
            $pool->as('gender')->get('https://api.genderize.io',    ['name' => $name]),
            $pool->as('age')   ->get('https://api.agify.io',        ['name' => $name]),
            $pool->as('nation')->get('https://api.nationalize.io',  ['name' => $name]),
        ]);

        $gender  = $responses['gender']->json();
        $age     = $responses['age']->json();
        $nation  = $responses['nation']->json();

        // --- Validate responses (502 if invalid) ---
        if (empty($gender['gender']) || empty($gender['count'])) {
            throw new \Exception('Genderize returned an invalid response', 502);
        }

        if (empty($age['age'])) {
            throw new \Exception('Agify returned an invalid response', 502);
        }

        if (empty($nation['country'])) {
            throw new \Exception('Nationalize returned an invalid response', 502);
        }

        // --- Pick top country ---
        $topCountry = collect($nation['country'])
            ->sortByDesc('probability')
            ->first();

        // --- Classify age group ---
        $ageGroup = $this->classifyAge($age['age']);

        // --- Store profile ---
        $profile = Profile::create([
            'name'                => $name,
            'gender'              => $gender['gender'],
            'gender_probability'  => $gender['probability'],
            'sample_size'         => $gender['count'],
            'age'                 => $age['age'],
            'age_group'           => $ageGroup,
            'country_id'          => $topCountry['country_id'],
            'country_probability' => $topCountry['probability'],
        ]);

        return [
            'status'  => 'created',
            'profile' => $profile,
        ];
    }

    private function classifyAge(int $age): string
    {
        return match(true) {
            $age <= 12 => 'child',
            $age <= 19 => 'teenager',
            $age <= 59 => 'adult',
            default    => 'senior',
        };
    }
}