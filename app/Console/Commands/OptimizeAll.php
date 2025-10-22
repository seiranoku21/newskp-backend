<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeAll extends Command
{

    protected $signature = 'optimize:all';
    protected $description = 'Menjalankan beberapa command optimize sekaligus (config:clear, cache:clear, config:cache, optimize)';

    public function handle()
    {
        $this->info('🚀 Menjalankan proses optimasi...');

        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('config:cache');
        $this->call('optimize');

        $this->info('✅ Semua proses optimize selesai!');
        return Command::SUCCESS;
    }
}