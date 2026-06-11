<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    private string $publishUrl = 'https://iae-sso.virtualfri.id/api/v1/messages/publish';

    public function publishTicketCreated(array $ticketData, string $jwtToken): bool
    {
        $payload = [
            'event'   => 'ticket.created',
            'service' => 'manajemen-tiket-tenant',
            'data'    => [
                'ticket_id'    => $ticketData['id'],
                'listing_id'   => $ticketData['listing_id'],
                'contract_id'  => $ticketData['contract_id'],
                'tenant_name'  => $ticketData['tenant_name'],
                'tenant_email' => $ticketData['tenant_email'],
                'description'  => $ticketData['description'],
                'status'       => $ticketData['status'],
                'timestamp'    => now()->toISOString(),
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwtToken,
                'Content-Type'  => 'application/json',
            ])->post($this->publishUrl, $payload);

            if ($response->successful()) {
                Log::info('RabbitMQ publish berhasil', ['event' => 'ticket.created']);
                return true;
            }

            Log::warning('RabbitMQ publish gagal', ['response' => $response->body()]);
            return false;

        } catch (\Exception $e) {
            Log::error('RabbitMQ error: ' . $e->getMessage());
            return false;
        }
    }
}