<?php

namespace App\Models\Restaurants;

use App\Models\Foods\Food;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'phone',
        'description_en',
        'description_ar',
        'image',
        'rate'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    function foods()
    {
        return $this->belongsToMany(Food::class, 'restaurants_foods', 'restaurant_id', 'food_id', 'id', 'id');
    }

    function categories()
    {
        return $this->foods()->categories();
    }
}
