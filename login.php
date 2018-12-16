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
    header('location: dashboard.php');
    die();
} else {
// User enters the login data
    if (isset($_POST["userid"])) {
        if ($_POST["userid"] == "Houghton" && $_POST["password"] == "snow") {
            $_SESSION["loggedin"] = true;
            $_SESSION["Instructor"] = true;
            header("Location: dashboard.php");
            exit;
        } else {
            $username = $_POST["userid"];
            $password = $_POST["password"];
            $config = parse_ini_file("db.ini");
            $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach ($dbh->query('SELECT stu_id, password from student') as $row) {
                if ($username == $row[0] && ($password == md5($row[1]))) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["Instructor"] = false;
                    $_SESSION['id'] = $username;
                    header("Location: dashboard.php");
                    die();
                }
            }

            echo '<p style="color:#FF0000">incorrect username and password </p>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles.css"/>
</head>
<body>
<form method="POST" action="login.php">
    username: <input type="text" name="userid"/>
    <br/>
    password: <input type="password" name="password"/>
    <br/>
    <input type="submit" value="Submit"/>
</form>
</body>
</html>