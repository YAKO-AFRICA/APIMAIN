<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{

    protected $SMSService;

    public function __construct(SMSService $SMSService)
    {
        $this->SMSService = $SMSService;
    }
    public function sendSms(Request $request)
    {
        DB::beginTransaction();
        try {

            $phone = preg_replace('/\D/', '', $request->phone);
            $phone = substr($phone, -10);
            $phoneNumber = '+225' . $phone;
            $dataMessage = $request->message;
            $response = $this->SMSService->sendSmsByInfobipAPI($phoneNumber, $dataMessage);
            
            // // Vérifier si une erreur s'est produite
            if (isset($response['error'])) {
                return response()->json(['error' => $response['error']], 500); // Retourne une erreur
            }else{
                return response()->json([
                    'success' => true,
                    'message' => 'Le message a été envoyé avec succès.',
                    'status' => 200,
                ]);
            }
           DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
