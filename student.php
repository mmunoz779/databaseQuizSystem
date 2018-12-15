<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="styles.css"/>
    <link rel="stylesheet" href="gradeStyle.css"/>
    <title>
        <?php
        echo "" . $_GET['name'];
        ?>'s Grades
    </title>
</head>
<body>
<h1><b>Student Name: </b><?php echo "" . $_GET['name'] ?></h1>
<h2><b>Student ID: </b><?php echo "" . $_GET['student'] ?></h2>
<table border="1px" class="table">
    <tr>
        <th>Quiz Name</th>
        <th>Date Created</th>
        <th>Points Earned</th>
        <th>Points Possible</th>
    </tr>
    <?php
    if (isset($_GET['student'])) {
        $config = parse_ini_file("db.ini");
        $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $dbh->prepare("SELECT exam.name, createdOn, tot_points, grade from exam left outer join (student natural join takes) on exam_name=exam.name where stu_id=:stu_id");
        $stmt->execute(array('stu_id' => $_GET['student']));

        $total_score = 0;
        $total_possible = 0;

        foreach ($stmt as $row) {

            if ($row[3] == null) {
                $grade = 'Not yet taken';
            } else {
                $grade = $row[3];
                $total_score += $row[3];
            }

            $total_possible += $row[2];

            echo "<tr><td>$row[0]</td><td>$row[1]</td><td>$grade</td><td>$row[2]</td></tr>";
        }

        echo "</table><br>";

        $percent = ($total_score / ($total_possible == 0 ? 1 : $total_possible)) * 100;

        switch ($percent / 10) {
            case 10:
                $letter = "A";
                break;
            case 9:
                $letter = "A";
                break;
            case 8:
                $letter = 'B';
                break;
            case 7:
                $letter = 'C';
                break;
            case 6:
                $letter = 'D';
                break;
            default:
                $letter = 'E';
                break;
        }

        echo "<table border='1px' class='table' style='width:10%'>";
        echo "<tr><td>Total:</td><td>$total_score/$total_possible</td></tr>";
        echo "<tr><td>Percent:</td><td>$percent%</td></tr>";
        echo "<tr><td>Letter Grade:</td><td>$letter</td></tr>";
        echo "</table>";

    } else {
        header('Location: ' . 'dashboard.php');
        die();
    }
    ?>
    <br>
    <button onclick="window.location.href='dashboard.php'" class="success">Dashboard</button>
</body>
</html>