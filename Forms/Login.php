<?php
/**
 * Created by PhpStorm.
 * User: Juan
 * Date: 8/24/2018
 * Time: 12:21 PM
 * This pag allows user to login or subscribe to cartocesna
 */
session_start();
$_SESSION['login'] = false;
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <link rel="stylesheet" href="../Master/css/indexBody.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body>
    <input type="button" value="Login" onclick="accessType(0)">
    <input type="button" value="Subscribe" onclick="accessType(1)">
    <form id="loginForm" style="display: none">
        <input type="text" placeholder="Nombre de Usuario">
        <input type="password" placeholder="Contraseña">
        <input type="submit" value="Ingresar">
    </form>
    <form id="subscribeForm" style="display: none">
        <input type="text" placeholder="Nombre de Usuario">
        <input type="password" placeholder="Crear Contraseña">
        <input type="password" placeholder="Repita Contraseña Anterior">
        <input type="submit" value="Registrar">
    </form>
</body>
</html>

<script type="text/javascript">
    function accessType(access) {
        switch (access){
            case 0:
                $("#loginForm").css("display", "block");
                $("#subscribeForm").css("display", "none");
                break;
            case 1:
                $("#loginForm").css("display", "none");
                $("#subscribeForm").css("display", "block");

                break;
        }
    }
</script>
