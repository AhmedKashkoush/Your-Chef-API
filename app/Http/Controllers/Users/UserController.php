<?php

namespace App\Http\Controllers\Users;

use Exception;
use App\Models\User;
use App\Mail\OtpMail;
use App\Locale\AppLocale;
use Illuminate\Http\Request;
use App\Models\Auth\OtpCodes;
use App\Traits\ResponseTrait;
use App\Traits\ValidationTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Notifications\SMSNotification;
use App\Traits\FileTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\VonageChannelServiceProvider;

class UserController extends Controller
{
    use ResponseTrait, FileTrait;

    //------------------Auth------------------//

    //Register
    public function register(Request $request)
    {
        $valid = validator(
            $request->all(),
            [
                'name' => 'min:7|max:255|required',
                'gender' => 'max:1|required',
                'phone' => 'max:255|unique:users|required',
                'email' => 'max:255|unique:users|required',
                'password' => 'min:8|max:255|required'
            ],
            ValidationTrait::registerRules(),
        );

        if (!$valid->fails()) {
            $user = $request->all();
            $user['password'] = bcrypt($request->password);
            $success = User::create($user);
            if ($success) {
                $success->gender = $success->gender % 2 == 0 ? 'Female' : 'Male';
                return $this->success($success, 'User registerd');
            }
        }
        return $this->failure($valid->errors()->first(), 400);
    }

    //Login
    public function login(Request $request)
    {
        $valid = validator(
            $request->all(),
            [
                'email' => 'max:255|required',
                'password' => 'min:8|max:255|required'
            ],
            ValidationTrait::loginRules(),
        );
        if (!$valid->fails()) {
            try {
                $user = User::where('email', $request->email)->first();
                $success = Hash::check($request->password, $user['password']);
                if (!$success) return $this->failure(AppLocale::getMessage('The email or password is not correct'), 400);
                if ($user && $success) {
                    if (!isset($user['verified_at'])) return $this->failure(AppLocale::getMessage('This user is not verified'), 400);
                    $user->online_status = 1;
                    $user->save();
                    $plainText = $user->createToken('access_token')->plainTextToken;
                    $plaintText = explode('|', $plainText);
                    $token = end($plaintText);
                    if (isset($user['image'])) {
                        $user['image'] = asset(Storage::url($user['image']));
                    }
                    $user->gender = $user->gender % 2 == 0 ? 'Female' : 'Male';
                    $user->online_status = $user->online_status == 1 ? 'Online' : 'Offline';
                    $user['token'] = $token;
                    return $this->success($user);
                }
            } catch (Exception $e) {
                return $this->failure(AppLocale::getMessage('This user does not exist'), 400);
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
        return $this->failure($valid->errors()->first(), 400);
    }

    //------------------Photos------------------//

    //Upload
    public function uploadPhoto(Request $request)
    {
        if ($request->hasFile('photo')) {
            try {
                if (isset($request->email)) {
                    $file = $request->file('photo');
                    $fileName = time() . $file->getClientOriginalName();
                    $path = 'Users/ProfilePhotos/' . $request->email;
                    //return file_get_contents($file);
                    // $filePath = Storage::disk('public')->put($path, $file, 'public');
                    // //$filePath = $file->storeAs($path,$fileName);
                    // //return Storage::url($filePath);
                    $filePath = $this->uploadFile($file, $path);
                    $user = User::where('email', $request->email)->first();
                    $user['image'] = $filePath;
                    $success =  $user->save();
                    if ($success) return $this->success();
                }
            } catch (Exception $e) {
                $this->failure(AppLocale::getMessage('Something went wrong'), 400);
            }
        }
        return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
    }

    //Delete
    public function deletePhoto(Request $request)
    {
        try {
            if (isset($request->email)) {
                $user = User::get()->where('email', $request->email)->first();
                $filePath = $user['image'];
                $success = $this->deleteFile($filePath); //Storage::disk('public')->delete([$filePath]);

                if ($success) {
                    $user['image'] = null;
                    $user->save();
                    return $this->success();
                }
            }
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
        return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
    }

    //Get All
    public function allPhotos(Request $request)
    {
        try {
            if (isset($request->email)) {
                // $photos = collect(Storage::disk('public')->allFiles('Users/ProfilePhotos/' . $request->email))->map(function ($photo) {
                //     return asset(Storage::url($photo));
                // });
                //$photos = Storage::allFiles('Users/ProfilePhotos/'.$request -> email);
                // for ($i = 0;$i < count($photos); $i ++){
                //     $photos[$i] = Storage::url($photos[$i]); //storage_path('app/'.$photos[$i]);
                // }
                $path = 'Users/ProfilePhotos/' . $request->email;
                $photos = $this->allFilesAt($path);
                if ($photos) {
                    return $this->success($photos);
                    // return [
                    // 'status' => 'success',
                    // 'data' => $photos
                    // ];
                }
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
    }

    //------------------Otp------------------//

    //Send Code
    public function sendOtp(Request $request)
    {
        if (!isset($request->via)) return $this->failure(AppLocale::getMessage('Verification method is required'), 400);
        //Make previous Otps expired
        $code = OtpCodes::orderBy('created_at', 'DESC')->where('expired_at', null)->first();
        if ($code) {
            $code['expired_at'] = now();
            $code->save();
        }
        //Generate Otp
        $otp = rand(10000, 99999);
        //Send Otp via phone
        if (isset($request->email)) {
            $user = User::where('email', $request->email)->get()->first();
            $isSent = OtpCodes::create([
                'phone' => $user->phone,
                'code' => $otp,
            ]);

            if (!$isSent) return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
            //Phone
            if (strtoupper($request->via) == 'PHONE') {
                Notification::sendNow($user, new SMSNotification($otp));
            }
            //Mail
            else if (strtoupper($request->via) == 'MAIL') {
                $from = AppLocale::getMessage('Your Chef');
                $name = explode(' ', $user->name);
                $name = reset($name);
                $header = AppLocale::getMessage('Hi') . ' ' . $name . '!';
                $body = AppLocale::getMessage('Your verification code is') . ' ' . $otp;
                $subject = AppLocale::getMessage('Your Chef Verification');
                $data = [
                    'from' => $from,
                    'header' => $header,
                    'body' => $body,
                    'subject' => $subject,
                ];
                Mail::to($user->email)->send(new OtpMail($data));
            } else return $this->failure(AppLocale::getMessage('Something went wrong'), 400);

            return $this->success(null, AppLocale::getMessage('Code sent'));
        }

        return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
    }

    //Send via Mail
    public function sendMailOtp(Request $request)
    {
        //Make previous Otps expired
        $code = OtpCodes::orderBy('created_at', 'DESC')->where('expired_at', null)->first();
        if ($code) {
            $code['expired_at'] = now();
            $code->save();
        }
        //Generate Otp
        $otp = rand(10000, 99999);
        //Send Otp via phone
        if (isset($request->email)) {
            $user = User::where('email', $request->email)->get()->first();
            $isSent = OtpCodes::create([
                'email' => $user->email,
                'code' => $otp,
            ]);

            if (!$isSent) return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
            $from = AppLocale::getMessage('Your Chef');
            $name = explode(' ', $user->name);
            $name = reset($name);
            $header = AppLocale::getMessage('Hi') . ' ' . $name . '!';
            $body = AppLocale::getMessage('Your verification code is') . ' ' . $otp;
            $subject = AppLocale::getMessage('Your Chef Verification');
            $data = [
                'from' => $from,
                'header' => $header,
                'body' => $body,
                'subject' => $subject,
            ];
            Mail::to($user->email)->send(new OtpMail($data));

            return $this->success(null, AppLocale::getMessage('Code sent to your email'));
        }

        return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
    }

    //Verify User
    public function verifyOtp(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        //Check if user is verified
        if (isset($user['verified_at'])) return $this->failure(AppLocale::getMessage('This user is already verified'), 400);
        if (!isset($request->code)) return $this->failure(AppLocale::getMessage('Code is required'), 400);
        if (isset($request->email)) {
            $code = OtpCodes::orderBy('created_at', 'DESC')->where('expired_at', null)->first();
            if (!$code) return $this->failure(AppLocale::getMessage('The code sent is expired'), 400);
            $isMatched = $code['code'] == $request->code;
            if ($isMatched) {
                $code['expired_at'] = now();
                $code->save();
                $user['verified_at'] = now();
                $user->save();
                return $this->success(null, AppLocale::getMessage('Code matched'));
            }
        }

        return $this->failure(AppLocale::getMessage('Did not match the code'), 400);
    }

    //Verify Phone
    public function verifyPhoneOtp(Request $request)
    {
        //Verify Phone
        if (!isset($request->code)) return $this->failure(AppLocale::getMessage('Code is required'), 400);
        if (isset($request->phone)) {
            $code = OtpCodes::orderBy('created_at', 'DESC')->where('expired_at', null)->first();
            if (!$code) return $this->failure(AppLocale::getMessage('The code sent is expired'), 400);
            $isMatched = $code['code'] == $request->code;
            if ($isMatched) {
                $code['expired_at'] = now();
                $code->save();
                return $this->success(null, AppLocale::getMessage('Code matched'));
            }
        }

        return $this->failure(AppLocale::getMessage('Did not match the code'), 400);
    }

    //Verify Mail
    public function verifyMailOtp(Request $request)
    {
        //Verify Email
        if (!isset($request->code)) return $this->failure(AppLocale::getMessage('Code is required'), 400);
        if (isset($request->email)) {
            $code = OtpCodes::orderBy('created_at', 'DESC')->where('expired_at', null)->first();
            if (!$code) return $this->failure(AppLocale::getMessage('The code sent is expired'), 400);
            $isMatched = $code['code'] == $request->code;
            if ($isMatched) {
                $code['expired_at'] = now();
                $code->save();
                return $this->success(null, AppLocale::getMessage('Code matched'));
            }
        }

        return $this->failure(AppLocale::getMessage('Did not match the code'), 400);
    }

    //------------------Password------------------//

    //Reset Password
    public function resetPassword(Request $request)
    {
        if ($request->email && $request->password) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user->password = bcrypt($request->password);
                $user->save();
                return $this->success(null, AppLocale::getMessage('Password reset successfully'));
            }
        }
        return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
    }

    //------------------User------------------//

    //Get Authenticated User
    public function user(Request $request)
    {
        $user = $request->user();
        $user->online_status = 'Online';
        return $user;
    }

    //Status
    public function updateUserStatus()
    {
        return $this->success();
    }

    //Edit User
    public function editUser(Request $request)
    {
        try {
            $user = $request->user();
            $data = $request->all(['name', 'gender']);
            $data['image'] = $user->image;
            if (isset($request->photo)) {
                $file = $request->file('photo');
                $fileName = time() . $file->getClientOriginalName();
                $path = 'Users/ProfilePhotos/' . $user->email;
                $filePath = $this->uploadFile($file, $path); //Storage::disk('public')->put($path, $file, 'public');
                $data['image'] = $filePath;
            }
            $success = User::where('id', $user->id)->update($data);
            if (!$success) return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
            $newUser = User::where('id', $user->id)->first();
            $newUser->gender = $newUser->gender % 2 == 0 ? 'Female' : 'Male';
            $newUser->online_status = $newUser->online_status == 0 ? 'Offline' : 'Online';
            return $this->success($newUser);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }

        return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
    }

    //Logout
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->online_status = 0;
        $user->save();
        $token = $user->currentAccessToken()->delete();
        if ($token) return $this->success(null, AppLocale::getMessage('You logged out successfully'));

        return $this->failure(AppLocale::getMessage('This user is not logged in'), 403);
    }

    //Delete User
    public function deleteUser(Request $request)
    {
        $user = $request->user();
        try {
            if (isset($user->image)) {
                $directory = 'Users/ProfilePhotos/' . $user->email;
                $success = $this->deleteFileDirectory($directory);
                //$success = Storage::disk('public')->deleteDirectory($path);
                if ($success) $success = $user->currentAccessToken()->delete();
                if ($success) $success = $user->delete();
                return $this->success(null, 'User deleted');
            } else {
                $success = $user->currentAccessToken()->delete();
                if ($success) $success = $user->delete();
                return $this->success(null, 'User deleted');
            }
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        } catch (Exception $e) {
            return $this->failure(AppLocale::getMessage('Something went wrong'), 400);
        }
    }
}
