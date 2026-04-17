<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    { 
        $query = Profile::query();

        if ($request->gender) {
            $query->whereRaw('LOWER(gender) = ?', [strtolower($request->gender)]);
        }

        if ($request->country_id) {
            $query->whereRaw('LOWER(country_id) = ?', [strtolower($request->country_id)]);
        }

        if ($request->age_group) {
            $query->whereRaw('LOWER(age_group) = ?', [strtolower($request->age_group)]);
        }

        $profiles = $query
        ->select('id','name','gender','age','age_group','country_id')
        ->get();

        return response()->json([
            'status' => 'success',
            'count' => $profiles->count(),
            'data' => $profiles->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'gender' => $profile->gender,
                    'age' => $profile->age,
                    'age_group' => $profile->age_group,
                    'country_id' => $profile->country_id,
                ];
            })
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->has('name') || trim($request->input('name')) === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing or empty name parameter.'
            ], 400);
        }   

        if (!is_string($request->input('name'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid type for name parameter.'
            ], 422);
        }


        $name = strtolower(trim($request->input('name')));
        // if(!$name){
        //     return response()->json([
        //         'status'=>'error',
        //         'message'=>'Missing or empty name parameter.'
        //     ],400);
        // }
        
           

        $existingProfile = Profile::where('name', $name)->first();
        if ($existingProfile) {
            return response()->json([
                'status' => 'success',  
                'message' => 'Profile already exists',
                'data' => [
                    'id' => $existingProfile->id,
                    'name' => $existingProfile->name,
                    'gender' => $existingProfile->gender,
                    'gender_probability' => $existingProfile->gender_probability,
                    'sample_size' => $existingProfile->sample_size,
                    'age' => $existingProfile->age,
                    'age_group' => $existingProfile->age_group, 
                    'country_id' => $existingProfile->country_id,
                    'country_probability' => $existingProfile->country_probability,     
                    'created_at' => $existingProfile->created_at,
                ]
            ], 200);
        }
        
        // // --- Call three external api ---
        $responses = Http::pool(fn(Pool $pool) => [
            $pool->as('gender')->get('https://api.genderize.io',    ['name' => $name]),
            $pool->as('age')   ->get('https://api.agify.io',        ['name' => $name]),
            $pool->as('country')->get('https://api.nationalize.io',  ['name' => $name]),
        ]);

        $genderize = $responses['gender']->json();
        $agify    = $responses['age']->json();
        $nationalize = $responses['country']->json();

    

        if(!isset($genderize['gender']) || is_null($genderize['gender'])|| ($genderize['count'] ?? 0) === 0){
            return response()->json([
                'status' => 'error',
                'message' => 'Genderize returned an invalid response'
            ], 502);
        } 
        if(empty($agify['age'])){
            return response()->json([
                'status' => 'error',
                'message' =>'Agify returned an invalid response',
            ], 502);
        }
        if(empty($nationalize['country'])){
            return response()->json([
                'status' => 'error',
                'message' =>'Nationalize returned an invalid response'
            ], 502);
        }

        $countryData = collect($nationalize['country'])
        ->sortByDesc('probability')
        ->first();

        $country_id = $countryData['country_id'] ?? null;
        $country_probability = $countryData['probability'] ?? null;

        $age = $agify['age'];

        if ($age <= 12) {
            $ageGroup = 'child';
        } elseif ($age <= 19) {
            $ageGroup = 'teenager';
        } elseif ($age <= 59) {
            $ageGroup = 'adult';
        } else {
            $ageGroup = 'senior';
        }
        
        $profile =Profile::create([
            'name' =>$name,
            'gender'=>$genderize['gender'],
            'gender_probability'=> $genderize['probability'],
            'sample_size' => $genderize['count'] , 
            'age' => $agify['age'],
            'age_group' => $ageGroup,
            'country_id' => $country_id,
            'country_probability' => $country_probability, 
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'gender' => $profile->gender,
                'gender_probability' => $profile->gender_probability,
                'sample_size' => $profile->sample_size,
                'age' => $profile->age,
                'age_group' => $profile->age_group,
                'country_id' => $profile->country_id,
                'country_probability' => $profile->country_probability,
                'created_at' => $profile->created_at,
            ]
        ], 201);    

    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $profile = Profile::find($id);
        if((!$profile)){
            return response()->json([
                'status' => 'error',
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'status'=>"success",
            'data'=>[
                'id'=>$profile->id,
                'name'=>$profile->name,
                'gender'=>$profile->gender,
                'gender_probability'=>$profile->gender_probability,
                'sample_size'=>$profile->sample_size,
                'age'=>$profile->age,
                'age_group'=>$profile->age_group,
                'country_id'=>$profile->country_id,
                'country_probability'=>$profile->country_probability,
                'created_at'=>$profile->created_at

            ]
        ], 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $profile = Profile::find($id);

        if (!$profile) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profile not found'
            ], 404);
        }

        $profile->delete();

        return response()->noContent(); // 204
    }
}
