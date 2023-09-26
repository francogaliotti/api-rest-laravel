<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }
    public function index(){
        $categories = Category::all();

        return response()->json([
            'categories' => $categories,
            'code' => 200,
            'status' => 'success'
        ]);
    }

    public function show($id){
        $category = Category::find($id);
        if(is_object($category)){
            $data = [
                'category' => $category,
                'code' => 200,
                'status' => 'success'
            ];
        } else {
            $data = [
                'categories' => null,
                'code' => 404,
                'status' => 'error',
                'message' => 'La catyegoria no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request){
        $json = $request->getContent();
        $params_array = json_decode($json,true);
        if($params_array){
            $validate = Validator::make($params_array,[
                'name'=>'required'
            ]);

            if($validate->fails()){
                $data = [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validate->errors(),
                ];
                
            } else {
                $category = new Category();
                $category->name = $params_array['name'];
                $category->save();
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'category' => $category
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'No category',
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request){
        $json = $request->getContent();
        $params_array = json_decode($json,true);

        if($params_array){
            $validate = Validator::make($params_array,[
                'name'=>'required'
            ]);

            if($validate->fails()){
                $data = [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Validation failed',
                    'errors' => $validate->errors(),
                ];
                
            } else {
                $category = Category::where('id', $id);
                $category->update($params_array);
                // $category->name = $params_array['name'];
                // $category->save();
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'category' => $category->first()
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'No category',
            ];
        }
        return response()->json($data, $data['code']);
    }
}
