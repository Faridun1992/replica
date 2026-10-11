<?php

namespace App\Console\Commands;

use App\Models\ShopCustomer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MeasureReplication extends Command
{
    protected $signature = 'replica:latency {--samples=10 : Number of measurements (1–100)}';

    protected $description = 'Measure how long a committed primary marker takes to become visible on the replica';

    public function handle(): int
    {
        $samples = filter_var($this->option('samples'), FILTER_VALIDATE_INT);
        if ($samples === false || $samples < 1 || $samples > 100) {
            $this->error('Samples must be between 1 and 100.');

            return self::FAILURE;
        }

        // Dedicated connection: disable sticky so the probe always reads the replica,
        // even though this process just wrote to primary.
        $config = config('database.connections.mysql');
        $config['sticky'] = false;
        config(['database.connections.replication_probe' => $config]);
        DB::purge('replication_probe');
        $connection = DB::connection('replication_probe');
        $times = [];

        try {
            // Warm up both connections; exclude connection establishment from timing.
            $connection->select('SELECT 1');
            $connection->select('SELECT 1', [], false);

            for ($sample = 1; $sample <= $samples; $sample++) {
                $marker = null;
                try {
                    $marker = ShopCustomer::on('replication_probe')->create([
                        'name' => 'Replication timing probe',
                        'email' => Str::uuid().'@probe.invalid',
                        'city' => 'Probe',
                    ]);
                    // Autocommit has completed when create() returns.
                    $started = hrtime(true);
                    $checks = 0;
                    do {
                        $visible = ShopCustomer::on('replication_probe')->whereKey($marker->id)->exists();
                        $elapsed = (hrtime(true) - $started) / 1_000_000;
                        $checks++;
                        if ($visible) {
                            $times[] = $elapsed;
                            $this->line(sprintf('%02d: %.2f ms (%d checks)', $sample, $elapsed, $checks));
                            break;
                        }
                        if ($elapsed >= 10000) {
                            $this->error('Replica did not receive marker within 10 seconds.');

                            return self::FAILURE;
                        }
                        usleep(1000);
                    } while (true);
                } finally {
                    $marker?->delete();
                }
            }

            sort($times);
            $this->table(['Min, ms', 'Average, ms', 'P95, ms', 'Max, ms'], [[
                number_format(min($times), 2),
                number_format(array_sum($times) / count($times), 2),
                number_format($times[(int) ceil(count($times) * .95) - 1], 2),
                number_format(max($times), 2),
            ]]);
            $this->info('Observed visibility delay after commit; includes SELECT round-trip and ~1 ms polling.');

            return self::SUCCESS;
        } finally {
            DB::purge('replication_probe');
        }
    }
}
