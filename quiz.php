<?php
/**
 * Created by PhpStorm.
 * User: mmuno
 * Date: 12/4/2018
 * Time: 4:14 PM
 */

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>
        <?php
        if ($_GET['name']) {
            echo "" . $_GET['name'];
        } else {
            header('location: dashboard.php');
        }
        ?>
    </title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
</head>
<body>

</body>
<script>
        var app = angular.module('quizApp', []);
        app.controller('quizController', function ($scope,$http) {
            var req = $http({
                url: '',

            });
        });
</script>
</html>
