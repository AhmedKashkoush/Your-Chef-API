<?php

namespace App\Models\Foods;

use App\Models\Categories\Category;
use App\Models\Restaurants\Restaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;

    protected $table = 'foods';

    protected $fillable = [
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'image',
        'rate',
        'price',
        'restaurant_id',
        'category_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'pivot',
        'restaurant_id',
        'category_id',
    ];

    function category()
    {
        return $this->belongsTo(Category::class);
    }

    function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'restaurants_foods', 'food_id', 'restaurant_id', 'id', 'id');
    }

    function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }    
}
