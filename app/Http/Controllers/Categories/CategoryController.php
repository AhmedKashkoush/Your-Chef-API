<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Locale\AppLocale;
use App\Models\Categories\Category;
use App\Traits\FileTrait;
use Exception;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;

class CategoryController extends Controller
{
    use ResponseTrait, FileTrait;
    public function all(Request $request)
    {
        $locale = app()->getLocale();
        try {
            $categories = Category::select("name_$locale as name");
            if (isset($request->limit)) {
                $categories = $categories->paginate($request->limit);
                return $this->success($categories);
            }
            $categories = $categories->get();
            return $this->success($categories);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
    }

    public function addCategory(Request $request)
    {
        try {
            $valid = $request->validate([
                'name_en' => 'required|min:4|max:255',
                'name_ar' => 'required|min:4|max:255',
            ], $request->all());
            if ($valid) {
                $fields = [
                    'name_en' => $request->name_en,
                    'name_ar' => $request->name_ar,
                ];
                $category = Category::create($fields);
                return $this->success($category);
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        } catch (Exception $e) {
            return $this->failure($e, 400);
        }
    }
}
