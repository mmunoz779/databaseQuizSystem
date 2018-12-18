<?php
/**
 * Created by PhpStorm.
 * User: 24G
 * Date: 12/17/2018
 * Time: 10:19 PM
 */
session_start();
if(!empty($_SESSION['user'])){
    echo json_encode($_SESSION['user']);
}
echo null;

?>