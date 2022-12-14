<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Locale\AppLocale;
use App\Models\Categories\Category;
use App\Traits\FileTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\ResponseTrait;

class CategoryController extends Controller
{
    use ResponseTrait, FileTrait;
    public function all(Request $request)
    {
        try {
            if (isset($request->limit)) {
                $categories = Category::paginate($request->limit);
                return $this->success($categories);
            }
            $categories = Category::get();
            return $this->success($categories);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
    }
}
