<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //create migration
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('gender');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('image')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('password');
            $table->tinyInteger('online_status')->default(0);;
            $table->bigInteger('notifications')->default(0);
            $table->bigInteger('home_notifications')->default(0);;
            $table->timestamps();
        });

        // Schema::table('users', function (Blueprint $table) {
        //     $table->after('name',function($table){
        //         $table->tinyInteger('gender');
        //     });
        //     $table->after('password',function($table){
        //         $table->tinyInteger('online_status');
        //         $table->bigInteger('notifications');
        //         $table->bigInteger('home_notifications');
        //     });
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
