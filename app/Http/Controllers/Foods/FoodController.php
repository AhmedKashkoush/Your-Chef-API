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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FoodController extends Controller
{
    use ResponseTrait, FileTrait;
    public function all(Request $request)
    {
        // "name_$locale as name","description_$locale as description","image","rate","price","stock","restaurants"
        $locale = app()->getLocale();
        try {
            if (isset($request->limit)) {
                $foods = Food::select("id","name_$locale as name","description_$locale as description","image","rate","price","stock","restaurant_id")->with(['restaurant' => function($query){
                    $locale = app()->getLocale();
                    $query->select('id',"name_$locale as name","description_$locale as description","image","rate","phone");
                }])->paginate($request->limit);
                foreach($foods as $food){
                    $food->image = asset(Storage::url($food->image));
                    $food->price = sprintf("%.2f",$food->price);
                    $food->rate = sprintf("%.2f",$food->rate);
                    if (!str_starts_with($food->restaurant->image,'http')) $food->restaurant->image = asset(Storage::url($food->restaurant->image));
                    $food->restaurant->rate = sprintf("%.2f",$food->restaurant->rate);
                }
                return $this->success($foods);
            }

            $foods = Food::select("id","name_$locale as name","description_$locale as description","image","rate","price","stock","restaurant_id")->with(['restaurant' => function($query){
                $locale = app()->getLocale();
                $query->select('id',"name_$locale as name","description_$locale as description","image","rate","phone");
            }])->get(); 
            // $foods = Food::select('id')->with('restaurant')->get();
            foreach($foods as $food){
                $food->image = asset(Storage::url($food->image));
                $food->price = sprintf("%.2f",$food->price);
                $food->rate = sprintf("%.2f",$food->rate);
                if (!str_starts_with($food->restaurant->image,'http')) $food->restaurant->image = asset(Storage::url($food->restaurant->image));
                $food->restaurant->rate = sprintf("%.2f",$food->restaurant->rate);
            }
            return $this->success($foods);
        } catch (Exception $e) {
            return $e -> getMessage();
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
            $foods = Category::find($request->category)->foods()->with('restaurant');//->select("name_$locale as name", "description_$locale as description", "image", "rate", "price");
            if ($valid) {
                if (isset($request->limit)) {
                    $foods = $foods->paginate($request->limit);
                    foreach($foods as $food){
                        $food->image = asset(Storage::url($food->image));
                        $food->price = sprintf("%.2f",$food->price);
                        $food->rate = sprintf("%.2f",$food->rate);
                        if (!str_starts_with($food->restaurant->image,'http')) $food->restaurant->image = asset(Storage::url($food->restaurant->image));
                        $food->restaurant->rate = sprintf("%.2f",$food->restaurant->rate);
                    }
                    return $this->success($foods);
                }
                $foods = $foods->get();
                foreach ($foods as $food) {
                    $food->image = asset(Storage::url($food->image));
                }
                if ($foods) return $this->success($foods);
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
    }

    public function fromRestaurant(Request $request)
    {
        $locale = app()->getLocale();
        try {
            $valid = $request->validate([
                'restaurant' => 'required'
            ], $request->all());
            $foods = Restaurant::find($request->restaurant)->foods()->select("name_$locale as name", "description_$locale as description", "image", "rate", "price");
            if ($valid) {
                if (isset($request->limit)) {
                    $foods = $foods->paginate($request->limit);
                    foreach ($foods as $food) {
                        $food->image = asset(Storage::url($food->image));
                    }
                    return $this->success($foods);
                }
                $foods = $foods->get();
                foreach ($foods as $food) {
                    $food->image = asset(Storage::url($food->image));
                }
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
                'name_en' => 'required|min:4|max:255',
                'name_ar' => 'required|min:4|max:255',
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
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                    'description_en' => $request->description_en,
                    'description_ar' => $request->description_ar,
                    'image' => '',
                    'rate' => $request->rate,
                    'price' => $request->price,
                    'restaurant_id' => $request->restaurant,
                    'category_id' => $request->category,
                ];
                $food = Food::create($fields);;
                if ($food) {
                    $path = "Foods/$restaurant->name_en";
                    $file = $request->file('image');
                    $fileName = $this->uploadFile($file, $path);
                    if (!$fileName) return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
                    $food->image = $fileName;
                    $food->save();
                }
                DB::insert('insert into restaurants_foods (restaurant_id, category_id,food_id) values (?, ?, ?)', [$request->restaurant, $request->category, $food->id]);
                return $this->success($food);
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        } catch (Exception $e) {
            return $this->failure($e, 400);
        }
    }
}
