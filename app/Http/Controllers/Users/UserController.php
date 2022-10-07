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

class UserController extends Controller
{
    use ResponseTrait;

    //------------------Auth------------------//

    //Register
    public function register(Request $request){
        $valid = validator($request->all(),        
            [
                'name' => 'min:7|max:255',
                'phone' => 'max:255|unique:users',
                'email' => 'max:255|unique:users',
                'password' => 'min:8|max:255'
            ]);
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
                'email' => 'max:255',
                'password' => 'min:8|max:255'
            ]);
        if (!$valid->fails()){
            $user = User::where('email',$request->email) -> first();
            $success = Hash::check($request -> password,$user['password']);
            if ($user && $success) {
                if (!isset($user['verified_at'])) return $this -> failure('This user is not verified',400); 
                $plainText = $user -> createToken('access_token') ->plainTextToken;
                $plaintText = explode('|',$plainText);
                $token = end($plaintText);
                if (isset($user['image'])){
                    $user['image'] = asset(Storage::url($user['image']));
                }
                $user['token'] = $token;               
                return $this -> success($user);
            }
            return $this -> failure('Something went wrong',400);
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
                    Storage::disk('public')->put($path,$file);
                    $filePath = $file->storeAs($path,$fileName);
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
        return $this -> failure('Something went wrong',400);
    }

    //Delete
    public function deletePhoto(Request $request){
        try{
            if (isset($request-> email))
            {
                $user = User::get() -> where('email',$request->email) -> first();
                $filePath = $user['image'];
                $success = Storage::delete([$filePath]);
                
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
        return $this -> failure('Something went wrong',400);
    }

    //Get All
    public function allPhotos(Request $request){
        try{
            if (isset($request-> email))
            {
                $photos = collect(Storage::allFiles('Users/ProfilePhotos/'.$request -> email))->map(function($photo){
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
        return $this -> failure('Something went wrong',400);
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

            if (!$isSent) return $this -> failure('Something went wrong',400);            
            Notification::sendNow($user,new SMSNotification($otp));
            
            return $this -> success(null,'Code sent');
        }

        return $this -> failure('Something went wrong',400);
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

            if (!$isSent) return $this -> failure('Something went wrong',400);
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
            
            return $this -> success(null,'Code sent to your email');
        }

        return $this -> failure('Something went wrong',400);
    }

    //Verify Phone
    public function verifyOtp(Request $request){
        $user = User::where('email',$request -> email) -> first();
        //Check if user is verified
        if (isset($user['verified_at'])) return $this -> failure('This user is already verified',400);
        //Verify Phone
        if (!isset($request -> code)) return $this -> failure('Code is required',400);
        if (isset($request -> email)){
            $code = OtpCodes::orderBy('created_at','DESC')->where('expired_at',null)->first();
            if (!$code) return $this -> failure('The code sent is expired',400);
            $isMatched = $code['code'] == $request -> code;
            if ($isMatched) {
                $code['expired_at'] = now();
                $code -> save();                
                $user['verified_at'] = now();
                $user -> save();
                return $this -> success(null,'Code matched');
            }
        }

        return $this -> failure('Did not match the code',400);
    }    

    //Verify Mail
    public function verifyMailOtp(Request $request){
        //Verify Email
        if (!isset($request -> code)) return $this -> failure('Code is required',400);
        if (isset($request -> email)){
            $code = OtpCodes::orderBy('created_at','DESC')->where('expired_at',null)->first();
            if (!$code) return $this -> failure('The code sent is expired',400);
            $isMatched = $code['code'] == $request -> code;
            if ($isMatched) {
                $code['expired_at'] = now();
                $code -> save();                
                return $this -> success(null,'Code matched');
            }
        }

        return $this -> failure('Did not match the code',400);
    }
}
