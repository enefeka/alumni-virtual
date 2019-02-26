<?php

namespace App\Http\Controllers;

use App\Users;
use DB;
use App\Roles;
use App\Privacity;
use App\Friend;
use Illuminate\Http\Request;
use \Firebase\JWT\JWT;
use Illuminate\Support\Facades\Mail;
use Image;

class UsersController extends Controller
{

    public function post_create()
    {
        try {

            if (empty($_POST['email']) || empty($_POST['password']) ) 
            {

                return $this->createResponse(401, 'Debes rellenar todos los campos');
            }

            if(strlen($_POST['password']) < 5 || strlen($_POST['password']) > 12){
                return $this->createResponse(400, 'La contraseña ha de tener entre 5 y 12 caracteres');
            }

            $email = $_POST['email'];
            $password = $_POST['password'];
            $username = explode("@", $email)[0];

            $userDB = Users::where('email', $email)->first();

            if ($userDB != null) {
                return $this->createResponse(400, 'El email ya existe');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->createResponse(400, 'Introduzca un emáil válido');
            }


            $mockData = [
                "jeff@gmail.com",
                "juanma@gmail.com",
                "carlos@gmail.com",
                "daniel@gmail.com",
                "dario@gmail.com",
                "juanma@gmail.com",
                "miguel@gmail.com",
                "julio@gmail.com",
            ];

            $mails = $mockData;
            if (!in_array($email, $mails)) {
                
                return $this->createResponse(401, 'Introduce un email que esté en la base de datos');
            }


            $newPrivacity = new Privacity();
            $newPrivacity->phone = 0;
            $newPrivacity->localization = 0;
            $newPrivacity->save();

            $newUser = new Users();
            $newUser->email = $email;
            $newUser->is_registered = 1;
            $newUser->id_rol = 3;
            $newUser->password = $password;
            $newUser->id_privacity = $newPrivacity->id;
            $newUser->username = $username;
            $newUser->name = $username;
            $newUser->save();

            return $this->createResponse(200, 'Usuario registrado');
}
        catch (Exception $e) 
        {
            return $this->createResponse(500, $e->getMessage());

        }      
    } 
    public function post_changepassword()
    {
        
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));
        $password = $_POST['password'];
        $lastPassword = $_POST['lastPassword'];
        if ($lastPassword !== $userData->password) {
            return $this->createResponse(400, 'La contraseña antigua no es correcta');
        }
        $id = $userData->id;
        $user = Users::find($id);
        $user->password = $password;
        $user->save();
        return $this->createResponse(200, 'Contraseña cambiada');
    }
    

    public function post_login()
    {
        try {

            if (empty($_POST['email']) || empty($_POST['password']) )
            {
                //return $this->createResponse(400, 'Introduzca todos los campos');
                return $this->createResponse(400, 'Parametros incorrectos');
                

            }
            $email = $_POST['email'];
            $password = $_POST['password'];
            $key = $this->key;
            $users = Users::where('email', $email)->get();
            if ($users->isEmpty()) { 
                //return $this->createResponse(400, "Ese usuario no existe");
                return $this->createResponse(400, 'Ese usuario no existe');
            }


            if(self::checkLogin($email, $password)){ 


                $userSave = Users::where('email', $email)->first();
                
                if (!empty($_POST['lon']) && !empty($_POST['lat']) ) {
                    $lon = $_POST['lon'];
                    $lat = $_POST['lat'];
                    $userSave->lon = $lon;
                    $userSave->lat = $lat;
                    $userSave->save();
                }
                

                $array = $arrayName = array
                (
                'id' => $userSave->id,
                'email' => $email,
                'username' => $userSave->username,
                'password' => $password,
                'id_rol' => $userSave->id_rol,
                'id_privacity' => $userSave->id_privacity,
                'group' => $userSave->group

                );
                

                $token = JWT::encode($array, $key);

                $privacity = Privacity::find($userSave->id_privacity);

                return $this->createResponse(200, 'login correcto', ['token'=>$token, 'user' => $userSave, 'privacity' =>$privacity]);

                

		      //    return response()->json([
			  //  'message' => $token,
			    // ]);

            }
            else
            {

              return $this->createResponse(400, 'incorrect password');
             // return $this->createResponse(400, 'incorrect password');

            }
        } 
        catch (Exception $e) 
        {
            return $this->createResponse(500, $e->getMessage());
        }
    }

    public function post_update(Request $request)
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));
        $id_user = $userData->id;
        $id = $_POST['id'];
        $user = Users::find($id_user);

        if ($user->id_rol != 1 && $userData->id != $id) {
            return $this->createResponse(401, 'No tienes permiso');
        }


        if (empty($_POST['id'])) {
            return $this->createResponse(400, 'Introduce la id del usuario');
        }
        try {
            $userBD = Users::find($id);
            if ($userBD == null) {
                return $this->createResponse(400, 'El usuario no existe');
            }
            $image = $request->file('image');

            if (!empty($image)) {
                $filename = $image->getClientOriginalName();
                Image::make($image)->resize(400,400)->save(public_path('/uploads/' . $filename));
                $userBD->photo = $this->getGlobalPath($filename);
            }


            if (!empty($_POST['name']) ) {
                $userBD->name = $_POST['name'];
            }
            if (!empty($_POST['email']) ) {
                $userBD->email = $_POST['email'];
            }
            if (!empty($_POST['phone']) ) {
                $userBD->phone = $_POST['phone'];
            }
            if (!empty($_POST['birthday']) ) {
                $userBD->birthday = $_POST['birthday'];
            }
            if (!empty($_POST['description']) ) {
                $userBD->description = $_POST['description'];
            }

            if (isset($_POST['phoneprivacity']) && isset($_POST['localizationprivacity'])) {

                if ($_POST['phoneprivacity'] != 0 && $_POST['phoneprivacity'] != 1){
                    return $this->createResponse(400, 'Valor de phoneprivacity no válido, debe ser 0 ó 1');
                }

                if ($_POST['localizationprivacity'] != 0 && $_POST['localizationprivacity'] != 1){
                    return $this->createResponse(400, 'Valor de localizationprivacity no válido, debe ser 0 ó 1');
                }

                $privacity = Privacity::find($userBD->id_privacity);
                $privacity->phone = $_POST['phoneprivacity'];
                $privacity->localization = $_POST['localizationprivacity'];
                $privacity->save();

            }
            if (!empty($_POST['id_rol']) ) {
                $rolDB = Roles::find($_POST['id_rol']);
                if ($rolDB == null) {
                    return $this->createResponse(400, 'Rol no valido');
                }
                $userBD->id_rol = $_POST['id_rol'];
            }

            $userBD->save();
            return $this->createResponse(200, 'Usuario actualizado');

            
        } catch (Exception $e) {
           
           return $this->createResponse(500, $e->getMessage());

        }
    }

    public function post_delete()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));
        $id_user = $userData->id;
        $user = Users::find($id_user);
        if ($user->id !== 1) {
            return $this->createResponse(401, 'No tienes permiso');
        }
        $id = $_POST['id'];
        if (empty($_POST['id'])) {
            return $this->createResponse(400, 'Introduce la id del usuario');
        }
        try {
            $userBD = Users::find($id);
            if ($userBD == null) {
                return $this->createResponse(400, 'El usuario no existe');
            }

            $userBD->delete();

            return $this->createResponse(200, 'Usuario borrado');
        } catch (Exception $e) {
            return $this->createResponse(500, $e->getMessage());
        }
    }


    public function post_recover(Request $request)
    {

        $email = $_POST['email'];
        if (empty($_POST['email'])) 
        {
            return $this->createResponse(401, 'Introduzca su email');
        }    
        $users = Users::where('email', $email)->get();
        if ($users->isEmpty()) { 
                return $this->createResponse(400, 'Ese usuario no existe');
            }
        if (self::recoverPassword($email)) {
            $userRecover = Users::where('email', $email)->first();
            $id = $userRecover->id;
            $pwdSent = Users::where('email', $userRecover->email)->first()->password;
            $dataEmail = array(
                'pwd' => $pwdSent,
            );
            Mail::send('emails.welcome', $dataEmail, function($message){
                $emailRecipient = $_POST['email'];
                $message->from('proyectogpass@gmail.com', 'Recuperación contraseña');
                $message->to($emailRecipient)->subject('Recuperación contraseña');
            });
            return $this->createResponse(200, "Contraseña Enviada");


        }
        else
        {
            return $this->createResponse(403, "Los datos no son correctos");

    }
}


    public function post_sendRequest()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));

        if (empty($_POST['id_user'])) 
        {
          return $this->createResponse(400, 'Introduzca la ID del usuario');
        }
        $id_user = $_POST['id_user'];

        try {

            $userBD = Users::where('id', $id_user)->first();
            if ($userBD == null) 
            {
                return $this->createResponse(400, 'No existe el usuario');
            }

        $userId = $userData->id;    
        $friend = Friend::where('state' , 1)
                    ->where('id_user_send', $userData->id)
                    ->where('id_user_receive', $id_user)
                    ->orWhere(function ($query) use($id_user, $userId) {
                        $query->where('id_user_send', $id_user)
                              ->where('id_user_receive', $userId);
                    })
                    ->get();
        $arrFriend = (array)$friend;
        $isFriendEmpty = array_filter($arrFriend);
         
            
        if (!empty($isFriendEmpty)) 
        {
            return $this->createResponse(400, 'Ya existe una petición existente entre ambos usuarios o ya sois amigos');
        }
            
        $newFriend = new Friend();
        $newFriend->id_user_send = $userData->id;
        $newFriend->id_user_receive = $id_user;
        $newFriend->state = 1;
        $newFriend->save();

            return $this->createResponse(200, 'Peticion enviada a '. $userBD->name);
            
        } catch (Exception $e) {
            return $this->createResponse(500, $e->getMessage());    
        }
    }

    public function post_responseRequest()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));

        if (empty($_POST['type']) || empty($_POST['id_user'])) 
        {
          return $this->createResponse(400, 'Faltan parámetros obligatorios (id_user o type) ');
        }

        if ($_POST['type'] != 2 && $_POST['type'] != 3) {
            return $this->createResponse(400, 'El tipo enviado no es valido');
        }
        $id_user = $_POST['id_user'];
        $type = $_POST['type'];

        try {
            $friend = Friend::where('state' , 1)
                ->where('id_user_send', $id_user)
                ->where('id_user_receive', $userData->id)
                ->first();
            $arrFriend = (array)$friend;
            $isFriendEmpty = array_filter($arrFriend);
            if (empty($isFriendEmpty)) 
            {
                return $this->createResponse(400, 'No existe la petición de amistad');
            }
            $friend->state = $type;
            $friend->save();

            if ($type==2) {
                return $this->createResponse(200, 'Solicitud de amistad Aceptada');
            }else{
                $friend->delete();
                return $this->createResponse(200, 'Solicitud de amistad Denegada');
            }
            
        } catch (Exception $e) {

                return $this->createResponse(500, $e->getMessage());
            
        }
    }


    public function post_deleteFriend()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));

        if (empty($_POST['id_user'])) 
        {
          return $this->createResponse(400, 'Introduzca la ID del Usuario');
        }
        $id_user = $_POST['id_user'];

        try {
            
            
            $userBD = Users::where('id', $id_user)->first();

            if ($userBD == null) {
                return $this->createResponse(400, 'No existe el usuario');
            }
            
            $userId = $userData->id;
            $friend = Friend::where('state' , 2)
                        ->where('id_user_send', $userData->id)
                        ->where('id_user_receive', $id_user)
                        ->orWhere(function ($query) use($id_user, $userId) {
                                $query->where('id_user_send', $id_user)
                                      ->where('id_user_receive', $userId);
                    })
                    ->first();
            $arrFriend = (array)$friend;
            $isFriendEmpty = array_filter($arrFriend);

            if (empty($isFriendEmpty)) 
            {
                return $this->createResponse(400, 'Este usuario no existe en tu lista de amigos');
            }
            
            $friend->delete();

                return $this->createResponse(200, 'Has borrado al usuario de tu lista de amigos');

            } 
                catch (Exception $e) 
                {
                    return $this->createResponse(500, $e->getMessage());
                }
    }


    public function post_cancelRequest()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));

        if (empty($_POST['id_user'])) 
        {
          return $this->createResponse(400, 'Introduzca la ID del Usuario');
        }
        $id_user = $_POST['id_user'];

        try {
            $userBD = Users::where('id', $id_user)->first();

            if ($userBD == null) {
                return $this->createResponse(400, 'No existe el usuario');
            }

            $friend = Friend::where('state' , 1)
                ->where('id_user_send', $userData->id)
                ->where('id_user_receive', $id_user)
                ->first();
            $arrFriend = (array)$friend;
            $isFriendEmpty = array_filter($arrFriend);
            if (empty($isFriendEmpty)) 
            {
                return $this->createResponse(400, 'No existe la petición de amistad');
            }

            $friend->delete();

            return $this->createResponse(200, 'Petición de amistad cancelada');
            
        } catch (Exception $e) {

            return $this->createResponse(500, $e->getMessage());
            
        }
    }
public function post_insertUser()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));
        $id_user = $userData->id;
        $user = Users::find($id_user);
        if ($user->id !== 1) {
            return $this->createResponse(401, 'No tienes permiso');
        } 
        if (empty($_POST['email']) || empty($_POST['id_rol']) || empty($_POST['id_group']) || empty($_POST['name'])) {
            return $this->createResponse(400, 'Falta parametro email, id_rol, id_group, name');
        }
        $email = $_POST['email'];
        $id_rol = $_POST['id_rol'];
        $id_group = $_POST['id_group'];
        $name = $_POST['name'];
        $username = explode("@", $email)[0];

        try {
            $rolDB = Roles::find($id_rol);
            if ($rolDB == null) {
               return $this->createResponse(400, 'Rol no valido (1-> admin, 2-> coordinador, 3-> profesor, 4-> alumno)');
            }
            
            $groupDB = Groups::find($id_group);
            if ($groupDB == null) {
                return $this->createResponse(400, 'id_group no válido');
            }

            $userDB = Users::where('email', $email)->first();

            if ($userDB != null) {
                return $this->createResponse(400, 'El email ya existe');
            }

            $newUser = new Users();
            $newUser->email = $email;
            $newUser->is_registered = 1;
            $newUser->id_rol = $id_rol;
            $newUser->password = "temporal";
            $newUser->id_privacity = 1;
            $newUser->name = $name;
            $newUser->username = $username;
            $newUser->save();



            $belongDB = new Belong();
            $belongDB->id_user = $newUser->id;
            $belongDB->id_group = $groupDB->id;
            $belongDB->save();
            
            return $this->createResponse(200, 'Usuario insertado con exito');

        } catch (Exception $e) 
        {
            return $this->createResponse(500, $e->getMessage());
        }
    }
    public function get_allusers()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));
        $id_user = $userData->id;
        $user = Users::find($id_user);
        $users = Users::where('is_registered', 1)->get();
        $userNames = [];
        $userRoles = [];
        foreach ($users as $user) {
            array_push($userNames, $user->username);
            array_push($userRoles, $user->id_rol);
        }
        // return response()->json([
        //     'users' => $userNames,
        //     'roles' => $userRoles,
        // ]);
                return $this->createResponse(200, 'Listado de usuarios', $users);
    }


    public function get_validateMail()
    {
        if (empty($_GET['email'])) {
            return $this->createResponse(400, 'Faltan parámetros');
        }
        $email = $_GET['email'];

        try {
            
            $userBD = Users::where('email', $email)->first(); 

            if($userBD != null){
                
                return $this->createResponse(200, 'Correo valido',array('email'=>$email, 'id'=>$userBD->id) );
            }else{
                
                return $this->createResponse(400, 'Email no valido');
            }

        } catch (Exception $e) {
                
                return $this->createResponse(500, $e->getMessage());
        }
    } 


    public function get_friends()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));


        $userId = $userData->id;

        $friends = Friend::where('state', 2)
                    ->where('id_user_send', $userId)
                    ->orWhere('id_user_receive', $userId)
                    ->get();

        $friendIds = [];

        
        foreach ($friends as $friend) {
            
            if ($friend->id_user_send == $userId) {
                $userDetail = Users::findOrFail($friend->id_user_receive);
                array_push($friendIds, $userDetail);

            }
            if ($friend->id_user_receive == $userId) {
                $userDetail = Users::findOrFail($friend->id_user_send);
                array_push($friendIds, $userDetail);
            }
        }

        // return response()->json([
        //     'friends' => $friendIds,

        // ]);
        return $this->createResponse(200, 'Lista de amigos', $friendIds);

        $users = Users::where('id', $friend->id_user_receive)
                        ->orWhere('id', $friend->id_user_send)
                        ->get();
        $userIds = [];
        foreach ($users as $user) {
            
            if ($user->id == $userId) {
                array_push($friendIds, $friend->id_user_receive);
            }
        }

        if (empty($isFriendEmpty)) 
            {
                return $this->error(400, 'El usuario no tiene amigos');
            }
            else{
                return $this->error(400, 'holi');
            }
    }


    public function get_userById()
    {
        try {

            $headers = getallheaders();
            $token = $headers['Authorization'];
            $key = $this->key;

            
            if ($token == null) 
            {
                return $this->createResponse(400, 'Permiso denegado');
            }
            
            $userData = JWT::decode($token, $key, array('HS256'));
            $id_user = $userData->id;
           
            $id = $_GET['id'];


            $userDB = Users::find($id);

            if ($userDB == null) 
            {
                return $this->createResponse(400, 'El usuario no existe');
            }

            $privacity = Privacity::find($userDB->id_privacity);

            $friend = Friend::where('id_user_send', $id)
                        ->where('id_user_receive', $id_user)    
                        ->orWhere(function ($query) use($id_user, $id) {
                             $query->where('id_user_send', $id)
                                   ->where('id_user_receive', $id_user);
                    })
                    ->first();

            // return $this->createResponse(200, 'Usuario devuelto: ' . $userDB->name . ' Privacity: ' . $privacity->phone . ' Friend: ' . $friend);
            return $this->createResponse(200, 'Usuario devuelto', array('user' => $userDB, 'privacity' => $privacity, 'friend' => $friend));


        } catch (Exception $e) {
            
            return $this->createResponse(500, $e->getMessage());
        }
    }

    public function get_user()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));
        $id = Users::where('email', $userData->email)->first()->id;
        if (empty($_GET['search'])) 
        {
          return $this->createResponse(400, 'Falta parámetro obligatorio (search)');
        }
        $search = $_GET['search'];
        $search = '%'.$search.'%';
        $users = Users::where('email', 'like', $search)
                    ->orWhere('username', 'like', $search)
                    ->orWhere('name', 'like', $search)
                    ->get();

        return $this->createResponse(200, 'Usuario', $users);

    }

    public function get_requests()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));
        
        $friends = Friend::where('id_user_send', $userData->id)
                       ->orWhere('id_user_receive', $userData->id)
                       ->get();

        $friends = Friend::where('state', 1)
                       ->where('id_user_send', $userData->id)
                       ->orWhere('id_user_receive', $userData->id)
                       ->get();

        $friendNames = [];

        foreach ($friends as $friend) {
            
            if ($friend->id_user_send == $userData->id) {
                $userDetail = Users::findOrFail($friend->id_user_receive);
                array_push($friendNames, $userDetail);

            }
            if ($friend->id_user_receive == $userData->id) {
                $userDetail = Users::findOrFail($friend->id_user_send);
                array_push($friendNames, $userDetail);
            }
        }





        // $results = DB::select('select * from friends where id_user_send = 1', array(1));
        return $this->createResponse(200, 'Peticiones devueltas', array('requests' => $friends));
    }


    //     $friends = \DB::query('SELECT * FROM users
    //                                     JOIN friend ON friend.id_user_send = '.$user->data->id.'
    //                                     AND users.id = friend.id_user_receive
    //                                     UNION 
    //                                     SELECT * FROM users
    //                                     JOIN friend ON friend.id_user_receive = '.$user->data->id.'
    //                                     AND users.id = friend.id_user_send
                                        
    //                                     ')->as_assoc()->execute();

// Vehicle::join('staff','vehicles.staff_id','staff.id')
//          ->select(
//                   'vehicles.id',
//                   'vehicles.name',
//                   'staff.id as staff_id',
//                   'staff.name'
//           )
//          ->get();


// Vehicle::where('id',1)
//             ->with(['staff'=> function($query){
//                             // selecting fields from staff table
//                             $query->select(['staff.id','staff.name']);
//                           }])
//             ->get(['id','name','staff_id']);












    public function userNotRegistered($email)
    {
        $users = Users::where('email', $email)->get();
        foreach ($users as $user) {
            if ($user->email == $email) {
                return false;
            }
            else{
                return true;
            }
        }
    }
}
