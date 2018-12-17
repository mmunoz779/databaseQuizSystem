<?php
/**
 * Created by PhpStorm.
 * User: 24G
 * Date: 12/16/2018
 * Time: 7:13 PM
 */

session_start();
session_destroy();
header('Location: login.php');
exit;