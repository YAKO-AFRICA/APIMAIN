<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    public function getUserByPartner(Request $request)
    {
        DB::BeginTransaction();
        try {

            $codePartenaire = $request->codepartenaire;

            if($codePartenaire == null){
                return response()->json(
                    [
                        'message' => 'Code partenaire manquant',
                        'success' => false,
                        'status' => 400
                    ]
                );
            }

            $users = User::where('codepartenaire', $codePartenaire)->with('membre')->get();

            return response()->json(
                [
                    'status' => 200,
                    'success' => true,
                    'Nbre user Trouve' => count($users),
                    'message' => 'Liste des utilisateurs du partenaire '.$codePartenaire,
                    'codePartenaire' => $codePartenaire,
                    'data' => $users,
                ]
            );

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getAllUserAssurFin()
    {
        DB::BeginTransaction();
        try {

            $users = User::where('codepartenaire', 'ASSFIN')->with('membre')->get();

            return response()->json(
                [
                    'status' => 200,
                    'success' => true,
                    'Nbre user Trouve' => count($users),
                    'message' => 'Liste des utilisateurs',
                    'data' => $users,
                ]
            );

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function userCheck(Request $request)
    {
        DB::beginTransaction();

        Log::info($request->all());

        try {

            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $email = $request->email;
            $password = $request->password;

            // Vérifier si l'email existe
            $user = User::where('email', $email)->with('membre', 'role')->first();
            

            if (!$user) {
                return response()->json([
                    'message' => 'Email inconnu dans notre système',
                    'success' => false,
                    'status' => 400
                ], 400);
            }

            // Vérifier le mot de passe
            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'message' => 'Mot de passe incorrect',
                    'success' => false,
                    'status' => 400
                ], 400);
            }

            $allNotifications = $user->notifications;

            // Ici connexion OK
            DB::commit();

            return response()->json([
                'message' => 'Utilisateur connecté',
                'success' => true,
                'status' => 200,
                'user' => $user,
                'all_notifications' => $allNotifications
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage(),
                'success' => false,
                'status' => 500
            ], 500);
        }
    }

    public function productByReseau($codeReseau)
    {
        DB::BeginTransaction();
        try {

            $productByReseau = ReseauProduct::select('CodeProduit')->where('codereseau', $codeReseau)->get();

            $codeProduits = $productByReseau->pluck('CodeProduit')->toArray();

            $products = Product::whereIn('CodeProduit', $codeProduits)->get();
            

            return response()->json(
                [
                    'status' => 200,
                    'success' => true,
                    'Nbre product Trouve' => count($products),
                    'message' => 'Liste des produits du reseau '.$codeReseau,
                    'data' => $products,
                ]
            );

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    

}
