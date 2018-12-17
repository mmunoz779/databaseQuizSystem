<?php
$postData = file_get_contents("php://input");
$request = json_decode($postData);
@$isPost = $request->isPost;
@$name = $request->name;
@$questions = $request->questions;
@$totalPoints = $request->totalPoints;

session_start();

if (isset($_SESSION['Instructor'])) {
    if (isset($isPost)) {
        try {
            $config = parse_ini_file("db.ini");
            $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh->beginTransaction();

            $stmt = $dbh->prepare('INSERT INTO exam(name,createdOn,tot_points) VALUES(:examName,CURRENT_TIMESTAMP(),:totalPoints)');
            $stmt->execute(array(':examName' => $name, ':totalPoints' => $totalPoints));

            foreach ($questions as $question) {
                @$choices = $question->choices;
                @$points = $question->points;
                @$qnum = $question->identifier;
                @$text = $question->text;
                $stmt = $dbh->prepare('INSERT INTO question(number, text,points,exam_name) VALUES(:qnum,:txt,:points,:examName)');
                $stmt->execute(array(':qnum' => $qnum, ':points' => $points, ':examName' => $name, ':txt' => $text));
                foreach ($choices as $choice) {
                    @$text = $choice->text;
                    @$correct = $choice->correct;
                    @$identifier = $choice->identifier;
                    $stmt = $dbh->prepare('INSERT INTO choice(identifier,text,correct,exam_name,qnum) VALUES(:identifier,:txt,:correct,:examName,:qnum)');
                    $stmt->execute(array(':examName' => $name, ':identifier' => $identifier, ':qnum' => $qnum, ':txt' => $text, ':correct' => $correct));
                }
            }

            //Insert into takes for each student
            $stmt = $dbh->query('SELECT stu_id FROM student');
            foreach ($stmt as $row) {
                @$stu_id = $row[0];
                $stmt = $dbh->prepare('INSERT INTO takes(stu_id, exam_name) VALUES(:stu_id,:examName)');
                $stmt->execute(array(':examName' => $name, ':stu_id' => $stu_id));
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
    <title>Create Quiz</title>
    <meta name="viewport" content="initial-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles.css"/>
    <link rel="stylesheet" href="createQuizStyle.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
</head>
<body ng-app="submitApp">
<form name="newQuiz" ng-submit="submitQuiz()" ng-controller="submitController">
    <label for="quizName">Quiz Name: </label>
    <input limit-to="40" type="text" id="quizName" ng-model="quizName" placeholder="Enter the quiz name here"/>
    <label>{{quizName.length || 0}} / 40 characters</label>
    <br>
    <label id="totalPoints">Total Points: {{getTotalPoints(questions) || prevPoints}}</label>
    <br>
    <div class="newControls">
        <label for="createButton" class="createLabel">New Question</label>
        <button type="button" name="createButton" class="create round" ng-click="newQuestion()">+</button>
    </div>
    <br>
    <div class="questions" id="questionHolder">
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
    <div class="navigationButtonDiv">
        <br>
        <button name="publish" class="publish rounded" type="submit">Publish</button>
        <button class="cancel rounded" type="button" name="cancel" onclick="window.location.href='dashboard.php'">Cancel
        </button>
    </div>
</form>
</body>
<script>
    var app = angular.module('submitApp', []);
    app.controller('submitController', ($scope, $http) => {
            $scope.quizName = "";

            $scope.questions = [{
                identifier: 0,
                text: "",
                points: 0,
                choices: [{
                    identifier: 0,
                    text: "",
                    correct: true
                }, {
                    identifier: 1,
                    text: "",
                    correct: false
                }, {
                    identifier: 2,
                    text: "",
                    correct: false
                }, {
                    identifier: 3,
                    text: "",
                    correct: false
                }]
            }];

            $scope.submitQuiz = () => {
                var questions = $scope.questions;

                var name = document.forms['newQuiz']['quizName'].value;
                if (name.length > 40) {
                    alert('The Quiz name is too long, please use less than 40 characters. You are currently at ' + name.length + ' characters.');
                } else {
                    var request = $http({
                        method: 'post',
                        url: 'createQuiz.php',
                        data: {
                            isPost: 'true',
                            name: name,
                            totalPoints: $scope.getTotalPoints(questions),
                            questions: $scope.questions
                        },
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    });
                    request.success((response) => {
                        window.location.href = 'dashboard.php?success=true';
                    });
                }
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
            };

            $scope.newQuestion = () => {
                $scope.questions.push({
                    identifier: $scope.questions.length,
                    text: "",
                    points: 0,
                    choices: [{
                        identifier: 0,
                        text: "",
                        correct: true
                    }, {
                        identifier: 1,
                        text: "",
                        correct: false
                    }, {
                        identifier: 2,
                        text: "",
                        correct: false
                    }, {
                        identifier: 3,
                        text: "",
                        correct: false
                    }]
                });
            };

            $scope.removeQuestion = (q) => {

                if ($scope.questions.length <= 1) {
                    alert('You must have at least question');
                } else {
                    $scope.questions = $scope.questions.filter((currElement) => {
                        return currElement !== q;
                    });
                }
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
                q.choices.forEach((choice) => {
                    console.log(choice.correct + '\n');
                });
                q.choices.push({
                    identifier: q.choices.length,
                    text: "",
                    correct: false
                });
            };

            $scope.removeChoice = (q, c) => {
                if (q.choices.length <= 1) {
                    alert('You must have at least one choice per question');
                } else if (c.correct === true) {
                    alert('You cannot delete the correct answer for a question');
                } else {
                    q.choices = q.choices.filter((currElement) => {
                        return currElement !== c;
                    });
                }
            };
            // Prevent enter in points from deleting choices
            $scope.block = (event) => {
                if (event.which === 13) {
                    event.preventDefault();
                    return false;
                }
            };

            $scope.setCorrect = function (question, choice) {
                angular.forEach(question.choices, function (c) {
                    c.correct = false; //set them all to false
                });
                choice.correct = true; //set the clicked one to true
            };
        }
    ).directive("limitTo", [function () {
        return {
            restrict: "A",
            link: function (scope, elem, attrs) {
                var limit = parseInt(attrs.limitTo);
                angular.element(elem).on("keypress", function (e) {
                    if (this.value.length === limit) e.preventDefault();
                });
            }
        };
    }]).directive('digitsOnly', function () {
        return {
            require: 'ngModel',
            link: function link(scope, element, attrs, ngModel) {
                ngModel.$parsers.push(function (value) {
                    var numbers = value.replace(/\D/g, '');
                    element.val(numbers);
                    return numbers;
                });
            }
        };
    });

</script>
</html>
