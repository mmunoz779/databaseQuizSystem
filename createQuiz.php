<?php
try {
    $config = parse_ini_file("db.ini");
    $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    var_dump($_POST);
} catch (PDOException $e) {
    print "Error!" . $e->getMessage() . "</br>";
    die();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Quiz</title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="createQuizStyle.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
</head>
<body ng-app="submitApp">
<form name="newQuiz" ng-submit="submitQuiz()" ng-controller="submitController">
    <label for="quizName">Quiz Name: </label>
    <input type="text" id="quizName" placeholder="Enter the quiz name here"/>
    <br>
    <label id="totalPoints">Total Points: {{getTotalPoints(questions) || prevPoints}}</label>
    <br>
    <div class="newControls">
        <label for="createButton" class="createLabel">New Question</label>
        <button type="button" name="createButton" class="create round" ng-click="newQuestion()">+</button>
    </div>
    <br>
    <div class="questions" id="questionHolder" ng-repeat="question in questions">
        <label for="questionTextInput" class="strong">Question: {{$index + 1}}</label>
        <br>
        <label for="qPoints" class="strong">Point value: </label>
        <input id="qPoints" ng-model="question.points"/>
        <br>
        <div id="questionContents" class="tab">
            <div class="newControls">
                <label for="createChoice" class="createLabel">New Choice</label>
                <button type="button" name="createButton" class="create round" ng-click="newChoice(question)">+</button>
            </div>
            <br>
            <input class="questionDescription" id="questionTextInput" type="text"
                   placeholder="Enter question's text here"
                   ng-model="questionText" ng-init="questionText=question.text"/>
            <br>
            <div id="choiceHolder" ng-repeat="choice in question.choices">
                <label for="choice{{$index}}">{{getLetter($index)}}</label>
                <input type="text" id="choice{{$index}}" placeholder="Enter choice text here"
                       ng-model="choiceText" ng-init="choiceText=choice.text"/>
                <button class="remove round" name="removeButton" ng-click="removeChoice(question,choice)">-</button>
            </div>
        </div>
    </div>
    <br>
    <button name="publish" class="publish rounded" type="submit">Publish</button>
    <button class="cancel rounded" type="button" name="cancel" onclick="window.location.href='dashboard.php'">Cancel
    </button>
</form>
</body>
<script>
    var app = angular.module('submitApp', []);
    app.controller('submitController', ($scope, $http) => {
        $scope.questions = [{
            text: "",
            points: 0,
            choices: [{
                text: ""
            }, {
                text: ""
            }, {
                text: ""
            }, {
                text: ""
            }]
        }];

        $scope.submitQuiz = () => {
            var questions = $scope.questions;
            var request = $http({
                method: 'post',
                url: 'createQuiz.php',
                data: {
                    name: document.forms['newQuiz']['quizName'].value,
                    totalPoints: getTotalPoints(questions),
                    questions: questions,
                    choices: getChoices()
                }
            });
            request.success((response) => {
                console.log(JSON.stringify(response));
            });
        };

        $scope.prevPoints = "0";

        $scope.getTotalPoints = (questions) => {
            var ret = 0;
            questions.forEach((question) => {
                ret += parseInt(question.points);
            });
            if (!isNaN(ret)) {
                $scope.prevPoints = ret.toString();
            }
            return ret;
        };

        $scope.getLetter = (index) => {
            var ret = "";
            if (index >= 26) {
                for (var i = 0; i < index / 25; i++) {
                    ret += String.fromCharCode((index % 26) + 65);
                }
            } else {
                ret = String.fromCharCode(index + 65);
            }
            return ret;
        };

        $scope.updatePoints = (q) => {
            q.points = document.getElementById('qPoints').value;
            console.log(q.points);
        };

        $scope.newQuestion = () => {
            $scope.questions.push({
                text: "",
                points: 0,
                choices: [{
                    text: ""
                }, {
                    text: ""
                }, {
                    text: ""
                }, {
                    text: ""
                }]
            });
        };

        $scope.removeQuestion = (q) => {
            $scope.questions = $scope.questions.filter((currElement) => {
                return currElement !== q;
            });
        };

        $scope.getChoices = (questions) => {
            var ret;
            questions.forEach((question) => {
                question.forEach((choice) => {
                    ret.push(choice);
                });
            });
        };

        $scope.newChoice = (q) => {
            q.choices.push({
                text: ""
            });
        };

        $scope.removeChoice = (q, c) => {
            if (q.choices.length <= 1) {
                alert('You must have at least one choice per question');
            } else {
                q.choices = q.choices.filter((currElement) => {
                    return currElement !== c;
                });

            }
        };

    })
    ;
</script>
</html>
