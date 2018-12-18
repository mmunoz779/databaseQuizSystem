<?php
/**
 * Created by PhpStorm.
 * User: 24G
 * Date: 12/17/2018
 * Time: 10:52 PM
 */
session_start();

$postData = file_get_contents("php://input");
$request = json_decode($postData);
@$isPost = $request->isPost;
@$newPass = $request->pass;
@$stu_id = $_SESSION['user']['stuId'];

if (isset($_SESSION['Instructor'])) {
    if (isset($isPost)) {
        try {
            $config = parse_ini_file("db.ini");
            $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh->beginTransaction();

            $stmt = $dbh->prepare('UPDATE student SET password = :newPass WHERE stu_id = :stu_id');
            $stmt->execute(array(':newPass' => md5($newPass), ':stu_id' => $stu_id));

            $dbh->commit();
            header('Content-Type: application/json;charset=utf-8');
            echo json_encode('{success:true}');
            die();
        } catch (PDOException $e) {
            $dbh->rollBack();
            header('Content-Type: html/text');
            print "<br>Error!" . $e->getMessage() . "</br>";
            die();
        }
    }
} else {
    header('Location: login.php');
    die();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Student</title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
</head>

<body ng-app="submitApp">

<form name="submitPass" ng-submit="submitPassword()" ng-controller="submitPassController">
    <label for="newPass">New Password: </label>
    <input id="newPass" type="password"/>
    <br>
    <label for="retypePass">Retype Password: </label>
    <input id="retypePass" type="password"/>
    <br>

    <div class="navigationButtonDiv">
        <br>
        <button name="submit" class="publish rounded" type="submit">Submit</button>
        <button class="cancel rounded" type="button" name="cancel" onclick="window.location.href='dashboard.php'">Cancel
        </button>
    </div>

</form>
</body>
<script>
    var app = angular.module('submitApp',[]);
    app.controller('submitPassController', ($scope, $http) => {
        $scope.newPass = "";

        $scope.submitPassword = () => {

            var newPass = document.forms['submitPass']['newPass'].value;

            if(newPass !== document.forms['submitPass']['retypePass'].value) {
                alert('Your passwords do not match');
            } else {
                var request = $http({
                    method:'post',
                    url:'changePassword.php',
                    data: {
                        isPost: 'true',
                        pass: newPass
                    },
                    header: {'Content-Type': 'application/x-www-form-urlencoded'}
                });
                request.success((response)=> {
                    console.log(response);
                    //window.location.href = 'studentView.php?success=true';
                });
            }
        };
    });
</script>
</html>
