<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SingPayService
{
    protected string $baseUrl;

    protected string $walletId;

    protected ?string $clientId;

    protected ?string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.singpay.base_url'), '/');
        $this->walletId = config('services.singpay.wallet_id', '');
        $this->clientId = config('services.singpay.client_id');
        $this->clientSecret = config('services.singpay.client_secret');
    }

    /**
     * Initie un paiement auprès de SingPay (Airtel Money ou Moov Money).
     */
    public function initiatePayment(Payment $payment): array
    {
        // Codes opérateurs SingPay : 74 pour Airtel, 62 pour Moov
        $operatorCode = $payment->operator === 'airtel_money' ? '74' : '62';
        $endpoint = "{$this->baseUrl}/{$operatorCode}/paiement";

        // Nettoyage et formatage du numéro (ex: +24166000000 -> 24166000000)
        $phone = preg_replace('/[^0-9]/', '', $payment->phone);
        if (str_starts_with($phone, '0')) {
            $phone = '241'.substr($phone, 1);
        }

        $payload = [
            'portefeuille' => $this->walletId,
            'montant' => (int) ($payment->amount / 100), // SingPay prend les montants en FCFA entiers
            'numero' => $phone,
            'reference' => 'PAY-'.$payment->id.'-'.time(),
            'callback_url' => route('singpay.webhook'),
        ];

        Log::info("SingPay Payment Request to {$endpoint}", [
            'payment_id' => $payment->id,
            'payload' => array_merge($payload, ['numero' => '***']),
        ]);

        // Mock en environnement de test CI/CD
        if (app()->environment('testing')) {
            return [
                'success' => true,
                'transaction_id' => 'mock_sp_'.uniqid(),
                'message' => 'Paiement simulé en test',
            ];
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($endpoint, $payload);

            if ($response->failed()) {
                Log::error('SingPay Payment Failed status code: '.$response->status(), [
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Erreur SingPay : '.($response->json('message') ?? 'Code erreur '.$response->status()),
                ];
            }

            $data = $response->json();

            Log::info('SingPay Payment Response Success', [
                'payment_id' => $payment->id,
                'response' => $data,
            ]);

            return [
                'success' => true,
                'transaction_id' => $data['transaction_id'] ?? $data['id'] ?? null,
                'raw' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('SingPay Payment Exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Exception lors de la connexion à SingPay.',
            ];
        }
    }

    /**
     * Vérifie le statut d'une transaction SingPay par son identifiant.
     */
    public function checkTransactionStatus(string $transactionId): array
    {
        $endpoint = "{$this->baseUrl}/transaction/api/status/{$transactionId}";

        if (app()->environment('testing')) {
            return [
                'status' => 'successful',
                'raw' => ['status' => 'successful'],
            ];
        }

        try {
            $response = Http::withHeaders($this->getHeaders())->get($endpoint);

            if ($response->failed()) {
                return [
                    'status' => 'failed',
                    'message' => 'Erreur statut SingPay.',
                ];
            }

            $data = $response->json();
            // Statuts possibles de SingPay : successful, failed, pending
            $status = $data['status'] ?? 'pending';

            return [
                'status' => $status,
                'raw' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('SingPay Status Query Exception: '.$e->getMessage());

            return [
                'status' => 'pending',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Génère les en-têtes d'authentification pour SingPay.
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->clientId) {
            $headers['x-client-id'] = $this->clientId;
        }

        if ($this->clientSecret) {
            $headers['Authorization'] = 'Bearer '.$this->clientSecret;
            $headers['x-client-secret'] = $this->clientSecret;
        }

        return $headers;
    }
}
