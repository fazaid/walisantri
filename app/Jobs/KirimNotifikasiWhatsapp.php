<?php

namespace App\Jobs;

use App\Services\FonnteWhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class KirimNotifikasiWhatsapp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $phoneNumber,
        public readonly string $message,
    ) {}

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(FonnteWhatsAppService $whatsapp): void
    {
        $whatsapp->send($this->phoneNumber, $this->message);
    }
}
