<?php

require 'DBHelper.php';
$DB = new DBHelper();

$author = $_POST["author"];
$idea = $_POST["idea"];
$id = $_POST["id"];
$type = $_POST["type"];

switch ($type){
    case 'insert':
        $insert = $DB->SP_INSERT_IDEA($author,$idea);
        if($insert){
            $result = $DB->SP_SELECTALL_IDEA();
            echo json_encode($result);
        }
        else
            echo json_encode($insert);
        break;

    case 'selectAll':
        $result = $DB->SP_SELECTALL_IDEA();
        if($result)
            echo json_encode($result);
        else
            echo json_encode("Error seleccionando idea.");
        break;
    case 'delete':
        $result = $DB->SP_DELETE_IDEA($id);
        if($result) {
            $result = $DB->SP_SELECTALL_IDEA();
            if ($result)
                echo json_encode($result);
            else
                echo json_encode("Error seleccionando idea para eliminacion.");
        }
        else
            echo json_encode("Idea no fue Eliminada.");
        break;
    default:
        echo json_encode("Error no tipo de operacion fue proporcionada");
}
?>