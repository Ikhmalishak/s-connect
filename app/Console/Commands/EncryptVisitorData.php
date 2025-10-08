<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptVisitorData extends Command
{
    protected $signature = 'data:encrypt-visitors';
    protected $description = 'Encrypt sensitive data in visitors table';

    public function handle()
    {
        $visitors = DB::table('visitors')->get();
        $bar = $this->output->createProgressBar(count($visitors));
        $bar->start();

        foreach ($visitors as $visitor) {
            $update = [];

            foreach (['ic_number', 'passport', 'phone_number'] as $column) {
                $value = $visitor->$column;
                if ($value && !$this->isEncrypted($value)) {
                    $update[$column] = Crypt::encryptString($value);
                }
            }

            if (!empty($update)) {
                DB::table('visitors')->where('id', $visitor->id)->update($update);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n✅ Encryption completed for visitors table!");
    }

    private function isEncrypted($value)
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
