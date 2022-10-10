<?php
namespace App\Locale;

class AppLocale{

    private static $appLocale = [
        'en' => [
            'This user does not exist' => 'This user does not exist',
            'The email or password is not correct' => 'The email or password is not correct',
            'This user is already verified' => 'This user is already verified',
            'This user is not verified' => 'This user is not verified',
            'Code sent to your email' => 'Code sent to your email',
            'Code is required' => 'Code is required',
            'The code sent is expired' => 'The code sent is expired',
            'Code matched' => 'Code matched',
            'Did not match the code' => 'Did not match the code',
            'Password reset successfully' => 'Password reset successfully',
            'You logged out successfully' => 'You logged out successfully',
            'This user is not logged in' => 'This user is not logged in',
            'Something went wrong' => 'Something went wrong',
        ],
        'ar' => [
            'This user does not exist' => 'هذا المستخدم غير موجود',
            'The email or password is not correct' => 'البريد الالكتروني او كلمة المرور غير صحيحة',
            'This user is already verified' => 'هذا المستخدم مفعل بالفعل',
            'This user is not verified' => 'هذا المستخدم غير مفعل',
            'Code sent to your email' => 'تم ارسال الكود الى بريدك الالكتروني',
            'Code is required' => 'الكود مطلوب',
            'The code sent is expired' => 'الكود المرسل منتهي الصلاحية',
            'Code matched' => 'الكود مطابق',
            'Did not match the code' => 'لم يتم مطابقة الكود',
            'Password reset successfully' => 'تم اعادة تعيين كلمة المرور بنجاح',
            'You logged out successfully' => 'لقد سجلت الخروج بنجاح',
            'This user is not logged in' => 'هذا المستخدم لم يسجل الدخول',
            'Something went wrong' => 'حدث خطأ ما',
        ],
    ];

    public static function getMessage($message){
        $locale = app()->getLocale();
        return self::$appLocale[$locale][$message];
    }
}