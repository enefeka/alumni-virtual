<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notas extends Model
{
    protected $table = 'notas';

    protected $fillable = ['asignatura', 'nota', 'comentario', 'id_user'];
}
