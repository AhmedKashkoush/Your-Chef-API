<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
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

class UserController extends Controller
{
    use ResponseTrait;
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
        if (isset($request -> phone)){
            $isSent = OtpCodes::create([
                'phone' => $request -> phone,
                'code' => $otp,
            ]);

            if (!$isSent) return $this -> failure('Something went wrong',400);
            $user = User::where('phone',$request -> phone)->get();
            Notification::sendNow($user,new SMSNotification($otp));
            
            return $this -> success(null,'Code sent');
        }

        return $this -> failure('Something went wrong',400);
    }

    public function verifyOtp(Request $request){
        $user = User::where('phone',$request -> phone) -> first();
        //Check if user is verified
        if (isset($user['verified_at'])) return $this -> failure('This user is already verified',400);
        //Verify Phone
        if (!isset($request -> code)) return $this -> failure('Code is required',400);
        if (isset($request -> phone)){
            $code = OtpCodes::orderBy('created_at','DESC')->where('expired_at',null)->first();
            $isMatched = $code['code'] == $request -> code;
            if ($isMatched) {
                $code['expired_at'] = now();
                $code -> save();                
                $user['verified_at'] = now();
                $user -> save();
                return $this -> success(null,'Code matched');
            }
        }

        return $this -> failure('Something went wrong',400);
    }

    public function login(Request $request){
        $valid = validator($request->all(),        
            [
                'email' => 'max:255',
                'password' => 'min:8|max:255'
            ]);
        if (!$valid->fails()){
            $user = User::query() -> get() -> where(['email'],$request->email) -> first();
            $success = Hash::check($request -> password,$user['password']);
            if ($success) {
                if (isset($user['image'])){
                    $user['image'] = storage_path('app/'.$user['image']);
                }
                return $this -> success($user);
            }
            return $this -> failure('Something went wrong',400);
        }
        return $this -> failure($valid -> errors() -> first(),400);
    }

    public function uploadPhoto(Request $request){
        if ($request->hasFile('photo')){
            try{
                if (isset($request-> email))
                {
                    $file = $request->file('photo');
                    $fileName = time().$file->getClientOriginalName();
                    $path = 'Users/ProfilePhotos/'. $request -> email;
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

    public function allPhotos(Request $request){
        try{
            if (isset($request-> email))
            {
                $photos = Storage::allFiles('Users/ProfilePhotos/'.$request -> email);
                for ($i = 0;$i < count($photos); $i ++){
                    $photos[$i] = storage_path('app/'.$photos[$i]);
                }
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
}