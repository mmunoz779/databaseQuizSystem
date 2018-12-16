<?php
/**
 * Created by PhpStorm.
 * User: mmuno
 * Date: 12/4/2018
 * Time: 4:07 PM
 */
session_start();

if (isset($_SESSION['Instructor'])) {
    if ($_SESSION['Instructor'] == true) {
        $postData = file_get_contents("php://input");
        $request = json_decode($postData);
        @$isPost = $request->isPost;

        if (isset($isPost)) {
            try {
                $config = parse_ini_file("db.ini");
                $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
                $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                if ($isPost == 'true') {
                    $data = ['quizzes' => array(), 'students' => array()];
                    foreach ($dbh->query('SELECT name,  DATE_FORMAT(createdOn,\'%c/%e/%y at %h:%i:%s %p\') createdOn, tot_points FROM exam') as $row) {
                        array_push($data['quizzes'], ['name' => $row[0], 'createdOn' => $row[1], 'tot_points' => $row[2]]);
                    }
                    foreach ($dbh->query('SELECT stu_id, major, name from student') as $row) {
                        array_push($data['students'], ['stu_id' => $row[0], 'major' => $row[1], 'name' => $row[2]]);
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
    } else {
        $postData = file_get_contents("php://input");
        $request = json_decode($postData);
        @$isPost = $request->isPost;
        $stu_id = $_SESSION['id'];

        if (isset($isPost)) {
            try {
                $config = parse_ini_file("db.ini");
                $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
                $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                if ($isPost == 'true') {
                    $data = ['quizzes' => array(), 'grades' => array()];
                    foreach ($dbh->query('SELECT name,  DATE_FORMAT(createdOn,\'%c/%e/%y at %h:%i:%s %p\') createdOn, tot_points FROM exam') as $row) {
                        array_push($data['quizzes'], ['name' => $row[0], 'createdOn' => $row[1], 'tot_points' => $row[2]]);
                    }
                    foreach ($dbh->query("SELECT name,  DATE_FORMAT(createdOn,'%c/%e/%y at %h:%i:%s %p') createdOn, tot_points, grade FROM exam join takes t on exam.name = t.exam_name WHERE stu_id = $stu_id") as $row) {
                        array_push($data['grades'], ['name' => $row[0], 'createdOn' => $row[1] ,'tot_points' => $row[2], 'grade' => $row[3]]);
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
    }
} else {
    header("Location: login.php");
    die();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>MyDashboard</title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles.css"/>
    <link rel="stylesheet" href="dashboardStyles.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
</head>
<body ng-app="dashboardApp" ng-controller="dashboardController">
<h1><b>My Dashboard</b></h1>
<div class="buttons">
    <ul class="buttons">
        <li class="create">
            <button id="createButton" class="create" onclick="window.location.href='createQuiz.php'">+</button>
            <label for="createButton" class="create" id="createLabel"></label>
        </li>
        <li class="quizToggle">
            <button class="toggleButton" id="defaultOpen" onclick="openTab('quizzes')">
                Quizzes
            </button>
        </li>
        <li class="studentToggle">
            <button class="toggleButton" onclick="openTab('students')">
                Students
            </button>
        </li>
    </ul>
</div>
<div class="quizzes tableDivs" id="quizzes" hidden>
    <table class="table" border="1px">
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
<div class="students tableDivs" id="students" hidden>
    <table class="table" border="1px">
        <tr>
            <th>Name</th>
            <th>Student ID</th>
            <th>Major</th>
            <th>Grades</th>
        </tr>
        <tr ng-repeat="stu in students">
            <td>{{stu.name}}</td>
            <td>{{stu.stu_id}}</td>
            <td>{{stu.major}}</td>
            <td>
                <button type="button" ng-click="view(stu)">View</button>
            </td>
        </tr>
    </table>
</div>
<script>
    var app = angular.module('dashboardApp', []);
    app.controller('dashboardController', ($scope, $http) => {
        var quizReq = $http({
            url: 'dashboard.php',
            data: {
                isPost: 'true'
            },
            method: "POST",
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        });
        quizReq.success((res) => {
            $scope.quizzes = res.quizzes;
            $scope.students = res.students;
        });
        quizReq.error((res) => {
            console.log('POST Error');
        });

        $scope.view = (student) => {
            window.location.href = 'student.php?student=' + student.stu_id + '&name=' + student.name;
        }
    });

    function createButton(tabName) {
        if (tabName == "Quiz") {
            window.location.href = 'createQuiz.php';
        } else {
            window.location.href = 'createStudent.php';
        }
    }

    function openTab(tabName) {
        switch (tabName) {
            case "quizzes":
                document.getElementById("quizzes").hidden = false;
                document.getElementById("students").hidden = true;
                document.getElementById("createLabel").innerHTML = "Create New Quiz";
                document.getElementById("createButton").onclick = function () {
                    createButton("Quiz");
                };
                break;
            case "students":
                document.getElementById("students").hidden = false;
                document.getElementById("quizzes").hidden = true;
                document.getElementById("createLabel").innerHTML = "Create New Student";
                document.getElementById("createButton").onclick = function () {
                    createButton("Student");
                };
                break;
            default:
                document.getElementById("quizzes").hidden = false;
                document.getElementById("students").hidden = true;
                document.getElementById("createLabel").innerHTML = "Create New Quiz";
                document.getElementById("createButton").onclick = function () {
                    createButton("Quiz");
                };
                break;
        }
    }

    openTab();
</script>
</body>
</html>