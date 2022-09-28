<?php

namespace App\Traits;
trait ResponseTrait{

    protected function unAuthorized(){
        $json = $this -> responseJson('failure','unauthorized');
        return response()->json($json,403);
    }

    protected function success($data = null,$msg = null){
        $json = $this -> responseJson('success',$msg,$data);
        return response()->json($json);        
    }

    protected function failure($msg,$statusCode){
        $json = $this -> responseJson('failure',$msg);
        return response()->json($json,$statusCode);        
    }

    private function responseJson($status,$msg = null,$data = []){
        return [
            'status' => $status,
            'msg' => $msg,
            'data' => $data,
        ];
    }
}