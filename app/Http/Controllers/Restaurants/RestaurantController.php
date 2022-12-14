<?php

namespace App\Http\Controllers\Restaurants;

use App\Http\Controllers\Controller;
use App\Locale\AppLocale;
use App\Models\Restaurants\Restaurant;
use App\Traits\FileTrait;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    use ResponseTrait, FileTrait;
    public function all(Request $request)
    {
        try {
            if (isset($request->limit)) {
                $restaurants = Restaurant::paginate($request->limit);
                return $this->success($restaurants);
            }
            $restaurants = Restaurant::get();
            return $this->success($restaurants);
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
                    $restaurants = Restaurant::where('category', $request->category)->paginate($request->limit);
                    return $this->success($restaurants);
                }
                $foods = Restaurant::where('category', $request->category)->get();
                return $this->success($foods);
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
    }

    public function addRestaurant(Request $request)
    {
        try {
            $valid = $request->validate([
                'name' => 'required|min:4|max:255',
                'phone' => 'required|min:11|max:255',
                'image' => 'required|mimes:png,jpg',
                'rate' => 'required',
            ], $request->all());
            if ($valid) {
                $fields = [
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'description' => $request->description,
                    'image' => '',
                    'rate' => $request->rate,
                ];
                $restaurant = Restaurant::create($fields);
                if ($restaurant) {
                    $path = 'Restaurants';
                    $file = $request->file('image');
                    $fileName = $this->uploadFile($file, $path);
                    if (!$fileName) return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
                    $restaurant->image = $fileName;
                    $restaurant->save();
                }
                return $this->success($restaurant);
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        } catch (Exception $e) {
            return $this->failure($e, 400);
        }
    }
}
