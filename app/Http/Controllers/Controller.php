<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use \Firebase\JWT\JWT;
use App\Users;
use Image;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    protected $key = '73yt247ht9y3hg93g876h38gh4598';


    protected function error($code, $message)
    {
        $json = ['message' => $message];
        $json = json_encode($json);
        return  response($json, $code)->header('Access-Control-Allow-Origin', '*');
    }
    
    protected function success($code, $message, $data = [])
    {
    	$json = ['code' => $code, 'message' => $message, 'data' => $data];
        $json = json_encode($json);
        return  response($json, 200)->header('Access-Control-Allow-Origin', '*');
    }

    protected function checkLogin($email, $password)
    {
        $userSave = Users::where('email', $email)->first();
        $emailSave = $userSave->email;
        $passwordSave = $userSave->password;
        if($emailSave == $email && $passwordSave == $password)
        {
            return true;
        }
        return false;
    }
    
    public static function reindex($arr)
    {
        $arr = array_merge($arr);

        foreach ($arr as $k => $v) {
            is_array($v) and $v = static::reindex($v);
        }

        return $arr;
    }


    protected function recoverPassword($email)
    {
        $userRecover = Users::where('email', $email)->first();
        $emailRecover = $userRecover->email;
        if($emailRecover == $email)
        {
            return true;
        }
        return false;
    }

    protected function getGlobalPath($image){
        return "http://192.168.1.34:8888/ProyectoAlumni/public/uploads/" . $image;
    }

    function createResponse($code, $message, $data = [])
    {
        if ($data == null) {
           $data = (object)[];
        }
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ]);

    }
    

}
