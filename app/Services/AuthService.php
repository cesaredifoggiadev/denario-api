<?php
namespace App\Services;

use App\Mail\ForgotPassword;
use App\Mail\Register;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class AuthService
{
    public static function generateSecureCode(){
        $code = '';
        for ($i = 0; $i<6; $i++) 
        {
            $code .= mt_rand(0,9);
        }
        return $code;
    }

    public static function createReminder($user){
        $code = self::generateSecureCode(); 
        DB::table('reminders')->where(['user_id'=> $user->id,'completed'=> 0])->delete();
        $created = DB::table('reminders')->insert(['user_id'=> $user->id,'code'=>$code,'created_at'=>now()]);
        if($created){
            $data=[
                'user_name' => $user->name,
                'code' => $code
            ];
            Mail::to($user->email)->send(new ForgotPassword($data));
        }
       
        return $created;
    }
    
    public static function createActivation($user){
        $code = self::generateSecureCode();
        DB::table('activations')->where(['user_id'=> $user->id,'completed'=> 0])->delete();
        $created = DB::table('activations')->insert(['user_id'=> $user->id,'code'=>$code,'created_at'=>now()]);
        if($created){
            $aUrl = URL::route('api.activate', [$user->id, $code]);
            $data=[
                'user_name' => $user->name,
                'activationUrl' => $aUrl,
                'code' => $code
            ];
            Mail::to($user->email)->send(new Register($data));
        }
        return $created;
       
    }
}