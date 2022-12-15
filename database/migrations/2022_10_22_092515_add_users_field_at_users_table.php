<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsersFieldAtUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('type');
            $table->date('dob')->nullable()->after('avatar');
            $table->string('gender')->after('dob');
            $table->string('phone')->nullable()->after('gender');
            $table->string('address')->nullable()->after('phone');
            $table->integer('position_id')->nullable()->after('address');
            $table->date('company_doj')->nullable()->after('position_id');
            $table->date('model_face')->nullable()->after('company_doj');
            $table->integer('is_active')->default(1)->after('model_face');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
