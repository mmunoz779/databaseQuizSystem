<?php
/**
 * Created by PhpStorm.
 * User: mmuno
 * Date: 12/4/2018
 * Time: 4:07 PM
 */

$postData = file_get_contents("php://input");
$request = json_decode($postData);
@$type = $request->type;

if (isset($type)) {
    try {
        $config = parse_ini_file("db.ini");
        $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($type == 'quizzes') {
            $data = ['quizzes' => array()];
            foreach($dbh->query('SELECT name, createdOn, tot_points from exam') as $row){
                array_push($data['quizzes'],['name'=>$row[0],'createdOn'=>$row[1],'tot_points'=>$row[2]]);
            }
            header('Content-Type: application/json;charset=utf-8');
            echo json_encode($data);
            die();
        }
    } catch (PDOException $e) {
        print "Error!" . $e->getMessage() . "</br>";
        die();
    }
}
?>

<html>
<head>
    <title>MyDashboard</title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="dashboardStyles.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
</head>
<body ng-app="dashboardApp">
<h1><b>My Dashboard</b></h1>
<div class="buttons">
    <ul class="buttons">
        <li>
            <button class="toggleButton" id="defaultOpen" onclick="openTab('quizzes')">
                Quizzes
            </button>
        </li>
        <li>
            <button class="toggleButton" onclick="openTab('students')">
                Students
            </button>
        </li>
        <li class="create">
            <label for="createButton" class="create">Create New Quiz</label>
            <button name="createButton" class="create" onclick="window.location.href='createQuiz.php'">+</button>
        </li>
    </ul>
</div>
<div class="quizzes" id="quizzes" hidden ng-controller="dashboardController">
    <table border=1px>
        <tr>
            <th>Quiz Name</th>
            <th>Created On</th>
            <th>Total Points</th>
        </tr>
        <tr ng-repeat="quiz in quizzes">
            <td>{{quiz.name}}</td>
            <td>{{quiz.createdOn}}</td>
            <td>{{quiz.tot_points}}</td>
        </tr>
    </table>
</div>
<div class="students" id="students" hidden>
    <table>
        <tr>
            <th>Name</th>
            <th>Major</th>
        </tr>
        <tr>
        </tr>
    </table>
</div>
<script>
    var app = angular.module('dashboardApp', []);
    app.controller('dashboardController', ($scope, $http) => {
        var req = $http({
            url: 'dashboard.php',
            data: {
                type: 'quizzes'
            },
            method: "POST",
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        });
        req.success((res) => {
            $scope.quizzes = res.quizzes;
        });
        req.error((res) => {
            console.log('POST Error');
        });
    });

    function createButton(tabName){
        if(tabName=="Quiz"){
            window.location.href='createQuiz.php';
        } else {
            window.location.href='createStudent.php';        }
    }
    function openTab(tabName) {
        switch (tabName) {
            case "quizzes":
                document.getElementById("quizzes").hidden = false;
                document.getElementById("students").hidden = true;
                document.getElementById("createLabel").innerHTML="Create New Quiz";
                document.getElementById("createButton").onclick=function(){createButton("Quiz");};
                break;
            case "students":
                document.getElementById("students").hidden = false;
                document.getElementById("quizzes").hidden = true;
                document.getElementById("createLabel").innerHTML="Create New Student";
                document.getElementById("createButton").onclick=function(){createButton("Student");};
                break;
            default:
                document.getElementById("quizzes").hidden = false;
                document.getElementById("students").hidden = true;
                document.getElementById("createLabel").innerHTML="Create New Quiz";
                document.getElementById("createButton").onclick=function(){createButton("Quiz");};
                break;
        }
    }

    openTab();
</script>
</body>
</html>