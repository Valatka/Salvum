<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use App\Transformers\AuthTransformer;
use App\Transformers\StatusTransformer;
use League\Fractal\Manager;

class AuthController extends Controller {
    /**
     * @var Manager
     */
    private $fractal;

    /**
     * @var AuthTransformer
     */
    private $authTransformer;

    /**
     * @var StatusTransformer
     */
    private $statusTransformer;


     /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(Manager $fractal, AuthTransformer $authTransformer, StatusTransformer $statusTransformer) {
        $this->middleware('auth:api', ['except' => ['login']]);
        $this->fractal = $fractal;
        $this->authTransformer = $authTransformer;
        $this->statusTransformer = $statusTransformer;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email:rfc'],
            'password' => ['required', Password::min(4)]
        ]);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json($this->statusTransformer-transform(false), 401);
        }

        return response()->json($this->authTransformer->transform($token));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();

        return response()->json($this->statusTransformer->transform(true));
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return response()-json($this->authTransformer->transform(auth()->refresh()));
    }
}
