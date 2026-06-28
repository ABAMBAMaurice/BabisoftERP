<?php
//Inclusion des fichiers

$new_tables = null;
$declared_classes_before = get_declared_classes();

foreach (glob("App/Tables/*.php") as $filename) {
    require_once $filename;
}

foreach (glob("App/*/Tables/*.php") as $filename) {
    require_once $filename;
}

$declared_classes_after = get_declared_classes();
$new_tables = array_diff($declared_classes_after, $declared_classes_before);

if(isset($_GET['SystemUpdateSchema'])) {
    $isOK = false;
    foreach ($new_tables as $object) {
        $base = db;
        $t = new $object;
        if (!$base->table_exists($t->table_name)) {
            $e = $base->executeQuery($t->MySQL_CreateQuery());
            if($base->getError()[0]>0){
                echo Error($base->getError()[2].'. Error on table Table: '.$t->table_name);
            }
        } else {
            $e = $base->executeQuery($t->MySQL_UpdateSchema());
            if($base->getError()[0]>0){
                echo Error("Table: '".$t->table_name."' ".$base->getError()[2]);
            }
        }
        $isOK = true;
    }
    

    if($isOK){   
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(["status"=>200, "Message" => "Installation éffectuée avec succès.", "Token:" => $token]));
    }else{
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(["status"=>500, "Message" => "Installation Echouée", "Token:" => $token]));
    }
}

?>