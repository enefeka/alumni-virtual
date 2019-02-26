<?php

namespace App\Http\Controllers;

use App\Chats;
use App\Comments;
use App\Events;
use App\Groups;
use App\Users;
use App\Roles;
use App\Types;
use App\Privacity;
use App\Belong;
use App\Messages;
use Illuminate\Http\Request;
use \Firebase\JWT\JWT;

class ChatsController extends Controller
{
    public function post_create()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));

        if (empty($_POST['id_user'])) {
            return $this->createResponse(400, 'Introduzca la id del usuario');
        }

        $id_user = $_POST['id_user'];

        try {
            $userBD = Users::find($id_user);
            
            if (empty($userBD)) {
                return $this->createResponse(400, "No existe el usuario para crear un chat");
            }

            $id = $userData->id;
            $chat = Chats::where('id_user1', $userData->id)
                         ->where('id_user2', $id_user)
                         ->orWhere(function($query) use($id_user, $id){
                            $query->where('id_user1', $id_user)
                                  ->where('id_user2', $id);
                         })
                        ->first();

            if ($chat != null) {
                return $this->createResponse(400, "Ya existe un chat con ese usuario");
            }

            $newChat = new Chats();
            $newChat->id_user1 = $userData->id;
            $newChat->id_user2 = $id_user;
            $newChat->save();

            return $this->createResponse(200, "Chat creado con éxito");
        } catch (Exception $e) {
            return $this->createResponse(500, $e->getMessage());
        }
    }
    
    public function post_sendMessage()
    {

    }

    public function get_messages()
    {

    }

    public function get_chats()
    {
        $headers = getallheaders();
        $token = $headers['Authorization'];
        $key = $this->key;
        $userData = JWT::decode($token, $key, array('HS256'));
        $id_user = $userData->id;
        try {
            $chats = Chats::where('id_user1', $id_user)
                        ->orWhere('id_user2', $id_user)
                        ->get();

            $arrChats = (array)$chats;
            $isChatsEmpty = array_filter($arrChats);
         
            
            if (empty($isChatsEmpty)) 
            {
                return $this->createResponse(400, 'No existen chats');
            }

            foreach ($arrChats as $keyChat => $chat) {
                $message = Messages::where('id_chat', $chat->id)->latest()->first();

            }


            $chat['message'] = $message;

            if ($chat->id_user1 == $id_user) {
                $idUserChat = $chat->id_user2;
            }else{
                $idUserChat = $chat->id_user1;
            }

            $userChat = Users::find($idUserChat);

            $chat['user'] = $userChat;

            return $this->createResponse(200, "Chats devueltos", array('chats' => Users::reindex($chats)));
            
        } catch (Exception $e) {
            return $this->createResponse(500, $e->getMessage());
        }
    }

    public function get_UsersToChat()
    {

    }
}
