<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateExamKeysCommand extends Command
{
    protected $signature = 'exam:generate-keys
                            {--force : Overwrite existing keys without prompting}';

    protected $description = 'Generate the RSA-2048 key pair used to sign and verify exam QR pass JWTs.';

    public function handle(): int
    {
        $keysDir     = storage_path('app/keys');
        $privatePath = $keysDir . '/exam_private.pem';
        $publicPath  = $keysDir . '/exam_public.pem';

        // ── Guard: keys already exist ────────────────────────────────────
        if (File::exists($privatePath) && ! $this->option('force')) {
            $this->warn('RSA keys already exist at storage/app/keys/.');
            $this->line('  Private key: ' . $privatePath);
            $this->line('  Public key:  ' . $publicPath);
            $this->newLine();

            if (! $this->confirm('Overwrite existing keys? All previously issued QR passes will become invalid.', false)) {
                $this->info('Key generation cancelled. Existing keys kept.');
                return self::SUCCESS;
            }
        }

        // ── Create directory ─────────────────────────────────────────────
        if (! File::isDirectory($keysDir)) {
            File::makeDirectory($keysDir, 0700, true);
            $this->line("Created directory: {$keysDir}");
        }

        // ── Generate RSA-2048 key pair ───────────────────────────────────
        $this->info('Generating RSA-2048 key pair...');

        $config = [
            'digest_alg'       => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);

        if ($resource === false) {
            $this->error('openssl_pkey_new() failed. Ensure the OpenSSL extension is installed and configured.');
            $this->error('OpenSSL error: ' . openssl_error_string());
            return self::FAILURE;
        }

        // Extract private key PEM
        $privateKeyPem = '';
        if (! openssl_pkey_export($resource, $privateKeyPem)) {
            $this->error('Failed to export private key: ' . openssl_error_string());
            return self::FAILURE;
        }

        // Extract public key PEM
        $details      = openssl_pkey_get_details($resource);
        $publicKeyPem = $details['key'] ?? null;

        if (! $publicKeyPem) {
            $this->error('Failed to extract public key details.');
            return self::FAILURE;
        }

        // ── Write keys to disk ───────────────────────────────────────────
        File::put($privatePath, $privateKeyPem);
        chmod($privatePath, 0600); // Owner read/write only — keep private key secret

        File::put($publicPath, $publicKeyPem);
        chmod($publicPath, 0644); // World-readable — safe to distribute

        // ── Success output ───────────────────────────────────────────────
        $this->newLine();
        $this->info('✓ RSA-2048 key pair generated successfully.');
        $this->table(
            ['File', 'Path', 'Permissions'],
            [
                ['Private key', $privatePath, '0600 (owner read/write)'],
                ['Public key',  $publicPath,  '0644 (world-readable)'],
            ]
        );

        $this->newLine();
        $this->warn('⚠  Keep the private key secret. Never commit it to version control.');
        $this->warn('⚠  Ensure storage/app/keys/ is excluded from your .gitignore.');
        $this->newLine();
        $this->line('All new QR passes will now be signed with RS256 (replaces HMAC fallback).');
        $this->line('Run php artisan key:generate if you have not done so already.');

        return self::SUCCESS;
    }
}
