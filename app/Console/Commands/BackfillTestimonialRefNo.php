<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Testimonial;

class BackfillTestimonialRefNo extends Command
{
    protected $signature = 'testimonials:backfill-ref';
    protected $description = 'Backfill missing ref_no for testimonials using year and padded ID';

    public function handle(): int
    {
        $missing = Testimonial::whereNull('ref_no')->orWhere('ref_no', '')->get();
        if ($missing->isEmpty()) {
            $this->info('No testimonials with missing ref_no.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($missing->count());
        $bar->start();

        foreach ($missing as $t) {
            $year = $t->created_at ? $t->created_at->format('Y') : date('Y');
            $t->ref_no = 'SL-' . $year . '-' . str_pad((string)$t->id, 5, '0', STR_PAD_LEFT);
            $t->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete. Updated: ' . $missing->count());
        return Command::SUCCESS;
    }
}
