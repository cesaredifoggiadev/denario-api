<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest\SignUp;
use App\Services\AuthService;
use Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use Cartalyst\Sentinel\Laravel\Facades\Activation;
use Cartalyst\Sentinel\Laravel\Facades\Reminder;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function login(Request $request)
    {

        $request->validate([
            "email"=> "required|email",
            "password"=> "required"
        ]);
        try {
            $data = [];
            if ($request->has('email')) {
                $data = $request->only(['email', 'password']);
            } else {
                $data = [
                    'email' => $request->post('username'),
                    'password' => $request->post('password')
                ];
            }

            if ($user = Sentinel::authenticate($data, $request->post('remember-me', 120))) {
                $token = JWTAuth::attempt($data);
                return $this->successResponse([
                    'user' => $user,
                    'token' => $token
                ]);
            }

            unset($data['password']);
            return $this->errorResponse('Nome utente o password non corrette', 401, $data);
        } catch (NotActivatedException $e) {
            return $this->errorResponse($e->getMessage(), 402, $e->getTrace());
        } catch (ThrottlingException $e) {
            return $this->errorResponse($e->getMessage(), 403, $e->getTrace());
        }
        catch(\Exception $e){
            return $this->errorResponse($e->getMessage(), 401, $e->getTrace());
        }
    }

 
    //Register Users Function
    public function signup(SignUp $request)
    {      
        try {
            $user = Sentinel::register(
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'type_id' => 1,
                ]
            ); 

            AuthService::createActivation($user);
            
        } catch (NotActivatedException $e) {
            return $this->errorResponse($e->getMessage(), 500, $e->getTrace());
        } catch (ThrottlingException $e) {
            return $this->errorResponse($e->getMessage(), 500, $e->getTrace());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, $e->getTrace());
        }
    }

    public function activation(Request $request){  

        $request->validate([
            "email" => "required|email",
        ]);
        try{
            $user = Sentinel::findByCredentials(['login' => $request->email]);
           
            if(!$user)
                return $this->errorResponse('L\'utente non esiste', 404);

            AuthService::createActivation($user); 
        
            return $this->successResponse([
                'user_id'=> $user->id,
                'email'=> $user->email
            ]);
        }
        catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400, $e->getTrace());
        }
    }


    public function activate($userId, $code){
       /* $qr = DB::table('activations')
        ->where(['user_id' => $userId, 'code'=>$code])
        ->update(['completed' => 1, 'completed_at'=> now()]);
        */

        $user = Sentinel::findById($userId);
        if (Activation::complete($user, $code))
        {
            $user = Sentinel::login($user);
            return $this->successResponse([
                'user' => $user,
            ]);
        }
        else
        {
            return $this->errorResponse('Errore attivazione utente', 400);
        }
    }

    public function reminder(Request $request){  

        $request->validate([
            "email" => "required|email",
        ]);
        try{
            $user = Sentinel::findByCredentials(['login' => $request->email]);
           
            if(!$user)
                return $this->errorResponse('L\'utente non esiste', 404);

            AuthService::createReminder($user); 

          
        
            return $this->successResponse([
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }
        catch (\Exception $e) {
            return $this->errorResponse('Errore attivazione utente', 400);
        }
    }

    public function checkReminder($userId, $code){

        try{
            $user = Sentinel::findById($userId);
            if (Reminder::exists($user, $code)){
                return $this->successResponse([
                    'user_id'=> $userId,
                    'email' => $user->email,
                    'code' => $code
                ]);
            } else {
                return $this->errorResponse('Errore, il codice non esiste o è scaduto', 400);
            }
        }
        catch (\Exception $e) {
            return $this->errorResponse('Errore, il codice non esiste o è scaduto', 400);
        }
    }

    public function reset(Request $request){

        $request->validate([
            'user_id'=> "required",
            'code' => "required",
            'password' => 'required|between:6,32',
            'password_confirm' => 'required|same:password'
        ],[],[
            'password_confirm' => "conferma password"
        ]);
        try{
            $user = Sentinel::findById($request->user_id);
            if(Reminder::complete($user, $request->code, $request->password)){
                return $this->successResponse('password reset');
            }
            else{
                return $this->errorResponse('Errore, non è possibile effettuare il reset password', 400);
            }
           
        }
        catch (\Exception $e) {
            return $this->errorResponse('Errore reset psw', 400);
        }

    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        Sentinel::logout();

        return $this->successResponse([
            'message' => 'logout ok',
        ]);

    }
}
