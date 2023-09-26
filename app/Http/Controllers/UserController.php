<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request){
        $json = $request->getContent();
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        $params_array = array_map('trim', $params_array);

        if ($params_array === null) {
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'Invalid JSON data',
            ];
            return response()->json($data, 400);
        }

        $validator = Validator::make($params_array, [
            'name' => 'required|alpha',
            'surname' => 'required|alpha',
            'email' => 'required|email|unique:users',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ];
            return response()->json($data, 400);
        }

        $pwd = hash( 'sha256', $params_array['password']);

        $user = new User();
        $user->name = $params_array['name'];
        $user->surname = $params_array['surname'];
        $user->email = $params_array['email'];
        $user->password = $pwd;
        $user->role = 'ROLE_USER';

        $user->save();

        $data = [
            'status' => 'success',
            'code' => 200,
            'message' => 'User created successfully',
            'user' => $user
        ];
        return response()->json($data, 200);
    }

    public function login(Request $request){

        $json = $request->getContent();
        $params_array = json_decode($json, true);
        $params_array = array_map('trim', $params_array); 
        
        $validator = Validator::make($params_array, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $signup = [
                'status' => 'error',
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ];
            return response()->json($signup, 400);
        } else {
            $pwd = hash( 'sha256', $params_array['password']);
            $jwtAuth = new JwtAuth();
            $signup = $jwtAuth->signup($params_array['email'], $pwd);
            if(!empty($params_array['gettoken'])){
                $signup = $jwtAuth->signup($params_array['email'], $pwd, true);
            }
        }
        
        

        return response()->json($signup, 200);
    }

    public function update(Request $request){
        $token = $request->header('Authorization');
        $jwtAuth = new JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        $json = $request->getContent();
        $params_array = json_decode($json, true);

        if($checkToken && !empty($params_array)){
            
            $json = $request->getContent();
            $params_array = json_decode($json, true);

            $user = $checkToken = $jwtAuth->checkToken($token, true);

            $validate = Validator::make($params_array,[
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users,'.$user->sub
            ]);

            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            $user_update = User::where('id', $user->sub)->update($params_array);

            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user_update,
                'changes' => $params_array
            );


        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function upload (Request $request){
        $image = $request->file('file0');

        $validate = Validator::make($request->all(),[
            'file0' => 'required|image|mimes:jpg,png,jpeg'
        ]);

        if($image || !$validate->fails()){
            $image_name = time().$image->getClientOriginalName();
            Storage::disk('users')->put($image_name, File::get($image));
            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ); 
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error de subida',
                'request' => $request->file('file0')
            ); 
        }
        return response()->json($data, $data['code']);
    }

    public function getImage($filename){
        $isset= Storage::disk('users')->exists($filename);
        if($isset){
            $file = Storage::disk('users')->get($filename);
            return new Response($file,200);
        } else {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'No existe la imagen',
            ); 
            return response()->json($data, $data['code']);
        }
    }

    public function detail($id){
        $user = User::find($id);

        if(is_object($user)){
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'No existe el usuario',
            ); 
        }

        return response()->json($data,$data['code']);
    }
}
