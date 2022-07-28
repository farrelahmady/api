<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ChangePictureName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'picture:default';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change Default Picture Name';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $customer = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/customer/profile/') !== false)->values();
        $customer->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/customer/profile/cust-" . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->move($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $customer->count() . " customer profiles files found.\n";

        $tailor = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/profile/') !== false)->values();
        $tailor->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/tailor/profile/tlr-"  . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->move($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $tailor->count() . " tailor profiles files found.\n";

        $place = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'images/tailor/place/') !== false)->values();
        $place->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/tailor/place/plc-"  . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->move($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $place->count() . " tailor place files found.\n";
    }
}
