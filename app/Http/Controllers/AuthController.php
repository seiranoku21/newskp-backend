<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Users;
use App\Http\Requests\UsersRegisterRequest;
use Exception;
use App\Helpers\JWTHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller{
	

	/**
     * Get user login data
     * @return array
     */
	private function getUserLoginData($user = null){
		if(!$user){
			$user = auth()->user();
		}
		$accessToken = $user->createToken('authToken')->accessToken;
        return ['token' => $accessToken];
	}
	

	/**
     * Authenticate and login user
     * @return \Illuminate\Http\Response
     */
	function login(Request $request){
		$username = $request->username;
		$password = $request->password;
		if(filter_var($username, FILTER_VALIDATE_EMAIL)) {
			Auth::attempt(['email' => $username, 'password' => $password]); //login with email 
		} 
		else {
			Auth::attempt(['username' => $username, 'password' => $password]); //login with username
		}
        if (!Auth::check()) {
            return $this->reject("Username or password not correct", 400);
        }
		$user = auth()->user();
		$loginData = $this->getUserLoginData($user);
        return $this->respond($loginData);
	}
	

	/**
     * generate token with user id
     * @return string
     */
	private function generateUserToken($user = null){
		return JWTHelper::encode($user->user_id);
	}
	

	/**
     * validate token and get user id
     * @return string
     */
	private function getUserIDFromJwt($token){
		$userId =  JWTHelper::decode($token);
 		return $userId;
	}
}
