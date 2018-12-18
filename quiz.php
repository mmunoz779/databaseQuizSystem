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
        $data = ['questions' => array()];
        foreach ($stmt as $row) {
            if (isset($data['questions'][$row[4]])) {
                $data['questions'][$row[4]]['text'] = $row[6];
                $data['questions'][$row[4]]['points'] = $row[5];
                array_push($data['questions'][$row[4]]['choices'], ['identifier' => $row[9], 'text' => $row[7], 'correct' => $row[8], 'selected' => false]);
            } else {
                $newArr = ['choices' => array(), 'text' => $row[6], 'points' => $row[5]];
                $data['questions'][$row[4]] = $newArr;
                array_push($data['questions'][$row[4]]['choices'], ['identifier' => $row[9], 'text' => $row[7], 'correct' => $row[8], 'selected' => false]);
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
        <ul>
            <li ng-repeat="question in questions">
                <div class="questionInfo">
                    <label for="questionTextInput" class="strong">Question: {{$index + 1}}</label>
                </div>
                <label for="qPoints" class="strong">Point value: </label>
                <label for="qPoints" class="strong">{{isCorrect(question) ? question.points : 0}}/</label>
                <label id="qPoints">{{question.points}}</label>
                <br>
                <br>
                <div id="questionContents" class="tab">
                    <label class="questionDescription" id="questionTextInput">{{question.text}}</label>
                    <div class="choiceHolder">
                        <ul>
                            <li ng-repeat="choice in question.choices">
                                <label style="padding-right: 4px;" for="choice{{$index}}">{{getLetter($index)}}</label>
                                <label style="padding-right: 4px;" id="choice{{$index}}">{{choice.text}}</label>
                                <input id="choice{{question.identifier}}" ng-checked="choice.selected"
                                       name="question{{question.identifier}}"
                                       type="radio" ng-click="choose(question,choice)"/>
                            </li>
                        </ul>
                        <br>
                    </div>
            </li>
        </ul>
    </div>
    <div class="navigationButtonDiv">
        <br>
        <button name="publish" class="publish rounded" type="submit">Submit</button>
        <button class="cancel rounded" type="button" name="cancel" onclick="window.location.href='dashboard.php'">Cancel
        </button>
    </div>
</form>
</body>
<script>
    var app = angular.module('quizApp', []);

    app.controller('quizController', function ($scope, $http, $location) {

        var name = $location.absUrl().split('?')[1].split('ame=')[1].split('%20').join(' ');

        var request = $http({
            method: 'post',
            url: 'quiz.php?name=' + name,
            data: {
                name: name
            },
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        });

        request.success((response) => {
            $scope.questions = response.questions;
        });

        request.error((response) => {
            console.log('ERROR:\n' + response);
        });

        $scope.choose = (q, c) => {
            q.choices.forEach((choice) => {
                choice.selected = (choice === c);
            });
        };

        $scope.submitted = false;

        $scope.submit = () => {
            $scope.submitted = true;
        };

        $scope.isCorrect = (q) => {
            q.choices.forEach((choice) => {
                if (choice.selected) {
                    console.log(choice.correct == 1);
                    return choice.correct == 1;
                }
            });
            return false;
        };

    });
</script>
</html>
