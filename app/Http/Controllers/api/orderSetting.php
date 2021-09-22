<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use App\Http\Controllers\api\ApiController;


class orderSetting extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       $api = new ApiController;
        $response = ["response"=>"Data not found"];
        $att_url="graphql.json";
        $url =$api->base_url($att_url);
        $data_json = file_get_contents('php://input');
        $data_arr = json_decode($data_json, TRUE);
        $dates=$api->date_format($data_arr['DateTime']);
        $body = "{\r\n  orders(first:100,query:\"created_at:>$dates\"){\r\n    edges{\r\n      node{\r\n        id\r\n      }\r\n    }\r\n  }\r\n}\r\n";
        $order_api_res =$api->CheckAuth($url,$body,null,'post');
        if(!empty($order_api_res["data"]['orders']['edges'])){
            $response = array();
            foreach ($order_api_res["data"]['orders']['edges'] as $edge) {
                $id = explode("/",$edge['node']['id']);
                $att_url="orders/".end($id).".json";
                $orderUrl =$api->base_url($att_url);
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
       $api = new ApiController;
        $response = ["response"=>"Data not found"];
        $data_json = file_get_contents('php://input');
        $data_arr = json_decode($data_json, TRUE);
        $dates=$api->date_format($data_arr['DateTime']);
        $att_url="graphql.json";
        $url =$api->base_url($att_url);
        $body = "{\r\n  products(first:250, query:\"updated_at:>$dates\"){\r\n    edges{\r\n     node{\r\n      id\r\n    }\r\n    }\r\n  }\r\n}\r\n";
        $order_sku_res = $this->CheckAuth($url,$body,null,"post");
        if(!empty($order_sku_res["data"]['products']['edges'])){
            $ids = array();
            foreach ($order_sku_res["data"]['products']['edges'] as $edge) {
                $id_explode = explode("/",$edge['node']['id']);
                array_push($ids, end($id_explode));
            }
            $att_url = "products.json?ids=".implode(",",$ids);
            $productGetUrl=$api->base_url($att_url);
            $response = $this->CheckAuth($productGetUrl,null,null,"get");
        }
        return $response;
    }

    public function stockUpdate()
    {
         $api = new ApiController;
        $response = ["response"=>"Data not found"];
        $data_json = file_get_contents('php://input');
        $data_arr = json_decode($data_json, TRUE);
        $data_arr = $data_arr["inventoryLevel"];
        $att_url = "products/".$data_arr['shopify_product_id']."/variants/".$data_arr['shopify_variant_id'].".json";
        $url=$api->base_url($att_url);
        $variants_res = $this->CheckAuth($url,null,null,"get");
        if(!empty($variants_res["variant"])){
            $inventory_item_id = $variants_res['variant']['inventory_item_id'];
            $att_url= "inventory_levels.json?inventory_item_ids=".$inventory_item_id;
            $locationUrl=$api->base_url($att_url);

            $location_res = $this->CheckAuth($locationUrl,null,null,"get");
            if(!empty($location_res["inventory_levels"])){
                $inventory_levels = $location_res['inventory_levels'][0];
                $location_id = $inventory_levels['location_id'];
                $inventory_item_id = $inventory_levels['inventory_item_id'];
                $qty = $data_arr['quantity'];
                $att_url = "inventory_levels/set.json";
                $stockUpdateUrl=$api->base_url($att_url);
                $body = "{\r\n  \"location_id\": ".$location_id.",\r\n  \"inventory_item_id\": ".$inventory_item_id.",\r\n  \"available\": ".$qty."\r\n}";
                $response = $this->CheckAuth($stockUpdateUrl,$body,"application/json","post");
            }
        }
        return $response;
    }

}
