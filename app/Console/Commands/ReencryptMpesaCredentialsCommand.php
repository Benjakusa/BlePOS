<?php

namespace App\Console\Commands;

use App\Models\MpesaCredential;
use App\Services\MpesaEncryptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('mpesa:reencrypt-credentials')]
#[Description('Re-encrypt stored M-Pesa credentials with the active key version.')]
class ReencryptMpesaCredentialsCommand extends Command
{
    public function handle(MpesaEncryptionService $encryption): int
    {
        $count = 0;
        MpesaCredential::query()->orderBy('id')->chunkById(100, function ($credentials) use ($encryption, &$count): void {
            foreach ($credentials as $credential) {
                $fields = ['passkey', 'consumer_key', 'consumer_secret', 'initiator_name', 'security_credential'];
                $changes = [];
                foreach ($fields as $field) {
                    if (is_string($credential->{$field}) && $credential->{$field} !== '') {
                        $changes[$field] = $encryption->reencryptForActiveVersion($credential->{$field});
                    }
                }
                if ($changes !== []) {
                    $credential->forceFill($changes)->saveQuietly();
                    $count++;
                }
            }
        });

        $this->components->info("Re-encrypted {$count} M-Pesa credential records with the active key version.");

        return self::SUCCESS;
    }
}
