<?php

namespace App\Http\Controllers\Foods;

use App\Http\Controllers\Controller;
use App\Locale\AppLocale;
use App\Models\Categories\Category;
use App\Models\Foods\Food;
use App\Models\Restaurants\Restaurant;
use App\Traits\FileTrait;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    use ResponseTrait, FileTrait;
    public function all(Request $request)
    {
        try {
            if (isset($request->limit)) {
                $foods = Food::paginate($request->limit);
                return $this->success($foods);
            }
            $foods = Food::get();
            return $this->success($foods);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
    }

    public function withCategory(Request $request)
    {
        try {
            $valid = $request->validate([
                'category' => 'required'
            ], $request->all());
            if ($valid) {
                if (isset($request->limit)) {
                    $foods = Category::find($request->category)->foods()->paginate($request->limit);
                    return $this->success($foods);
                }
                $foods = Category::find($request->category)->foods;
                if ($foods) return $this->success($foods);
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
    }

    public function fromRestaurant(Request $request)
    {
        try {
            $valid = $request->validate([
                'restaurant' => 'required'
            ], $request->all());
            if ($valid) {
                if (isset($request->limit)) {
                    $foods = Restaurant::find($request->restaurant)->foods()->paginate($request->limit);
                    return $this->success($foods);
                }
                $foods = Restaurant::find($request->restaurant)->foods;
                if ($foods) return $this->success($foods);
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
    }


    public function addFood(Request $request)
    {
        try {
            $valid = $request->validate([
                'name' => 'required|min:4|max:255',
                'image' => 'required|mimes:png,jpg',
                'rate' => 'required',
                'price' => 'required',
                'category' => 'required',
                'restaurant' => 'required'
            ], $request->all());
            if ($valid) {
                $category = Category::where('id', $request->category)->first();
                $restaurant = Restaurant::where('id', $request->restaurant)->first();
                if (!$category || !$restaurant) return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
                $fields = [
                    'name' => $request->name,
                    'description' => $request->description,
                    'image' => '',
                    'rate' => $request->rate,
                    'price' => $request->price,
                    'restaurant_id' => $request->restaurant,
                    'category_id' => $request->category,
                ];
                $food = Food::create($fields);;
                if ($food) {
                    $path = 'Foods';
                    $file = $request->file('image');
                    $fileName = $this->uploadFile($file, $path);
                    if (!$fileName) return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
                    $food->image = $fileName;
                    $food->save();
                }
                return $this->success($food);
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        } catch (Exception $e) {
            return $this->failure($e, 400);
        }
    }
}
