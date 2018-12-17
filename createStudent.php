<?php
/**
 * Created by PhpStorm.
 * User: mmuno
 * Date: 12/4/2018
 * Time: 4:06 PM
 */
$postData = file_get_contents("php://input");
$request = json_decode($postData);
@$isPost = $request->isPost;
@$name = $request->name;
@$major = $request->major;
@$pwd = $request->tempPass;
@$id = $request->id;

session_start();

if (isset($_SESSION['Instructor'])) {
    if (isset($isPost)) {
        try {
            $config = parse_ini_file("db.ini");
            $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh->beginTransaction();

            $stmt = $dbh->prepare('INSERT INTO student(stu_id,name,major,password) VALUES(:stu_id, :name,:major,:pass)');
            $stmt->execute(array(':stu_id' => $id, ':name' => $name, ':major' => $major, ':pass' => md5($pwd)));

            //Insert into takes for each student
            $stmt = $dbh->query('SELECT name FROM exam');
            foreach ($stmt as $row) {
                @$examName = $row[0];
                $stmt = $dbh->prepare('INSERT INTO takes(stu_id, exam_name) VALUES(:stu_id,:examName)');
                $stmt->execute(array(':examName' => $examName, ':stu_id' => $id));
            }

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
    <link rel="stylesheet" href="createStudentStyle.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
</head>

<body ng-app="createApp">
<h1>Create Student</h1>

<form name="newStudent" ng-submit="submitStudent()" ng-controller="submitStudentController">
    <label for="studentID">Student ID: </label>
    <input limit-to="20" type="text" placeholder="Enter the student ID here" id="studentID" ng-model="studentID"/>
    <label>{{studentID.length || 0}} / 20 characters</label>
    <br>
    <label for="studentName">Student Name: </label>
    <input limit-to="20" type="text" placeholder="Enter the student name here" id="studentName" ng-model="studentName"/>
    <label>{{studentName.length || 0}} / 20 characters</label>
    <br>
    <label for="studentMajor">Student Major: </label>
    <input limit-to="30" type="text" placeholder="Enter the student's major here" id="studentMajor"
           ng-model="studentMajor"/>
    <label>{{studentMajor.length || 0}} / 30 characters</label>
    <br>
    <label for="tempPassword">Initial Password: </label>
    <input type="password" placeholder="Type password here" id="tempPassword"/>
    <br>
    <label for="retypePassword">Retype Password: </label>
    <input type="password" placeholder="Retype password here" id="retypePassword" ng-model="tempPassword"/>

    <div class="navigationButtonDiv">
        <br>
        <button name="create" class="publish rounded" type="submit">Create</button>
        <button class="cancel rounded" type="button" name="cancel" onclick="window.location.href='dashboard.php'">Cancel
        </button>
    </div>
</form>
</body>

<script>
    var app = angular.module('createApp', []);
    app.controller('submitStudentController', ($scope, $http) => {
        $scope.studentName = "";

        $scope.studentMajor = "";

        $scope.tempPassword = "";

        $scope.submitStudent = () => {

            var id = document.forms['newStudent']['studentID'].value;
            var name = document.forms['newStudent']['studentName'].value;
            var major = document.forms['newStudent']['studentMajor'].value;
            var pwd = document.forms['newStudent']['tempPassword'].value;
            if (pwd !== document.forms['newStudent']['retypePassword'].value) {
                alert('Your passwords do not match');
            } else {

                var request = $http({
                    method: 'post',
                    url: 'createStudent.php',
                    data: {
                        isPost: 'true',
                        id: id,
                        name: name,
                        major: major,
                        tempPass: pwd
                    },
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                });
                request.success((response) => {
                    window.location.href = 'dashboard.php?success=true';
                });
            }
        };
    }).directive("limitTo", [function () {
        return {
            restrict: "A",
            link: function (scope, elem, attrs) {
                var limit = parseInt(attrs.limitTo);
                angular.element(elem).on("keypress", function (e) {
                    if (this.value.length === limit) e.preventDefault();
                });
            }
        }
    }]);
</script>
</html>
