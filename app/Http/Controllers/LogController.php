<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    /**
     * Récupère et enregistre les logs des requêtes
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequestLogs(Request $request)
    {
        // Préparer les données du log
        $logData = [
            'timestamp' => now()->toDateTimeString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ];

        // Journaliser dans le fichier Laravel par défaut
        Log::info('Request received', $logData);

        // Journaliser dans un fichier personnalisé 'log_pay'
        $this->writeToCustomLog($logData);

        // Retourner une réponse (optionnel)
        return response()->json([
            'success' => true,
            'message' => 'Request logged successfully'
        ]);
    }

    /**
     * Écrit les logs dans un fichier personnalisé
     *
     * @param array $logData
     * @return void
     */
    private function writeToCustomLog(array $logData)
    {
        // Chemin vers le fichier log_pay
        $logPath = storage_path('logs/log_pay.log');
        
        // Formater les données en JSON
        $logEntry = json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        $logEntry = "[" . date('Y-m-d H:i:s') . "] " . $logEntry;
        
        // Écrire dans le fichier
        File::append($logPath, $logEntry);
    }

    /**
     * Crée un log spécifique pour les paiements
     *
     * @param array $paymentData
     * @return void
     */
    public function logPayment(array $paymentData)
    {
        $paymentLog = [
            'timestamp' => now()->toDateTimeString(),
            'type' => 'payment',
            'data' => $paymentData,
        ];

        $this->writeToCustomLog($paymentLog);
    }
}