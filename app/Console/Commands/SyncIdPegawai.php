<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\SimpegController;
use Illuminate\Http\Request;
use DB;

class SyncIdPegawai extends Command
{

    protected $signature = 'sync-id-pegawai {--email=}';
    protected $description = 'Sync pegawai_id pada tabel skp_kontrak menggunakan email (emailPegawai) dari API SIMPEG';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Memulai Sinkronisasi pegawai_id...');

        try {
            // Get all unique emails from skp_kontrak
            $emailOption = $this->option('email');

            $query = DB::table('skp_kontrak')
                ->where(function($q) {
                    $q->whereNull('pegawai_id')
                      ->orWhere('pegawai_id', '');
                })
                ->whereNotNull('bobot_persen')
                ->where('bobot_persen', '!=', '');

            // If --email option provided, filter by that email (supports comma-separated list)
            if (!empty($emailOption)) {
                $emailOption = trim($emailOption);
                if (strpos($emailOption, ',') !== false) {
                    $emails = array_map('trim', explode(',', $emailOption));
                    $this->info('Filtering sync to emails: ' . implode(', ', $emails));
                    $query->whereIn('pegawai_email', $emails);
                } else {
                    $this->info('Filtering sync to email: ' . $emailOption);
                    $query->where('pegawai_email', $emailOption);
                }
            }

            $contracts = $query->select('pegawai_email')
                ->distinct()
                ->get();

            $simpegController = new SimpegController();
            $count = 0;

            foreach ($contracts as $contract) {
                if (!$contract->pegawai_email) {
                    continue;
                }

                // membuat request email
                $request = new Request();
                $request->merge(['email' => $contract->pegawai_email]);

                // Get pegawai data dari SIMPEG
                $response = $simpegController->get_pegawai($request);
                $data = json_decode($response->getContent());

                if (!empty($data) && isset($data[0]->kodeData)) {
                    // Update skp_kontrak records match dengan email
                    $updated = DB::table('skp_kontrak')
                        ->where('pegawai_email', $contract->pegawai_email)
                        ->update(['pegawai_id' => $data[0]->kodeData]);

                    if ($updated) {
                        $count += $updated;
                        $this->info("Updated pegawai_id for email: {$contract->pegawai_email}");
                    }
                } else {
                    $this->warn("No data found for email: {$contract->pegawai_email}");
                }
            }

            $this->info("Sync completed! Updated {$count} records.");
            return 0;

        } catch (\Exception $e) {
            $this->error("Error occurred: " . $e->getMessage());
            return 1;
        }
    }
}