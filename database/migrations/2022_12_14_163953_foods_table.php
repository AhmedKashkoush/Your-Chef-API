<?php

use App\Models\Categories\Category;
use App\Models\Restaurants\Restaurant;
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
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('description_en')->nullable();
            $table->string('description_ar')->nullable();
            $table->string('image')->nullable();
            $table->float('rate')->nullable();
            $table->integer('stock')->default(0);
            $table->float('price');
            $table->foreignIdFor(Restaurant::class);
            $table->foreignIdFor(Category::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('foods');
    }
};
