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
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignUuid('user_tailor_id')->constrained("user_tailors", "uuid")->onUpdate(
                'cascade'
            )->onDelete('cascade');
            $table->date('date');
            $table->time('time');
            $table->timestamps();
            $table->softDeletes();
        });

        if (env('DB_CONNECTION') === 'mysql') {
            echo "Creating Event Scheduler for MySQL\n";
            DB::statement('DROP EVENT IF EXISTS availabilities_update_scheduler');
            DB::statement('CREATE EVENT availabilities_update_scheduler ON SCHEDULE EVERY 1 DAY
                        STARTS DATE_FORMAT(CURRENT_TIMESTAMP, \'%Y-%m-%d\') + INTERVAL 1 DAY
                        DO
                        UPDATE availabilities SET date = DATE_ADD(date, INTERVAL 14 DAY) WHERE CURDATE() >= date ');
            # code...
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('availabilities');
    }
};
