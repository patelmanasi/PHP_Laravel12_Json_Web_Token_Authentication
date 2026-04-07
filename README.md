# PHP_Laravel12_Json_Web_Token_Authentication


## Introduction

This project demonstrates **JWT (JSON Web Token) Authentication** in **Laravel 12**.  
It allows users to **register, login, logout, refresh token, and view profile** via an API using JWT authentication.  

JWT authentication ensures **stateless and secure API communication**, which is essential for modern web and mobile applications.

---

## Project Overview

- Users can **register** with name, email, and password.
- Users can **login** to receive a JWT token.
- Users can **logout**, which invalidates the token.
- Users can **refresh** their token.
- Users can **view their profile** via a protected route.
- API responses are always **JSON-formatted**, including errors.

---


##  Project Structure

```
PHP_Laravel12_Json_Web_Token_Authentication/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   │   │       ├── BaseController.php      # Handles standard API responses
│   │   │       └── AuthController.php      # JWT authentication logic (register, login, logout, refresh, profile)
│   ├── Models/
│   │   └── User.php                         # User model (default Laravel users table)
├── config/
│   └── auth.php                             # Auth configuration (guards & providers for JWT)
├── database/
│   ├── migrations/
│       └── 2014_10_12_000000_create_users_table.php  # Default Laravel users table migration
│          
├── routes/
│   └── api.php                              # API routes for JWT authentication
├── .env                                     # Environment variables (DB, JWT_SECRET, etc.)
├── composer.json
├── package.json
├── phpunit.xml
├── artisan
└── README.md                                # Full project setup and instructions
```



---

##  Requirements

- PHP >= 8.1
- Laravel 12
- MySQL / MariaDB
- Composer
- Postman (for testing APIs)

---

## Step 1: Project Setup

Open terminal:

```bash
composer create-project laravel/laravel PHP_Laravel12_Json_Web_Token_Authentication "12.*"
cd PHP_Laravel12_Json_Web_Token_Authentication
```

---


## Step 2: .env Configuration

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jwt_auth
DB_USERNAME=root
DB_PASSWORD=
```

After run this command to create database:

```bash
php artisan migrate
```

---


## Step 3: Enable API Scaffolding & Handle Authentication Exceptions

To enable Laravel API features & prepare JSON error responses:

```
php artisan install:api
```

update bootstrap/app.php to ensure API exceptions return JSON:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
     ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 401);
            }
        });

    })
    ->create();
```
Purpose: Ensures that all API routes return JSON for unauthorized requests.

---


## Step 4: Install JWT Package

Install the JWT package using Composer:

```
composer require php-open-source-saver/jwt-auth
```

Publish the configuration:

```
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
```

Generate a JWT secret key:

```
php artisan jwt:secret
```

This will create a JWT_SECRET key in your .env file.

This should add:

```
JWT_SECRET=xxxxx
```

---


## Step 5: Configure auth.php Guards

Edit config/auth.php:

```
'defaults' => [
    'guard' => 'api',
    'passwords' => 'users',
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

This tells Laravel to use JWT for API authentication. 


---


Step 6: Update User Model

app/Models/User.php:

Modify app/Models/User.php to implement JWTSubject:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/*
|--------------------------------------------------------------------------
| JWT Interface
|--------------------------------------------------------------------------
| This interface is required by jwt-auth package.
| It tells Laravel how to identify and create JWT tokens for the user.
*/
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Attributes
    |--------------------------------------------------------------------------
    | These fields can be filled using User::create()
    */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden Attributes
    |--------------------------------------------------------------------------
    | These attributes will be hidden when returning user as JSON
    */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    | Laravel 12 uses method-based casting
    */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Automatically hashes password
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | JWT Required Methods
    |--------------------------------------------------------------------------
    | These two methods are mandatory for JWT authentication
    */

    /**
     * Get the identifier that will be stored in the JWT.
     * Usually the user's primary key (id).
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return custom claims for JWT.
     * We are not adding any extra claims now.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

---


## Step 7: Create Controllers

7.1 BaseController

Command to create controller:

```bash
php artisan make:controller API/BaseController
```

app/Http/Controllers/API/BaseController.php:

```php
<?php
namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

/*
|--------------------------------------------------------------------------
| BaseController
|--------------------------------------------------------------------------
| This controller is the parent for all API controllers.
| It provides standard methods for API responses (success & error).
*/
class BaseController extends Controller
{
    /**
     * Send a standard success response
     *
     * @param mixed $result Data to send in response
     * @param string $message Message to send
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse($result, $message)
    {
        // Prepare response array
        $response = [
            'success' => true,    // Indicates success
            'data'    => $result, // Response data
            'message' => $message,// Response message
        ];

        // Return JSON response with HTTP status 200
        return response()->json($response, 200);
    }

    /**
     * Send a standard error response
     *
     * @param string $error Error message
     * @param array $errorMessages Optional additional error data
     * @param int $code HTTP status code (default 404)
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        // Prepare response array
        $response = [
            'success' => false, // Indicates failure
            'message' => $error // Main error message
        ];

        // If additional error messages exist, add them
        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        // Return JSON response with the specified HTTP code
        return response()->json($response, $code);
    }
}
```

7.2 AuthController

Command:

```bash
php artisan make:controller API/AuthController
```

app/Http/Controllers/API/AuthController.php:

```php
<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;

/*
|--------------------------------------------------------------------------
| AuthController
|--------------------------------------------------------------------------
| This controller handles all authentication-related actions for the API.
| Functions include register, login, logout, refresh token, and profile retrieval.
| It extends BaseController to use standard API response methods.
*/
class AuthController extends BaseController
{
    /**
     * Register a new user
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password', // Confirm password must match
        ]);

        // If validation fails, return error
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        // Hash password and create user
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        $success['user'] = $user;

        // Return success response
        return $this->sendResponse($success, 'User register successfully.');
    }

    /**
     * Login user and return JWT token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        // Get credentials from request
        $credentials = request(['email', 'password']);

        // Attempt to authenticate and generate token
        if (!$token = auth()->attempt($credentials)) {
            return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
        }

        // Return token in response
        $success = $this->respondWithToken($token);

        return $this->sendResponse($success, 'User login successfully.');
    }

    /**
     * Get authenticated user profile
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        // Retrieve authenticated user
        $success = auth()->user();

        return $this->sendResponse($success, 'User profile retrieved successfully.');
    }

    /**
     * Logout user (invalidate token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout(); // Invalidate JWT token

        return $this->sendResponse([], 'Successfully logged out.');
    }

    /**
     * Refresh JWT token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $success = $this->respondWithToken(auth()->refresh());

        return $this->sendResponse($success, 'Refresh token returned successfully.');
    }

    /**
     * Format JWT token response
     *
     * @param string $token
     * @return array
     */
    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,               // The JWT token
            'token_type'   => 'bearer',             // Token type
            'expires_in'   => auth()->factory()->getTTL() * 60 // Expiration time in seconds
        ];
    }
}
```

This handles API registration, login, logout, refresh & profile.

---


## Step 8: Configure API Routes

routes/api.php:

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

/*
|--------------------------------------------------------------------------
| API Authentication Routes
|--------------------------------------------------------------------------
| These routes handle user authentication using JWT.
| All routes are prefixed with /auth
| The "api" middleware enables JSON-based API handling
*/
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    | These routes do NOT require authentication
    */

    // Register a new user
    Route::post('/register', [AuthController::class, 'register']);

    // Login user and generate JWT token
    Route::post('/login',    [AuthController::class, 'login']);

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    | These routes require a valid JWT token
    | auth:api middleware validates the token
    */

    // Logout user and invalidate JWT token
    Route::post('/logout',   [AuthController::class, 'logout'])
        ->middleware('auth:api');

    // Refresh JWT token
    Route::post('/refresh',  [AuthController::class, 'refresh'])
        ->middleware('auth:api');

    // Get authenticated user profile
    Route::post('/profile',  [AuthController::class, 'profile'])
        ->middleware('auth:api');
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
```

---


## Step 9: Start Server

```bash
php artisan serve
```

---


## step 10: JWT Authentication Postman Example

Base URL

```
http://127.0.0.1:8000/api
```

Change 127.0.0.1:8000 to your server URL if different.


**1) Register User**

Request

POST /api/auth/register


Headers

Accept: application/json
Content-Type: application/json


Body (raw JSON)

```
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "123456",
    "c_password": "123456"
}
```

Response Example

```
{
    "success": true,
    "data": {
        "user": {
            "name": "John Doe",
            "email": "john@example.com",
            "updated_at": "2026-01-01T06:00:00.000000Z",
            "created_at": "2026-01-01T06:00:00.000000Z",
            "id": 1
        }
    },
    "message": "User register successfully."
}
```

**2) Login User**

Request

POST /api/auth/login


Headers

Accept: application/json
Content-Type: application/json


Body (raw JSON)

```
{
    "email": "john@example.com",
    "password": "123456"
}
```

Response Example

```
{
    "success": true,
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    },
    "message": "User login successfully."
}
```

Copy access_token for protected routes.


**3) Get User Profile (Protected Route)**

Request

POST /api/auth/profile


Headers

Accept: application/json
Authorization: Bearer <access_token>


Body

```
None
```

Response Example

```
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2026-01-01T06:00:00.000000Z",
        "updated_at": "2026-01-01T06:00:00.000000Z"
    },
    "message": "User profile retrieved successfully."
}
```

**4) Refresh Token (Protected Route)**

Request

POST /api/auth/refresh


Headers

Accept: application/json
Authorization: Bearer <access_token>


Response Example

```
{
    "success": true,
    "data": {
        "access_token": "new_eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    },
    "message": "Refresh token returned successfully."
}
```

Use this new token for subsequent requests.


**5) Logout User (Protected Route)**

Request

POST /api/auth/logout


Headers

Accept: application/json
Authorization: Bearer <access_token>


Body

```
None
```

Response Example

```
{
    "success": true,
    "data": [],
    "message": "Successfully logged out."
}
```

---

Notes for Postman

Always include Authorization: Bearer <token> for protected routes (profile, refresh, logout).

Use raw JSON body for POST requests.

Make sure JWT token is generated from the login route before calling protected routes.

Set Accept: application/json to ensure JSON responses.


---

## Output

**Register**

<img width="1384" height="999" alt="Screenshot 2026-01-01 113255" src="https://github.com/user-attachments/assets/094fe2c3-f147-46e1-8f4f-6e28eb9ce4f9" />

**Login**

<img width="1384" height="1004" alt="Screenshot 2026-01-01 113758" src="https://github.com/user-attachments/assets/fc355bea-2296-4418-8fd2-871821dccd28" />

**Profile**

<img width="1386" height="1007" alt="Screenshot 2026-01-01 114404" src="https://github.com/user-attachments/assets/190d65af-8c7c-4ab9-9de3-ed0b41325671" />

**Refresh**

<img width="1384" height="1004" alt="Screenshot 2026-01-01 114459" src="https://github.com/user-attachments/assets/69e32ede-5716-44b9-ae9a-f288a0517de7" />

**Logout**

<img width="1388" height="998" alt="Screenshot 2026-01-01 114728" src="https://github.com/user-attachments/assets/705445f9-8353-441d-a280-593e68581cdf" />



---

Your PHP_Laravel12_Json_Web_Token_Authentication Project is Now Ready!
<<<<<<< HEAD

=======
>>>>>>> origin/main
