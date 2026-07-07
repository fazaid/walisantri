<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteWhatsAppService
{
    /**
     * Kirim satu pesan WA via Fonnte.
     *
     * Melempar exception untuk SEMUA jenis kegagalan (nomor tidak valid, HTTP
     * error, maupun status:false dari Fonnte) — kegagalan permanen tetap harus
     * ikut mekanisme retry/failed_jobs job pemanggil, sesuai kebijakan PRD §12
     * ("retry max 3x exponential backoff, gagal permanen -> failed_jobs").
     */
    public function send(string $phoneNumber, string $message): void
    {
        $target = $this->normalizePhoneNumber($phoneNumber);

        if ($target === null) {
            Log::warning('FonnteWhatsAppService: nomor HP tidak valid', [
                'phone_number' => $phoneNumber,
            ]);

            throw new \RuntimeException("Nomor HP tidak valid: {$phoneNumber}");
        }

        $response = Http::withHeaders([
                'Authorization' => config('services.fonnte.token'),
            ])
            ->asForm()
            ->timeout(10)
            ->post(config('services.fonnte.url'), [
                'target' => $target,
                'message' => $message,
            ]);

        $response->throw();

        $payload = $response->json() ?? [];

        if (($payload['status'] ?? false) !== true) {
            $reason = $payload['reason'] ?? '(tidak ada reason di response)';

            Log::warning('FonnteWhatsAppService: Fonnte menolak pesan', [
                'target' => $target,
                'reason' => $reason,
            ]);

            throw new \RuntimeException("Fonnte menolak pesan: {$reason}");
        }
    }

    public function normalizePhoneNumber(string $phoneNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if ($digits === '') {
            return null;
        }

        $countryCode = (string) config('services.fonnte.default_country_code', '62');

        if (str_starts_with($digits, '0')) {
            $digits = $countryCode.substr($digits, 1);
        }

        if (strlen($digits) < 9) {
            return null;
        }

        return $digits;
    }
}
