<?php
namespace App\Helpers;

//require_once('vendor/autoload.php');
use App\Models\User;
use DomainException;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

class JwtAuth{
    public $key;
    public function __construct(){
        $this->key ='super private key';
    }
    public function signup($email, $password, $getToken = null)
    {
        try {
            $user = User::where([
                'email' => $email,
                'password' => $password
            ])->first();

            if ($user) {
                $token = array(
                    'sub' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'iat' => time(),
                    'exp' => time() + (7 * 24 * 60 * 60)
                );

                $jwt = JWT::encode($token, $this->key, 'HS256');
                $decoded = JWT::decode($jwt, $this->key, array('HS256'));

                $data = is_null($getToken) ? $jwt : $decoded;
            } else {
                $data = array(
                    'status' => 'error',
                    'message' => 'Incorrect login'
                );
            }
        } catch (Exception $e) {
            // Maneja el error en la decodificación
            $data = array(
                'status' => 'error',
                'message' => 'Failed to decode token: ' . $e->getMessage()
            );
        }

        return $data;
    }

    public function checkToken ($jwt, $getIdentity = false)
    {
        $auth = false;
        try{
            $decoded = JWT::decode($jwt, $this->key, array('HS256'));
        } catch(UnexpectedValueException $e){
            $auth = false;
        } catch(DomainException $e){
            $auth = false;
        }
        if(!empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        } else {
            $auth = false;
        }
        if($getIdentity){
            return $decoded;
        }
        return $auth;
    }

}