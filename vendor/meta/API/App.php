<?php

class App{

    public static function route($method, $path, $callback){

        $currentMethod = $_SERVER['REQUEST_METHOD'];
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $currentPath = str_replace(DIR, '', $currentPath);
        // Convertir le chemin prévu (path) en expression régulière
        $pathRegex = "@^" . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path ) . "$@";
        
        if ($method === $currentMethod) {
            if (preg_match($pathRegex, $currentPath, $matches)) {
                    array_shift($matches);
                    $callback(...$matches);
                    exit;
            }
        }
    }



    public static function Insert($Recref, $data){
        if(is_array($data)){
            $result = [];
            foreach ($data as $value) {
                $rec = new $Recref();
                foreach ($value as $key => $val) {
                    $rec->Validate($key, $val);
                }
                array_push($result,$rec->Insert());
            }
            echo json_encode(array("status" => 200, "result" =>$result));
        }else{
            $rec = new $Recref();
            foreach ($data as $key => $value) {
                $rec->Validate($key, $value);
            }
            $result = $rec->Insert();
            echo json_encode($result);
        }
    }
    public static function Delete($Recref, ...$code){
        $rec = new $Recref();
        foreach ($data as $key => $value) {
            $rec->Validate($key, $value);
        }
        $result = $rec->Insert();

        echo json_encode($result);
    }
}
