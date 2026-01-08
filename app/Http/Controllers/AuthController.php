<?php 
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Users;
use App\Http\Requests\UsersRegisterRequest;
use App\Http\Requests\SsoLoginRequest;
use Exception;
use App\Helpers\JWTHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Google\Client as Google_Client;

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

	/**
	 * SSO Login via Google
	 * Endpoint: POST /api/auth/sso
	 * 
	 * @param SsoLoginRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	function sso(SsoLoginRequest $request) {
		try {
			$provider = $request->input('provider');
			$idToken = $request->input('id_token');
			$email = $request->input('email');
			$name = $request->input('name');
			$picture = $request->input('picture');

			// Verify Google ID token
			if ($provider === 'google') {
				$client = new Google_Client(['client_id' => config('services.google.client_id')]);
				
				try {
					$payload = $client->verifyIdToken($idToken);
				} catch (Exception $e) {
					return response()->json([
						'error' => 'Invalid ID token',
						'message' => 'Google ID token verification failed: ' . $e->getMessage()
					], 401);
				}

				if (!$payload) {
					return response()->json([
						'error' => 'Invalid ID token',
						'message' => 'Google ID token verification failed'
					], 401);
				}

				// Verify email matches
				if ($payload['email'] !== $email) {
					return response()->json([
						'error' => 'Email mismatch',
						'message' => 'The email in the token does not match the provided email'
					], 401);
				}

				// Find or create user
				$user = Users::where('email', $email)->first();

				if (!$user) {
					// Option A: Auto-register user
					$user = new Users();
					$user->username = $email;
					$user->email = $email;
					$user->name_info = $name ?? $payload['name'] ?? $email;
					$user->photo = $picture ?? $payload['picture'] ?? null;
					$user->password = bcrypt(Str::random(32)); // Random password for SSO users
					$user->email_verified_at = now(); // Auto-verify email for Google users
					$user->user_role_id = 2; // Default role (adjust as needed)
					$user->auth_provider = 'google';
					$user->save();
				} else {
					// Update existing user's photo if provided
					if ($picture || isset($payload['picture'])) {
						$user->photo = $picture ?? $payload['picture'];
						$user->save();
					}
				}

					// Option B: Return error if user not found
					// return response()->json([
					//     'error' => 'User not found',
					//     'message' => 'No user account found with this email address'
					// ], 404);
				}

				// Generate JWT token using existing helper (menggunakan JWT_SECRET yang sama dengan frontend)
				$token = $this->generateUserToken($user);

				// Set cookie untuk access_token (sesuai middleware CheckToken)
				$cookie = cookie(
					'access_token',              // nama cookie (diubah dari session_token)
					$token,                     // value (JWT token)
					config('auth.jwt_duration'), // durasi dalam menit
					'/',                        // path
					null,                       // domain
					false,                      // secure (set true di production dengan HTTPS)
					true,                       // httpOnly
					false,                      // raw
					'lax'                       // sameSite
				);

				// Return response dengan cookie dan data user
				return response()->json([
					'token' => $token,
					'user' => [
						'id' => $user->user_id,
						'username' => $user->username,
						'email' => $user->email,
						'name' => $user->name_info,
						'picture' => $user->photo
					]
				], 200)->cookie($cookie);
			}

			return response()->json([
				'error' => 'Invalid provider',
				'message' => 'Unsupported authentication provider'
			], 400);

		} catch (Exception $e) {
			return response()->json([
				'error' => 'SSO authentication failed',
				'message' => $e->getMessage()
			], 500);
		}
	}

	/**
	 * Legacy SSO Login (kept for backward compatibility)
	 * @deprecated Use sso() method instead
	 */
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

	/**
	 * SSO Login untuk Testing (DEVELOPMENT ONLY!)
	 * Endpoint: POST /api/auth/sso-test
	 * 
	 * Endpoint ini bypass Google verification untuk keperluan testing
	 * JANGAN AKTIFKAN DI PRODUCTION!
	 * 
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	function ssoTest(Request $request) {
		// Hanya izinkan di environment development
		if (config('app.env') !== 'local' && config('app.env') !== 'development') {
			return response()->json([
				'error' => 'Forbidden',
				'message' => 'This endpoint is only available in development environment'
			], 403);
		}

		try {
			$email = $request->input('email');
			$name = $request->input('name', 'Test User');

			// Validasi basic
			if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				return response()->json([
					'error' => 'Invalid email',
					'message' => 'Please provide a valid email address'
				], 400);
			}

			// Find or create user (sama seperti SSO asli)
			$user = Users::where('email', $email)->first();

			if (!$user) {
				// Auto-register user
				$user = new Users();
				$user->username = $email;
				$user->email = $email;
				$user->name_info = $name;
				$user->password = bcrypt(Str::random(32));
				$user->email_verified_at = now();
				$user->user_role_id = 2;
				$user->auth_provider = 'google';
				$user->save();
			}

			// Generate JWT token
			$token = $this->generateUserToken($user);

			// Set cookie access_token
			$cookie = cookie(
				'access_token',
				$token,
				config('auth.jwt_duration'),
				'/',
				null,
				false,
				true,
				false,
				'lax'
			);

			// Return response
			return response()->json([
				'token' => $token,
				'user' => [
					'id' => $user->user_id,
					'username' => $user->username,
					'email' => $user->email,
					'name' => $user->name_info
				],
				'note' => 'This is a test endpoint without Google verification'
			], 200)->cookie($cookie);

		} catch (Exception $e) {
			return response()->json([
				'error' => 'SSO test failed',
				'message' => $e->getMessage()
			], 500);
		}
	}
}
