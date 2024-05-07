<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\repository\usersRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private $_repository;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(usersRepository $usersRepository)
    {
        $this->_repository = $usersRepository;
        //$this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {


            $user = $this->_repository->findOneByMail($credentials['email']);
            if (is_null($user))
            {
                return response()->json(['error' => 'identifiant non trouvé'], 401);
            }else
            {
                if (!Hash::check($credentials['password'], $user->password))
                {
                    return response()->json(['error' => 'mot de passe incorrect'], 401);
                }
            }

        }
        return $this->respondWithToken($token);



    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
    public function register(Request $request)
    {
        try {
            // Validation des données de la requête
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|min:8',
                'nom' => 'required',
                'prenom' => 'required',
                'age' => 'required|integer|min:18',
            ]);

            // Vérifier si la validation a échoué
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Récupérer les informations validées de la requête
            $credentials = $request->only('email', 'password', 'nom', 'prenom', 'age');

            // Hasher le mot de passe avant de l'enregistrer en base de données
            $credentials['password'] = Hash::make($credentials['password']);

            // Enregistrer l'utilisateur en base de données
            DB::table('users')->insert($credentials);

            // Tentative de connexion de l'utilisateur nouvellement enregistré
            $token = auth()->attempt($request->only('email', 'password'));

            // Retourner une réponse JSON avec le token d'authentification
            return response()->json([
                'success' => true,
                'data' => $this->respondWithToken($token)
            ]);
        } catch (ValidationException $e) {
            // En cas d'erreur de validation, retourner les erreurs au format JSON
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 422);
        }
    }
}
