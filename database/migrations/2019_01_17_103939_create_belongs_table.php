<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBelongsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('belongs', function (Blueprint $table) {
            $table->primary(['id_user', 'id_group']);
            $table->integer('id_user')->unsigned();
            $table->foreign('id_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            $table->integer('id_group')->unsigned();
            $table->foreign('id_group')
                  ->references('id')->on('groups')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
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
        Schema::dropIfExists('belongs');
    }
}
