<?php

namespace App\Models\Categories;

use App\Models\Foods\Food;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function foods()
    {
        return $this->hasMany(Food::class);
    }

    public function restuarants()
    {
        return $this->foods()->restuarants();
    }
}
