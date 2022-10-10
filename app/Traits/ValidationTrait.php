<?php

namespace App\Traits;
trait ValidationTrait{

    private static $register = [
        'en' => [
            'name.required' => 'Name is required',
            'phone.required' => 'Phone is required',
            'email.required' => 'Email is required',
            'password.required' => 'Password is required',
            'phone.unique' => 'This phone already exists',
            'email.unique' => 'This email already exists',
            'name.max' => 'Name must not exceed 255 characters',
            'phone.max' => 'Phone must not exceed 255 characters',
            'email.max' => 'Email must not exceed 255 characters',
            'password.max' => 'Password must not exceed 255 characters',
            'name.min' => 'Name must be at least 7 characters',
            'password.min' => 'Password be at least 8 characters',
        ],
        'ar' => [
            'name.required' => 'الاسم مطلوب',
            'phone.required' => 'الهاتف مطلوب',
            'email.required' => 'البريد الالكتروني مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'phone.unique' => 'هذا الهاتف موجود بالفعل',
            'email.unique' => 'هذا البريد موجود بالفعل',
            'name.max' => 'يجب الا يتخطى الاسم 255 حرفا',
            'phone.max' => 'يجب الا يتخطى الهاتف 255 حرفا',
            'email.max' => 'يجب الا يتخطى البريد الالكتروني 255 حرفا',
            'password.max' => 'يجب الا تتخطى كلمة المرور 255 حرفا',
            'name.min' => 'يجب ان يكون الاسم على الاقل 7 أحرف',
            'password.min' => 'يجب ان تكون كلمة المرور على الافل 8 أحرف',
        ],
    ];

    private static $login = [
        'en' => [
            'email.required' => 'Email is required',
            'password.required' => 'Password is required',
            'email.max' => 'Email must not exceed 255 characters',
            'password.max' => 'Password must not exceed 255 characters',           
            'password.min' => 'Password be at least 8 characters',
        ],
        'ar' => [
            'email.required' => 'البريد الالكتروني مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'email.max' => 'يجب الا يتخطى البريد الالكتروني 255 حرفا',
            'password.max' => 'يجب الا تتخطى كلمة المرور 255 حرفا',
            'password.min' => 'يجب ان تكون كلمة المرور على الافل 8 أحرف',
        ],
    ];

    public static function registerRules(){
        return self::$register[app()->getLocale()];
    }

    public static function loginRules(){
        return self::$login[app()->getLocale()];
    }
}