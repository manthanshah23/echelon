<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;


class ApiController extends Controller
{
    public static function CheckAuth($url,$body=null,$type="application/graphql",$method="get")
    {
        $client = new Client();
        if($method == "get"){
                $request = $client->get($url,[
                    'headers' => [
                        'Authorization' => env('AUTH_KEY'),
                        'Content-Type' => 'application/json',
                    ]
                ]);
         }else{
            $request = $client->request('post', $url, [
                'headers' => [
                    'Authorization' => env('AUTH_KEY'),
                    'Content-Type' => $type
                ],
                'body' => $body
            ]);
         }
        $response = json_decode($request->getBody(), true);
        return $response;
    }

    public function base_url($att_url){
        return env('API_URL').$att_url;
    }

    public function date_format($data_arr){
            $dates = date("Y-m-d", strtotime($data_arr));
            $dates .= "T";
            $dates .= date("h:m:s", strtotime($data_arr));
            return $dates;
        }

}
