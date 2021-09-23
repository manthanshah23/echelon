<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;


class orderSetting extends Controller
{
    public static function GetApi($url)
    {
        $client = new Client();
        $request = $client->get($url,[
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
        $response = json_decode($request->getBody(), true);
        return $response;
    }

    public static function PostApi($url,$body,$type="application/graphql") {
        $client = new Client();
        $request = $client->request('post', $url, [
            'headers' => [
                'Content-Type' => $type
            ],
            'body' => $body
        ]);
        $response = json_decode($request->getBody(), true);
        return $response;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $response = ["response"=>"Data not found"];
        $data_json = file_get_contents('php://input');
        $data_arr = json_decode($data_json, TRUE);

        $username = $data_arr['shopifyConnection']['apiKey'];
        $password = $data_arr['shopifyConnection']['apiPassword'];
        $surl = $data_arr['shopifyConnection']['apiUrl'];

        $url = 'https://'.$username.':'.$password.'@'.$surl.'/admin/api/2021-07/graphql.json';

        $body = "{\r\n  orders(first:100,query:\"created_at:>2021-08-18T01:00:00\"){\r\n    edges{\r\n      node{\r\n        id\r\n      }\r\n    }\r\n  }\r\n}\r\n";
        $order_api_res = $this->PostApi($url,$body);
        if(!empty($order_api_res["data"]['orders']['edges'])){
            $response = array();
            foreach ($order_api_res["data"]['orders']['edges'] as $edge) {
                $id = explode("/",$edge['node']['id']);
                $orderUrl = "https://bdmas.myshopify.com/admin/api/2021-07/orders/".end($id).".json";
                $orderResponse = $this->GetApi($orderUrl);
                if(!empty($orderResponse['order'])){
                    array_push($response, $orderResponse['order']);
                }
            }
        }
        return $response;
    }



    public function getSku()
    {
        $response = ["response"=>"Data not found"];
        $data_json = file_get_contents('php://input');
        $data_arr = json_decode($data_json, TRUE);
        $dates = date("Y-m-d", strtotime($data_arr['DateTime']));
        $dates .= "T";
        $dates .= date("h:m:s", strtotime($data_arr['DateTime']));

        $username = $data_arr['shopifyConnection']['apiKey'];
        $password = $data_arr['shopifyConnection']['apiPassword'];
        $surl = $data_arr['shopifyConnection']['apiUrl'];

        $url = 'https://'.$username.':'.$password.'@'.$surl.'/admin/api/2021-07/graphql.json';
        $body = "{\r\n  products(first:250, query:\"updated_at:>$dates\"){\r\n    edges{\r\n     node{\r\n      id\r\n    }\r\n    }\r\n  }\r\n}\r\n";
        $order_sku_res = $this->PostApi($url,$body);
        if(!empty($order_sku_res["data"]['products']['edges'])){
            $ids = array();
            foreach ($order_sku_res["data"]['products']['edges'] as $edge) {
                $id_explode = explode("/",$edge['node']['id']);
                array_push($ids, end($id_explode));
            }
            $productGetUrl = 'https://'.$username.':'.$password.'@'.$surl.'/admin/api/2021-07/products.json?ids='.implode(",",$ids);
            $response = $this->GetApi($productGetUrl);
        }
        return $response;
    }

    public function stockUpdate()
    {
        $response = ["response"=>"Data not found"];
        $data_json = file_get_contents('php://input');
        $data_arr = json_decode($data_json, TRUE);
        $data_arr = $data_arr["inventoryLevel"];

        $username = $data_arr['shopifyConnection']['apiKey'];
        $password = $data_arr['shopifyConnection']['apiPassword'];
        $surl = $data_arr['shopifyConnection']['apiUrl'];

        $url = 'https://'.$username.':'.$password.'@'.$surl.'/admin/api/2021-07/products/'.$data_arr['shopify_product_id'].'/variants/'.$data_arr['shopify_variant_id'].'.json';

        $variants_res = $this->GetApi($url);

        if(!empty($variants_res["variant"])){
            $inventory_item_id = $variants_res['variant']['inventory_item_id'];
            $locationUrl = 'https://'.$username.':'.$password.'@'.$surl.'/admin/api/2021-07/inventory_levels.json?inventory_item_ids='.$inventory_item_id;
            $location_res = $this->GetApi($locationUrl);
            if(!empty($location_res["inventory_levels"])){
                $inventory_levels = $location_res['inventory_levels'][0];
                $location_id = $inventory_levels['location_id'];
                $inventory_item_id = $inventory_levels['inventory_item_id'];
                $qty = $data_arr['quantity'];
                $stockUpdateUrl = 'https://'.$username.':'.$password.'@'.$surl.'/admin/api/2021-07/inventory_levels/set.json';
                $body = "{\r\n  \"location_id\": ".$location_id.",\r\n  \"inventory_item_id\": ".$inventory_item_id.",\r\n  \"available\": ".$qty."\r\n}";
                $response = $this->PostApi($stockUpdateUrl,$body,"application/json");
            }
        }
        return $response;
    }

}
