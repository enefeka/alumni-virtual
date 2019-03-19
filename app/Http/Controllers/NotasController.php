<?php

namespace App\Http\Controllers;

use App\Notas;
use App\Groups;
use App\Users;
use App\Roles;
use App\Privacity;
use App\Belong;
use Illuminate\Http\Request;
use \Firebase\JWT\JWT;

class NotasController extends Controller
{

    public function index()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));
        $id = Users::where('email', $userData->email)->first()->id;
        $notasDB = Notas::where('id_user', $id)->get();


        return $this->createResponse (200, 'Notas', ["nota" => $notasDB]);


    }

    public function eval()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $notasDB = Notas::where('id_user', 2)->get();


        return $this->createResponse (200, 'Notas', ["nota" => $notasDB]);

    }
}
