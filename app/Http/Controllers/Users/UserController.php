<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Auth\OtpCodes;
use App\Models\User;
use App\Notifications\SMSNotification;
use Exception;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\VonageChannelServiceProvider;
use Illuminate\Support\Facades\Mail;
use App\Traits\ValidationTrait;
use App\Locale\AppLocale;

class UserController extends Controller
{
    use ResponseTrait;

    //------------------Auth------------------//

    //Register
    public function register(Request $request){
        $valid = validator($request->all(),        
            [
                'name' => 'min:7|max:255|required',
                'phone' => 'max:255|unique:users|required',
                'email' => 'max:255|unique:users|required',
                'password' => 'min:8|max:255|required'
            ],
            ValidationTrait::registerRules(),
        );

        if (!$valid->fails()){
            $user = $request->all();
            $user['password'] = bcrypt($request->password);
            $success = User::create($user);
            if ($success) 
            return $this -> success($success);
        }
        return $this -> failure($valid -> errors() -> first(),400);
    }

    //Login
    public function login(Request $request){
        $valid = validator($request->all(),        
            [
                'email' => 'max:255|required',
                'password' => 'min:8|max:255|required'
            ],
            ValidationTrait::loginRules(),
        );
        if (!$valid->fails()){
            try{
                $user = User::where('email',$request->email)->first();
                $success = Hash::check($request -> password,$user['password']);
                if (!$success) return $this -> failure(AppLocale::getMessage('The email or password is not correct'),400);
                if ($user && $success) {
                    if (!isset($user['verified_at'])) return $this -> failure(AppLocale::getMessage('This user is not verified') ,400); 
                    $plainText = $user -> createToken('access_token') ->plainTextToken;
                    $plaintText = explode('|',$plainText);
                    $token = end($plaintText);
                    if (isset($user['image'])){
                        $user['image'] = asset(Storage::url($user['image']));
                    }
                    $user['token'] = $token;               
                    return $this -> success($user);
                }
            }
            catch(Exception $e){
                return $this -> failure(AppLocale::getMessage('This user does not exist'),400);
            }
            return $this -> failure(AppLocale::getMessage('Something went wrong'),400);
        }
        return $this -> failure($valid -> errors() -> first(),400);
    }

    //------------------Photos------------------//

    //Upload
    public function uploadPhoto(Request $request){
        if ($request->hasFile('photo')){
            try{
                if (isset($request-> email))
                {
                    $file = $request->file('photo');
                    $fileName = time().$file->getClientOriginalName();
                    $path = 'Users/ProfilePhotos/'. $request -> email;
                    //return file_get_contents($file);
                    $filePath = Storage::disk('public')->put($path,$file,'public');
                    //$filePath = $file->storeAs($path,$fileName);
                    //return Storage::url($filePath);
                    $user = User::get() -> where('email',$request->email) -> first();
                    $user['image'] = $filePath;
                    $success =  $user -> save();
                    if ($success) return $this -> success();
                }
            }
            catch(Exception $e){
                return $e -> getMessage();
            }
        }
        return $this -> failure(AppLocale::getMessage('Something went wrong'),400);
    }

    //Delete
    public function deletePhoto(Request $request){
        try{
            if (isset($request-> email))
            {
                $user = User::get() -> where('email',$request->email) -> first();
                $filePath = $user['image'];
                $success = Storage::disk('public')->delete([$filePath]);
                
                if ($success) {
                    $user['image'] = null;
                    $user -> save();
                    return $this -> success();
                }
            }
        }
        catch(Exception $e){
            return $e -> getMessage();
        }
        return $this -> failure(AppLocale::getMessage('Something went wrong'),400);
    }

    //Get All
    public function allPhotos(Request $request){
        try{
            if (isset($request-> email))
            {
                $photos = collect(Storage::disk('public')-> allFiles('Users/ProfilePhotos/'.$request -> email))->map(function($photo){
                    return asset(Storage::url($photo));
                });
                //$photos = Storage::allFiles('Users/ProfilePhotos/'.$request -> email);
                // for ($i = 0;$i < count($photos); $i ++){
                //     $photos[$i] = Storage::url($photos[$i]); //storage_path('app/'.$photos[$i]);
                // }
                if ($photos) {
                    return $this -> success($photos);
                    // return [
                    // 'status' => 'success',
                    // 'data' => $photos
                    // ];
                }
            }
        }
        catch(Exception $e){
            return $e -> getMessage();
        }
        return $this -> failure(AppLocale::getMessage('Something went wrong'),400);
    }

    //------------------Otp------------------//

    //Send Phone
    public function sendOtp(Request $request){
        //Make previous Otps expired
        $code = OtpCodes::orderBy('created_at','DESC')->where('expired_at',null)->first();
        if ($code)
        {
            $code['expired_at'] = now();
            $code -> save();
        }    
        //Generate Otp
        $otp = rand(10000,99999);
        //Send Otp via phone
        if (isset($request -> email)){
            $user = User::where('email',$request -> email)->get()->first();
            $isSent = OtpCodes::create([
                'phone' => $user -> phone,
                'code' => $otp,
            ]);

            if (!$isSent) return $this -> failure(AppLocale::getMessage('Something went wrong'),400);            
            Notification::sendNow($user,new SMSNotification($otp));
            
            return $this -> success(null,AppLocale::getMessage('Code sent'));
        }

        return $this -> failure(AppLocale::getMessage('Something went wrong'),400);
    }

    //Send Mail
    public function sendMailOtp(Request $request){
        //Make previous Otps expired
        $code = OtpCodes::orderBy('created_at','DESC')->where('expired_at',null)->first();
        if ($code)
        {
            $code['expired_at'] = now();
            $code -> save();
        }    
        //Generate Otp
        $otp = rand(10000,99999);
        //Send Otp via phone
        if (isset($request -> email)){
            $user = User::where('email',$request -> email)->get()->first();
            $isSent = OtpCodes::create([
                'email' => $user -> email,
                'code' => $otp,
            ]);

            if (!$isSent) return $this -> failure(AppLocale::getMessage('Something went wrong'),400);
            $name = explode(' ',$user -> name);
            $name = reset($name);
            $body = "Your verification code is $otp";  
            $subject = 'Your Chef Verification';
            $data = [
                'name' => $name,
                'body' => $body,
                'subject' => $subject,
            ];
            Mail::to($user -> email)->send(new OtpMail($data));
            
            return $this -> success(null,AppLocale::getMessage('Code sent to your email'));
        }

        return $this -> failure(AppLocale::getMessage('Something went wrong'),400);
    }

    //Verify Phone
    public function verifyOtp(Request $request){
        $user = User::where('email',$request -> email) -> first();
        //Check if user is verified
        if (isset($user['verified_at'])) return $this -> failure(AppLocale::getMessage('This user is already verified'),400);
        //Verify Phone
        if (!isset($request -> code)) return $this -> failure(AppLocale::getMessage('Code is required'),400);
        if (isset($request -> email)){
            $code = OtpCodes::orderBy('created_at','DESC')->where('expired_at',null)->first();
            if (!$code) return $this -> failure(AppLocale::getMessage('The code sent is expired'),400);
            $isMatched = $code['code'] == $request -> code;
            if ($isMatched) {
                $code['expired_at'] = now();
                $code -> save();                
                $user['verified_at'] = now();
                $user -> save();
                return $this -> success(null,AppLocale::getMessage('Code matched'));
            }
        }

        return $this -> failure(AppLocale::getMessage('Did not match the code'),400);
    }    

    //Verify Mail
    public function verifyMailOtp(Request $request){
        //Verify Email
        if (!isset($request -> code)) return $this -> failure(AppLocale::getMessage('Code is required'),400);
        if (isset($request -> email)){
            $code = OtpCodes::orderBy('created_at','DESC')->where('expired_at',null)->first();
            if (!$code) return $this -> failure(AppLocale::getMessage('The code sent is expired'),400);
            $isMatched = $code['code'] == $request -> code;
            if ($isMatched) {
                $code['expired_at'] = now();
                $code -> save();                
                return $this -> success(null,AppLocale::getMessage('Code matched'));
            }
        }

        return $this -> failure(AppLocale::getMessage('Did not match the code'),400);
    }

    //------------------Password------------------//

    //Reset Password
    public function resetPassword(Request $request){
        if ($request->email && $request->password){
            $user = User::where('email',$request->email)->first();
            if ($user){
                $user->password = bcrypt($request->password);
                $user->save();
                return $this -> success(null,AppLocale::getMessage('Password reset successfully'));
            }
        }
        return $this -> failure(AppLocale::getMessage('Something went wrong'),400);
    }

    //------------------User------------------//

    //Get Authenticated User
    public function user(Request $request){
        return $request->user();
    }

    //Logout
    public function logout(Request $request){
        $user = $request->user();
        $token = $user->currentAccessToken()->delete();
        if ($token) return $this -> success(null,AppLocale::getMessage('You logged out successfully'));

        return $this -> failure(AppLocale::getMessage('This user is not logged in'),403);
    }
}
