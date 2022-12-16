<?php

namespace App\Http\Controllers\Restaurants;

use App\Http\Controllers\Controller;
use App\Locale\AppLocale;
use App\Models\Restaurants\Restaurant;
use App\Traits\FileTrait;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class RestaurantController extends Controller
{
    use ResponseTrait, FileTrait;
    public function all(Request $request)
    {
        $locale = app()->getLocale();
        try {
            $restaurants = Restaurant::select("name_$locale as name", "description_$locale as description", "phone", "image", "rate");
            if (isset($request->limit)) {
                $restaurants = $restaurants->paginate($request->limit);
                foreach ($restaurants as $restaurant) {
                    $restaurant->image = asset(Storage::url($restaurant->image));
                }

                return $this->success($restaurants);
            }
            $restaurants = $restaurants->get();
            foreach ($restaurants as $restaurant) {
                $restaurant->image = asset(Storage::url($restaurant->image));
            }
            return $this->success($restaurants);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
    }

    public function withCategory(Request $request)
    {
        $locale = app()->getLocale();
        try {
            $valid = $request->validate([
                'category' => 'required'
            ], $request->all());
            $restaurants = Restaurant::select("name_$locale as name", "description_$locale as description", "phone", "image", "rate")->where('category', $request->category);
            if ($valid) {
                if (isset($request->limit)) {
                    $restaurants = $restaurants->paginate($request->limit);
                    return $this->success($restaurants);
                }
                $foods = $restaurants->get();
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
                'name_en' => 'required|min:4|max:255',
                'name_ar' => 'required|min:4|max:255',
                'phone' => 'required|min:11|max:255',
                'image' => 'required|mimes:png,jpg',
                'rate' => 'required',
            ], $request->all());
            if ($valid) {
                $fields = [
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'phone' => $request->phone,
                    'description_en' => $request->description_en,
                    'description_ar' => $request->description_ar,
                    'image' => '',
                    'rate' => $request->rate,
                ];
                $restaurant = Restaurant::create($fields);
                if ($restaurant) {
                    $path = "Restaurants/$restaurant->name_en";
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
