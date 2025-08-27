<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Users;
use App\Http\Requests\UsersRegisterRequest;
use Exception;
use App\Helpers\JWTHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller{

	private function getUserLoginData($user = null){
		if(!$user){
			$user = auth()->user();
		}
		
		// Cek user Login via SSO atw Simpeg
		if($user->sso_token || $user->simpeg_token) {
			// Untuk User SSO/Simpeg , gunakan token yg ada masing2
			return [
				'token' => $user->sso_token ?? $user->simpeg_token,
				'is_sso' => !empty($user->sso_token),
				'is_simpeg' => !empty($user->simpeg_token)
			];
		}
		
		// Untuk User Reguler, buat token baru
		$accessToken = $user->createToken('authToken')->accessToken;
		return [
			'token' => $accessToken,
			'is_sso' => false,
			'is_simpeg' => false
		];
	}
	
	private function checkSsoUser($userData) {
		// Implement logic to find or create user based on SSO data
		// Example: Look up user by 'sso_id' or 'email'
		$user = Users::where('email', $userData['email'])->first();

		if (!$user) {
			// Create new user if not found
			$user = new Users();
			$user->username = $userData['username'] ?? $userData['email'];
			$user->email = $userData['email'];
			$user->password = bcrypt(Str::random(16)); // Generate a random password
			$user->email_verified_at = now();
			$user->user_role_id = 1; // Default role
			$user->is_active = 1;
			// ... other user fields from SSO data ...
			$user->save();
		}
		return $user;
	}

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

	function ssoLogin(Request $request) {
		try {
			// Assuming SSO/SPL provides user data in the request body or query parameters
			$ssoUserData = $request->all(); // Placeholder - adjust based on actual SSO/SPL response

			// Validate required SSO data
			$validator = Validator::make($ssoUserData, [
				'email' => 'required|email',
				// Add other validation rules for SSO data
			]);

			if ($validator->fails()) {
				return $this->reject($validator->errors(), 400);
			}

			$user = $this->checkSsoUser($ssoUserData);

			// Generate JWT for the authenticated user
			$accessToken = $this->generateUserToken($user);

			return $this->respond([
				'token' => $accessToken,
				'is_sso' => true,
				'is_simpeg' => false,
				'user' => $user // Optionally return user data
			]);

		} catch (Exception $e) {
			return $this->reject("SSO Login failed: " . $e->getMessage(), 500);
		}
	}
	
	private function generateUserToken($user = null){
		return JWTHelper::encode($user->user_id);
	}
	
	private function getUserIDFromJwt($token){
		$userId =  JWTHelper::decode($token);
 		return $userId;
	}
}
