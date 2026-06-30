<?php

require_once('vendor/meta/Database/Database.php');

// Chargement conditionnel des fichiers de sécurité
$securityFilesLoaded = false;
if (file_exists('vendor/security/security.config.php') && file_exists('vendor/security/Security.class.php')) {
    try {
        require_once('vendor/security/security.config.php');
        require_once('vendor/security/Security.class.php');
        $securityFilesLoaded = true;
    } catch (Exception $e) {
        $securityFilesLoaded = false;
    }
}

define("_SYSTEM_TTFONTS", "C:/Windows/Fonts/");

// Configuration sécurisée de la base de données
// TODO: Déplacer ces informations vers un fichier .env ou config sécurisé
try {
    DEFINE('db', new Database(DB_HOST, DB_PORT, DB_USER, DB_PASSWORD,DB_NAME));
    db->beginTransaction();
} catch (Exception $e) {
    // Log seulement si Security est disponible
    if ($securityFilesLoaded && class_exists('Security') && method_exists('Security', 'logSecurityEvent')) {
        Security::logSecurityEvent('database_connection_failed', ['error' => $e->getMessage()]);
    }
    header("content-type: application/json; charset=utf-8");
    die(json_encode(['status' => 500, 'message' => 'Erreur de connexion à la base de données']));
}
/*
function beginTransaction(){
    $db->beginTransaction();
}

function commit(){
    $db->commit();
}

function rollback(){
    $db->rollback();
}*/


require_once('vendor/Libs/FPDF/tfpdf.php');
require_once('vendor/meta/Objects/Tables/Field/FieldType.enum.php');
require_once('vendor/meta/Objects/Tables/Table.class.php');
require_once('vendor/meta/Objects/Pages/Pagetype.enum.php');
require_once('vendor/meta/Objects/Controls/Control.class.php');
require_once('vendor/meta/Objects/Pages/Page.class.php');
require_once('vendor/meta/Objects/Controls/SubRepeater.class.php');

// ── Pages pilotées par métadonnées (MetaPage extends Page) ───────────────
require_once('vendor/meta/Objects/Pages/PageDescriptor.class.php');
require_once('vendor/meta/Objects/Pages/MetaPage.class.php');
require_once('vendor/meta/Objects/Pages/PageRegistry.class.php');
$currentObject = null;
$base = db;


if(isset($_POST['page'])) {
    $page = $_POST['page'];
}else
    $page ='';

/*
function OpenPage($pageId, $record = null){
    $view = new Views();
    $view->setRange('Id', $pageId);
    if($view->FindFirst()){
        $page = new $view->className->value;
        if($record !== null)
            $page->setRecord($record);
        else
            $page->rec->FindAll();

        die(json_encode(array("status" => 300, "page" => json_decode($page))));
    }else
        Error404("Page non trouvée");
}
*/


function PageOpen($page){
    //Error(json_encode(array("status" => 300, "page" => json_decode($page))));   
   die(json_encode(array("status" => 302, "page" => json_decode($page))));
}

function PageOpenModal($page){
    //Error(json_encode(array("status" => 300, "page" => json_decode($page))));   
   die(json_encode(array("status" => 302, "page" => json_decode($page))));
}

function Error($message)
{
    //header("content-type: application/json; charset=utf-8");
    if(db->inTransaction())
        db->rollback();
    http_response_code(500);
    die(json_encode(array("status" => 500, "message" => $message)));

}

function ErrorCode($code, $message)
{
    if(db->inTransaction())
        db->rollback();
    http_response_code($code);
    die(json_encode(array("status" => $code, "message" => $message)));
}


function Error404($message)
{
    if(db->inTransaction())
        db->rollback();
    http_response_code(404);
    die(json_encode(array("status" => 404, "message" => $message)));
}

/*function getAthorizationToken(){
    $headers = getallheaders();
    if(isset($headers['Authorization']))
        return str_replace('Bearer ', '', $headers['Authorization']);
    else
        UnAuthorized('Connexion non autorisée');
}*/

function UnAuthorized($message){
    header("Content-type: application/json");
    if(db->inTransaction())
        db->rollback();
    http_response_code(401);    
    die(json_encode(array("status"=>401, "message"=>$message)));
}

function IsNullOrEmptyString(string|null $str){
    return $str === null || trim($str) === '';
}

function OneIsNullOrEmptyString(string|null ...$str){
    $isNull = false;
    foreach ($str as $value) {
        if($value === null || trim($value) === ''){
            $isNull = true;
        }
    }
    return $isNull;
}

function calcDate($interval,$date){
    $dte = new DateTime($date);
    preg_match('(\d+)',$interval,$matches);
    if ($matches[0] > 0)
        $i = "+".$matches[0];
    else
        $i = $matches[0];

    $interval = preg_replace(array('(\d+[D])','(\d+[M])','(\d+[Y])','(\d+[H])','(\d+[i])','(\d+[S])'),array($i.'Day',$i.'Month',$i.'Year',$i.'Hour',$i.'Minute',$i.'Second'),$interval);
    $dte->modify($interval);
    return $dte->format('Y-m-d H:i:s');
}

function Str2Date($date){
    $dte = new DateTime($date);
    return $dte;
}

function DateCompare(DateTime $date1, DateTime $date2,string $operator='='){
    switch ($operator) {
        case '>':
            return $date1 > $date2;
        case '<':
            return $date1 < $date2;
        case '=':
            return $date1 == $date2;
        case '>=':
            return $date1 >= $date2;
        case '<=':
            return $date1 <= $date2;
        default:
            Error("'".$operator."'  n'est pas un opérateur valide");
    } 
}

function Evaluate(string $value): float|int|bool {
    // Supprime les espaces
    $trimmed = trim($value);

    // Vérifie si c'est un entier
    if (ctype_digit($trimmed)) {
        return (int)$trimmed;
    }else
    // Vérifie si c'est un float valide
    if (is_numeric($trimmed)) {
        return (float)$trimmed;
    }else{
        Error("Impossible d'évaluer la valeur numérique de ".$value);
        return false;
    }
}


function quote($txt){
    return str_replace("'","\'",$txt);
}

// Retourne le Tenant_code de l'utilisateur associé à la session courante
function getSessionTenant(?string $token = null): string {
    if (!$token) {
        $headers = getallheaders();
        $bearer  = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(.+)$/i', $bearer, $m)) $token = $m[1];
    }
    if (!$token) return '';
    $session = new Session();
    $session->setRange('session_token', $token);
    if (!$session->FindFirst()) return '';
    $user = new User();
    if (!$user->get($session->user_email->value)) return '';
    return $user->Tenant_code->value ?? '';
}

?>