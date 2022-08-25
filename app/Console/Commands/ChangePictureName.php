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
        $customer = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'dummy/customer/profile/') !== false)->values();
        $customer->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/customer/profile/cust-" . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->put($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $customer->count() . " customer profiles files found.\n";

        $tailor = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'dummy/tailor/profile/') !== false)->values();
        $tailor->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/tailor/profile/tlr-"  . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->put($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $tailor->count() . " tailor profiles files found.\n";

        $place = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'dummy/tailor/place/') !== false)->values();
        $place->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/tailor/place/plc-"  . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->put($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $place->count() . " tailor place files found.\n";

        $celanaPendek = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'dummy/tailor/catalog/lower/celana-pendek/') !== false)->values();
        $celanaPendek->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/tailor/catalog/lower/celana-pendek/celana-pendek-"  . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->put($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $celanaPendek->count() . " celana pendek picture files found.\n";

        $jeans = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'dummy/tailor/catalog/lower/jeans/') !== false)->values();
        $jeans->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/tailor/catalog/lower/jeans/jeans-"  . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->put($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $jeans->count() . " celana jeans picture files found.\n";

        $batik = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'dummy/tailor/catalog/upper/batik/') !== false)->values();
        $batik->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/tailor/catalog/lower/batik/batik-"  . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->put($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $batik->count() . " baju batik picture files found.\n";

        $hoodie = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'dummy/tailor/catalog/upper/hoodie/') !== false)->values();
        $hoodie->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/tailor/catalog/lower/hoodie/hoodie-"  . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->put($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $hoodie->count() . " baju hoodie picture files found.\n";

        $kaos = collect(Storage::disk('public')->allFiles())->filter(fn ($file) => strpos($file, 'dummy/tailor/catalog/upper/kaos/') !== false)->values();
        $kaos->each(function ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $newName = "images/tailor/catalog/lower/kaos/kaos-"  . \Str::random($length = 16) . "-" . Carbon::now()->toDateString()  . "." . $extension;
            Storage::disk('public')->put($file, $newName);
        });
        echo Carbon::now()->toDateTimeString() . ": " . $kaos->count() . " baju kaos picture files found.\n";
    }
}
