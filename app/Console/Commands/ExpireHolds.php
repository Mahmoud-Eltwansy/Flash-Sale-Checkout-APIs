<?php

namespace App\Console\Commands;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holds:expires';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make the old holds expired and release reserved stocks';

    /**
     * Execute the console command.
     */
    public function handle(Schedule $schedule)
    {
        if (!app()->runningInConsole()) {
            $schedule->command($this->signature)->everyMinute()->withoutOverlapping();
        }
        $startTime = microtime(true);
        $expiredCount = 0;
        $errorCount = 0;

        Hold::expiredAndActive()
            ->chunk(100, function ($holds) use (&$expiredCount, &$errorCount) {
                foreach ($holds as $hold) {
                    try {
                        DB::transaction(function () use ($hold) {
                            $product = Product::where('id', $hold->product_id)->lockForUpdate()->first();

                            if (!$product) {
                                Log::info("Product not found for Expired Hold ", [
                                    'hold_id' => $hold->id,
                                    'product_id' => $hold->product_id
                                ]);
                                return;
                            }

                            // Release reserved stock
                            $product->decrement('reserved_stock', $hold->quantity);

                            // Update hold status to expired
                            $hold->update(['status' => 'expired']);

                            // Invalidate Cache
                            Cache::forget("product:{$hold->product_id}:data");

                            Log::info('Hold Expired', [
                                'hold_id' => $hold->id,
                                'product_id' => $hold->product_id,
                                'quantity' => $hold->quantity
                            ]);
                        });
                        $expiredCount++;
                    } catch (\Throwable $th) {
                        $errorCount++;

                        Log::error('Error expiring hold', [
                            'hold_id' => $hold->id,
                            'error' => $th->getMessage()
                        ]);
                    }
                }
            });
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $this->info("Expired {$expiredCount} holds in {$duration}ms");

        if ($errorCount > 0) {
            $this->error("Failed to expire {$errorCount} holds");
        }

        Log::info('Hold expiry job completed', [
            'expired_count' => $expiredCount,
            'error_count' => $errorCount,
            'duration_ms' => $duration,
        ]);

        return Command::SUCCESS;
    }
}
