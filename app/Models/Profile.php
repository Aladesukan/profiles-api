<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Profile extends Model
{
    use HasFactory, HasUuids;
    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }
    // protected $table = 'profiles';

    protected $fillable = [
        'id',
        'name',
        'gender',
        'gender_probability',
        'sample_size',
        'age',
        'age_group',
        'country_id',
        'country_probability'
    ];

    protected $casts = [
        'gender_probability'  => 'decimal:4',
        'country_probability' => 'decimal:4',
        'sample_size'         => 'integer',
        'age'                 => 'integer',
    ];

}
