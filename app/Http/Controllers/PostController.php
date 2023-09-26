<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class PostController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => [
            'index', 
            'show', 
            'getImage', 
            'getPostsByCategory', 
            'getPostsByUser']]);
    }
    public function index(){
        $posts = Post::all()->load('category');

        return response()->json([
            'posts' => $posts,
            'code' => 200,
            'status' => 'success'
        ], 200);
    }

    public function show($id){
        $post = Post::find($id)->load('category');
        if(is_object($post)){
            $data = [
                'post' => $post,
                'code' => 200,
                'status' => 'success'
            ];
        } else {
            $data = [
                'post' => null,
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request){
        $json = $request->getContent();
        $params_array = json_decode($json,true);
        $params = json_decode($json);
        if($params_array){
            $user = $this->getIdentity($request);

            $validate = Validator::make($params_array,[
                'title'=>'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);

            if($validate->fails()){
                $data = [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validate->errors(),
                ];
                
            } else {
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params_array['category_id'];
                $post->title = $params_array['title'];
                $post->content = $params_array['content'];
                $post->category_id = $params_array['category_id'];
                $post->image = $params->image;
                $post->save();
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'post' => $post
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'No post',
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request){
        $json = $request->getContent();
        $params_array = json_decode($json,true);
        $user = $this->getIdentity($request);

        if($params_array){
            $validate = Validator::make($params_array,[
                'title'=>'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);
            unset($params_array['id']);
            unset($params_array['user_id']);

            if($validate->fails()){
                $data = [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validate->errors(),
                ];
                
            } else {
                $post = Post::where('id', $id)->where('user_id', $user->sub);
                if(is_object($post->first())){
                    $post->update($params_array);
                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'post' => $post->first()
                    ];
                } else {
                    $data = [
                        'post' => null,
                        'code' => 404,
                        'status' => 'error',
                        'message' => 'El post no existe'
                    ];
                }
            }
        } else {
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'No post',
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request){
        
        $user = $this->getIdentity($request);
        
        $post = Post::where('id', $id)->where('user_id', $user->sub)->first();
        if(is_object($post)){
            $post->delete();
            $data = [
                'post' => $post,
                'code' => 200,
                'status' => 'success'
            ];
        } else {
            $data = [
                'post' => null,
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request) {
        $image = $request->file('file0');

        $validate = Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,png,jpeg'
        ]);

        if(!$image || $validate->fails()){
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'error al subir la imagen'
            ];
        } else {
            $image_name = time().$image->getClientOriginalName();
            Storage::disk('images')->put($image_name, File::get($image));
            $data = [
                'status' => 'success',
                'code' => 200,
                'message' => 'Imagen subvida',
                'image' => $image_name
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id){
        $posts = Post::where('category_id', $id)->get();
        if(is_object($posts)){
            $data = [
                'posts' => $posts,
                'code' => 200,
                'status' => 'success'
            ];
        } else {
            $data = [
                'post' => null,
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function getPostsByUser($id){
        $posts = Post::where('user_id', $id)->get();
        if(is_object($posts)){
            $data = [
                'posts' => $posts,
                'code' => 200,
                'status' => 'success'
            ];
        } else {
            $data = [
                'post' => null,
                'code' => 404,
                'status' => 'error',
                'message' => 'El post no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function getImage($filename){
        $isset = Storage::disk('images')->exists($filename);
        if($isset){
            $file = Storage::disk('images')->get($filename);
            $data = [
                'status' => 'success',
                'code' => 200,
                'file' => $file
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'error al buscar la imagen'
            ];
        }
        return response()->json($data, $data['code']);
    }

    private function getIdentity($request) {
        $jwtauth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtauth->checkToken($token,true);
        return $user;
    }
}
