<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldAtAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('clock_hours')->after('total_rest');
            $table->integer('clock_minutes')->after('total_rest');
            $table->integer('late_hours')->after('total_rest');
            $table->integer('late_minutes')->after('total_rest');
            $table->integer('early_hours')->after('total_rest');
            $table->integer('early_minutes')->after('total_rest');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            //
        });
    }
}
