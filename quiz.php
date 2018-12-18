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
@$submitting = $request->submitting;
@$id = $_SESSION['user']['stuId'];
@$grade = $request->grade;
@$selected = $request->selections;

if (isset($submitting)) {

    try {

        $config = parse_ini_file("db.ini");
        $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $dbh->prepare('UPDATE takes SET grade = :grade, selected = :selected WHERE stu_id=:id AND exam_name=:name');
        $stmt->execute(array(':name' => $name, ':id' => $id, ':grade' => $grade, 'selected' => $selected));

        die();
    } catch (PDOException $e) {
        echo 'ERROR:' . $e;
        die();
    }
} elseif (isset($name)) {
    try {

        $config = parse_ini_file("db.ini");
        $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $dbh->prepare('SELECT * FROM exam join (SELECT question.exam_name, question.number, question.points, question.text questionText, choice.text choiceText, choice.correct, choice.identifier '
            . 'FROM question join choice on question.exam_name = choice.exam_name and question.number = choice.qnum) questions on questions.exam_name = exam.name'
            . ' WHERE exam.name = :name');
        $stmt->execute(array(':name' => $name));
        $data = ['questions' => array(), 'grade' => null, 'selected' => null];
        foreach ($stmt as $row) {
            if (isset($data['questions'][$row[4]])) {
                $data['questions'][$row[4]]['correctIdentifier'] = ($row[8] == 1 && $data['questions'][$row[4]]['correctIdentifier'] == -1 ? $row[9] : $data['questions'][$row[4]]['correctIdentifier']);
                array_push($data['questions'][$row[4]]['choices'], ['identifier' => $row[9], 'text' => $row[7], 'correct' => $row[8], 'selected' => false, 'selectable' => true]);
            } else {
                $newArr = ['choices' => array(), 'correctIdentifier' => ($row[8] == 1 ? $row[9] : -1), 'text' => $row[6], 'points' => $row[5], 'identifier' => $row[4]];
                $data['questions'][$row[4]] = $newArr;
                array_push($data['questions'][$row[4]]['choices'], ['identifier' => $row[9], 'text' => $row[7], 'correct' => $row[8], 'selected' => false, 'selectable' => true]);
            }
        }

        $stmt = $dbh->prepare('SELECT grade, selected FROM exam join takes ON exam.name = takes.exam_name WHERE exam.name = :name AND stu_id=:id');
        $stmt->execute(array(':name' => $name, ':id' => $id));

        foreach ($stmt as $row) {
            $data['grade'] = $row[0];
            $data['selected'] = $row[1];
        }

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($data);
        die();
    } catch (PDOException $e) {
        echo 'ERROR:' . $e;
        die();
    }
} elseif ($_GET['name']) {
    if (isset($_SESSION['user'])) {

    } else {
        header('Location: login.php');
        die();
    }

} else {
    header('location: dashboard.php');
    die();
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
    <link rel="stylesheet" href="quizStyles.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.0/angular.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
</head>
<body ng-app="quizApp">
<?php
echo '<h1>' . $_GET['name'] . '</h1>';
?>
<form name="quizForm" ng-controller="quizController" ng-submit="submit()">
    <div class="questions" id="questionHolder">
        <label for="earnedPoints">{{pointsMessage}}</label>
        <label id="earnedPoints" for="totalPoints" ng-hide="!submitted">{{getPointsEarned(questions)}}/</label>
        <label id="totalPoints">{{getTotalPoints(questions)}}</label>
        <ul>
            <li ng-repeat="question in questions">
                <div class="questionInfo">
                    <label for="questionTextInput" class="strong">Question: {{$index + 1}}</label>
                </div>
                <label for="qPoints" class="strong">{{pointValueMessage}}</label>
                <label for="qPoints" class="strong" ng-hide="!submitted">{{question.choices[question.correctIdentifier].selected
                    ? question.points : 0}}/</label>
                <label id="qPoints">{{question.points}}</label>
                <br>
                <br>
                <div id="questionContents" class="tab">
                    <label class="questionDescription" id="questionTextInput">{{question.text}}</label>
                    <div class="choiceHolder">
                        <ul>
                            <li ng-repeat="choice in question.choices">
                                <div class="choiceHolder">
                                    <label ng-style="choice.correct == 1 ? correctStyle : (choice.selected ? selectedStyle : none)"
                                           style="padding-right: 4px;"
                                           for="choice{{$index}}">{{getLetter($index)}}.) </label>
                                    <label ng-style="choice.correct == 1 ? correctStyle : (choice.selected ? selectedStyle : none)"
                                           style="padding-right: 4px;" id="choice{{$index}}"
                                           for="Question{{question.identifier}}choice{{choice.identifier}}">{{choice.text}}</label>
                                    <input ng-style="choice.correct == 1 ? correctStyle : (choice.selected ? selectedStyle : none)"
                                           id="Question{{question.identifier}}choice{{choice.identifier}}"
                                           ng-checked="choice.selected"
                                           name="question{{question.identifier}}"
                                           type="radio" ng-click="choose(question,choice)"
                                           ng-hide="!choice.selectable"/>
                                </div>
                            </li>
                        </ul>
                        <br>
                    </div>
            </li>
        </ul>
    </div>
    <div class="navigationButtonDiv">
        <div class="unfinishedNav" ng-hide="submitted">
            <br>
            <button name="publish" class="publish rounded" type="submit">Submit</button>
            <button class="cancel rounded" type="button" name="cancel" onclick="window.location.href='dashboard.php'">
                Cancel
            </button>
        </div>
        <div class="completedQuizNav" ng-hide="!submitted">
            <button class="dashboard rounded" type="button" onclick="window.location.href = 'dashboard.php'">Dashboard
            </button>
        </div>
    </div>
</form>
</body>
<script>
    var app = angular.module('quizApp', []);

    app.controller('quizController', function ($scope, $http, $location) {

        var name = $location.absUrl().split('?')[1].split('ame=')[1].split('%20').join(' ');

        $scope.pointsMessage = "Points Possible: ";

        $scope.pointValueMessage = "Point value: ";

        var request = $http({
            method: 'post',
            url: 'quiz.php?name=' + name,
            data: {
                name: name
            },
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        });

        $scope.submitted = false;

        request.success((response) => {
            $scope.questions = response.questions;
            if (response.grade != null) {
                $scope.submitted = true;

                response.selected.split(',').forEach((pair) => {
                    $scope.questions.forEach((question) => {
                        if (question.identifier == pair.split(":")[0])
                            question.choices.forEach((choice) => {
                                if (choice.identifier == pair.split(":")[1])
                                    choice.selected = true;
                            });
                    });
                });

                $scope.questions.forEach((question) => {
                    question.choices.forEach((choice) => {
                        choice.selectable = false;
                    });
                });

                $scope.correctStyle = {"background-color": "rgba(0, 180, 0, 0.3)"};
                $scope.selectedStyle = {"background-color": "rgba(180,0, 0, 0.3)"};
                $scope.none = {};

                $scope.pointsMessage = "Points Earned: ";

                $scope.pointValueMessage = "Points Earned: ";
            }
        });

        request.error((response) => {
            console.log('ERROR:\n' + response);
        });

        $scope.choose = (q, c) => {
            q.choices.forEach((choice) => {
                choice.selected = (choice === c);
            });
        };

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

        $scope.getPointsEarned = (questions) => {
            var ret = 0;
            questions.forEach((question) => {
                question.choices.forEach((choice) => {
                    if (choice.selected) {
                        ret += (choice.correct == 1 ? parseInt(question.points) : 0);
                    }
                });
            });
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


        $scope.submit = () => {

            $scope.submitted = true;
            $scope.questions.forEach((question) => {
                question.choices.forEach((choice) => {
                    choice.selectable = false;
                });
            });

            $scope.correctStyle = {"background-color": "rgba(0, 180, 0, 0.3)"};
            $scope.selectedStyle = {"background-color": "rgba(180,0, 0, 0.3)"};
            $scope.none = {};

            $scope.pointsMessage = "Points Earned: ";

            $scope.pointValueMessage = "Points Earned: ";

            console.log('selections: ' + $scope.getSelections($scope.questions));

            var submitReq = $http({
                method: 'post',
                url: 'quiz.php',
                data: {
                    'name': name,
                    'submitting': true,
                    'grade': $scope.getPointsEarned($scope.questions),
                    'selections': $scope.getSelections($scope.questions)
                }
            });

            submitReq.success((response) => {
                console.log(response);
            });

            submitReq.error((response) => {
                alert('ERROR: unable to submit. Debug: ' + response);
            });

        };

        $scope.getSelections = (questions) => {
            var ret = [];
            questions.forEach((question) => {
                question.choices.forEach((choice) => {
                    if (choice.selected)
                        ret.push(question.identifier + ":" + choice.identifier);
                });
            });
            return ret.join(',');
        };

        $scope.isCorrect = (q) => {
            q.choices.forEach((choice) => {
                if (choice.selected) {
                    return choice.correct == 1;
                }
            });
            return false;
        };

    });
</script>
</html>
