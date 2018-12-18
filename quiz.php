<?php
/**
 * Created by PhpStorm.
 * User: mmuno
 * Date: 12/4/2018
 * Time: 4:14 PM
 */

session_start();
$postData = file_get_contents("php://input");
$request = json_decode($postData);
@$name = $request->name;

if (isset($name)) {
    try {
        $name = $_GET['name'];

        $config = parse_ini_file("db.ini");
        $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $dbh->prepare('SELECT * FROM exam join (SELECT question.exam_name, question.number, question.points, question.text questionText, choice.text choiceText, choice.correct, choice.identifier '
            . 'FROM question join choice on question.exam_name = choice.exam_name and question.number = choice.qnum) questions on questions.exam_name = exam.name'
            . ' WHERE exam.name = :name');
        $stmt->execute(array(':name' => $name));
        $data = ['questions' => ['choices' => ['identifier' => '', 'text' => '', 'correct' => 0], 'text' => '', 'points' => 0]];
        foreach ($stmt as $row) {
            if (in_array($row[4], $data['questions'])) {
                $data['questions'][$row[4]]['text'] = $row[6];
                $data['questions'][$row[4]]['points'] = $row[5];
                array_push($data['questions'][$row[4]]['choices'], ['identifier' => $row[9], 'text' => $row[7], 'correct' => $row[8]]);
            }
        }
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($data);
        die();
    } catch (PDOException $e) {
        echo 'ERROR:' . $e;
        die();
    }
} elseif ($_GET['name']) {
    if (isset($_SESSION['Instructor'])) {

    } else {
        header('Location: login.php');
        die();
    }

} else {
    header('location: dashboard.php');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>
        <?php
        echo "" . $_GET['name'];
        ?>
    </title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
</head>
<body ng-app="quizApp">
<div class="questions" id="questionHolder" ng-controller="quizController">
    <ul>
        <li ng-repeat="question in questions">
            <div class="questionInfo">
                <button class="round remove" ng-click="removeQuestion(question)">-</button>
                <label for="questionTextInput" class="strong">Question: {{$index + 1}}</label>
            </div>
            <br><br>
            <label for="qPoints" class="strong">Point value: </label>
            <input id="qPoints" type="text" digits-only ng-keypress="block($event)" ng-model="question.points"/>
            <br>
            <div id="questionContents" class="tab">
                <div class="newControls">
                    <label for="createChoice" class="createLabel">New Choice</label>
                    <button type="button" name="createButton" class="create round" ng-click="newChoice(question)">+
                    </button>
                </div>
                <br><br>
                <input class="questionDescription" id="questionTextInput" type="text"
                       placeholder="Enter question's text here"
                       ng-model="questionText" ng-init="questionText=question.text" required/>
                <br>
                <div class="choiceHolder">
                    <ul>
                        <li ng-repeat="choice in question.choices">
                            <label style="padding-right: 4px;" for="choice{{$index}}">{{getLetter($index)}}</label>
                            <input style="padding-right: 4px;" type="text" id="choice{{$index}}"
                                   placeholder="Enter choice text here"
                                   ng-model="choiceText" ng-init="choiceText=choice.text" required/>
                            <label style="padding-left: 4px;" for="isCorrect{{question.identifier}}">Correct
                                Answer:</label>
                            <input id="isCorrect{{question.identifier}}" ng-checked="choice.correct"
                                   name="question{{question.identifier}}"
                                   type="radio" ng-click="setCorrect(question,choice)"/>
                            <button class="remove round" name="removeButton"
                                    ng-click="removeChoice(question,choice)">-
                            </button>
                        </li>
                    </ul>
                </div>
        </li>
    </ul>
</div>
</body>
<script>
    var app = angular.module('quizApp', []);

    app.controller('quizController',function ($scope, $http, $location) {

        var name = $location.absUrl().split('?')[1].split('ame=')[1].split('%20').join(' ');

        var request = $http({
            method: 'post',
            url: 'quiz.php',
            data: {
                name: name
            },
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        });

        request.success((response) => {
            console.log(response);
        });

        request.error((response) => {
            console.log('ERROR:\n' + response);
        });

    });
</script>
</html>
