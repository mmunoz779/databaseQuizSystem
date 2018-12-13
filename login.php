<?php
/**
 * Created by PhpStorm.
 * User: mmuno
 * Date: 12/4/2018
 * Time: 4:06 PM
 */
session_start();

//
if (isset($_SESSION["loggedin"])) {
    echo '<p style="color:#00AA00">Already Logged in</p>';
} else {

// User enters the login data
    if (isset($_POST["userid"])) {
        if ($_POST["userid"] == "Houghton" && $_POST["password"] == "snow") {
            $_SESSION["loggedin"] = true;
            header("Location: dashboard.php");
            exit;
        } else {
            echo '<p style="color:#FF0000">incorrect username and password </p>';
        }
    }
}
?>

<form method="POST" action="login.php">
    username: <input type="text" name="userid"/>
    <br/>
    password: <input type="password" name="password"/>
    <br/>
    <input type="submit" value="Submit"/>
</form>