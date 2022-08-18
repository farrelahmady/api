<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        echo "DB Connection = " . env('DB_CONNECTION') . "\n";
        if (env('DB_CONNECTION') === 'mysql') {
            // Event Scheduler for updating availability status every 1 Day
            echo "Drop availabilities_update_scheduler\n";
            DB::statement('DROP EVENT IF EXISTS availabilities_update_scheduler');
            echo "Creating availabilities_update_scheduler\n";
            DB::statement('CREATE EVENT availabilities_update_scheduler ON SCHEDULE EVERY 1 DAY
                        STARTS DATE_FORMAT(CURRENT_TIMESTAMP, \'%Y-%m-%d\') + INTERVAL 1 DAY
                        DO
                        UPDATE availabilities SET date = DATE_ADD(date, INTERVAL 14 DAY) WHERE CURDATE() >= date ');
            // Event Scheduler for check first then updating Appointment status every 1 Minute
            echo "Drop appointments_status_update_scheduler\n";
            DB::statement('DROP EVENT IF EXISTS appointments_status_update_scheduler');
            echo "Creating appointments_status_update_schedulerL\n";
            DB::statement('CREATE EVENT appointments_status_update_scheduler ON SCHEDULE EVERY 1 MINUTE
                        STARTS DATE_FORMAT(CURRENT_TIMESTAMP, \'%Y-%m-%d %H:%i\') + INTERVAL 1 MINUTE
                        DO
                        UPDATE appointments SET status = 3 WHERE status = 2 AND CURDATE() >= date  AND CURRENT_TIME() >= time ');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_scheduler');
    }
};
