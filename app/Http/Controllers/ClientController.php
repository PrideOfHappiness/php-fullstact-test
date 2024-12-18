<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MyClient;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function store(Request $request){
        $dataTerkumpul = $request->validate([
            'name' => 'required|string|max:250',
            'slug' => 'required|string|max:100|unique:my_client.slug',
            'is_project' => 'required|in:0,1]',
            'self_capture' => 'required|in:0,1',
            'client_prefix'=>'required|string|max:4',
            'client_logo' => 'required|string|file|mimes:jpg,png,jpeg|max:2048',
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'required|string|max:50',
        ]);

        if($request->hasFile('client_logo')){
            $path = $request->file('client_logo')->store('clients', 's3');
            $dataTerkumpul['client_logo'] = Storage::disk('s3')->url($path);
        }

        $clent = MyClient::create($dataTerkumpul);
        Redis::set($clent->slug, json_encode($clent));
        return response()->json($clent, 201);
    }

    public function show($slug){
        $data = Redis::get($slug);
        if(!$data){
            $data = MyClient::where('slug', $slug)->firstOrFail();
            Redis::set($slug,json_encode($data));
        }else{
            $data = json_decode($data);
        }

        return response()->json($data, 200);
    }

    public function update(Request $request, $slug){
        $client = MyClient::where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'name' => 'required|string|max:250',
            'is_project' => 'required|in:0,1]',
            'self_capture' => 'required|in:0,1',
            'client_prefix'=>'required|string|max:4',
            'client_logo' => 'required|string|file|mimes:jpg,png,jpeg|max:2048',
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'required|string|max:50',
        ]);

        if($request->hasFile('client_logo')){
            $path = $request->file('client_logo')->store('clients', 's3');
            $dataTerkumpul['client_logo'] = Storage::disk('s3')->url($path);
        }

        $client->update($data);
        Redis::delete($slug);
        Redis::set($client->slug, json_encode($client));
        return response()->json($client,201);
    }
}
