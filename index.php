<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require 'config.php';
if(!empty($_SESSION["id"])){
    $id = $_SESSION["id"];
    $result = mysqli_query($conn, "SELECT * FROM usuarios WHERE id = $id");
    $row = mysqli_fetch_assoc($result);
}
else{
    header("Location:".DIR_PATH."/login.php");
}

// Alerta se dados já foram enviados
if (isset($_GET['message'])) {
    echo "<script>alert('" . htmlspecialchars($_GET['message']) . "');</script>";
}

// Get the data to insert into the displays
$current_date = date("Y-m-d");
$monthly_date = date('Y-m-d', strtotime($current_date . ' - 1 month'));
$weekly_date = date("Y-m-d", strtotime($current_date . ' - 1 week'));

$monthly_sleep_quality_query = "SELECT sleep_quality, COUNT(*) AS count FROM sleep_data
                        WHERE user_id = $id AND sleep_date BETWEEN '$monthly_date' AND '$current_date'
                        GROUP BY sleep_quality";

$weekly_sleep_quality_query = "SELECT sleep_quality, COUNT(*) AS count FROM sleep_data
                        WHERE user_id = $id AND sleep_date BETWEEN '$weekly_date' AND '$current_date'
                        GROUP BY sleep_quality";


$monthly_quality_counts = [
    '1' => 0,
    '2' => 0,
    '3' => 0,
    '4' => 0,
    '5' => 0
];

$weekly_quality_counts = [
    '1' => 0,
    '2' => 0,
    '3' => 0,
    '4' => 0,
    '5' => 0
];

$monthly_results = mysqli_query($conn, $monthly_sleep_quality_query);
if ($monthly_results){
    while ($row_monthly = mysqli_fetch_assoc($monthly_results)) {
            $sleep_quality = $row_monthly['sleep_quality'];
            $count = $row_monthly['count'];
            // Add the counts to the array
            $monthly_quality_counts[$sleep_quality] = $count;
    }
}

$weekly_results = mysqli_query($conn, $weekly_sleep_quality_query);
if ($weekly_results){
    while ($row_weekly = mysqli_fetch_assoc($weekly_results)) {
            $sleep_quality = $row_weekly['sleep_quality'];
            $count = $row_weekly['count'];
            // Add the counts to the array
            $weekly_quality_counts[$sleep_quality] = $count;
    }
}

$monthly_chart = [
    'Muito Mal' => $monthly_quality_counts['1'],
    'Mal' => $monthly_quality_counts['2'],
    'Ok' => $monthly_quality_counts['3'],
    'Bem' => $monthly_quality_counts['4'],
    'Ótimo' => $monthly_quality_counts['5']
];

$weekly_chart = [
    'Muito Mal' => $weekly_quality_counts['1'],
    'Mal' => $weekly_quality_counts['2'],
    'Ok' => $weekly_quality_counts['3'],
    'Bem' => $weekly_quality_counts['4'],
    'Ótimo' => $weekly_quality_counts['5']
];

$monthly_json = json_encode($monthly_chart);
$weekly_json = json_encode($weekly_chart);

$qualities = [
    0 => 'Dados insuficientes',
    1 => 'Muito mal',
    2 => 'Mal',
    3 => 'Ok',
    4 => 'Bem',
    5 => 'Ótimo',
];

$monthly_average_query = "SELECT avg(sleep_quality) AS avg FROM sleep_data WHERE user_id = $id AND sleep_date BETWEEN '$monthly_date' AND '$current_date'";
$monthly_average = mysqli_query($conn, $monthly_average_query);
$monthly_average = mysqli_fetch_assoc($monthly_average);
if ($monthly_average['avg'] != NULL) {
$monthly_average = intval(round($monthly_average['avg']));
} else {
    $monthly_average = 0;
}
$monthly_average = $qualities[$monthly_average];

$weekly_average_query = "SELECT avg(sleep_quality) AS avg FROM sleep_data WHERE user_id = $id AND sleep_date BETWEEN '$weekly_date' AND '$current_date'";
$weekly_average = mysqli_query($conn, $weekly_average_query);
$weekly_average = mysqli_fetch_assoc($weekly_average);
if ($weekly_average['avg'] != NULL) {
$weekly_average = intval(round($weekly_average['avg']));
} else {
    $weekly_average = 0;
}
$weekly_average = $qualities[$weekly_average];


if (max($monthly_chart) == 0) {
    $most_frequent_monthly = 'Dados insuficientes';
} else {
$most_frequent_monthly = array_search(max($monthly_chart), $monthly_chart);
}
if (max($weekly_chart) == 0) {
    $most_frequent_weekly = 'Dados insuficientes';
} else {
$most_frequent_weekly = array_search(max($weekly_chart), $weekly_chart);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="styles.css" rel="stylesheet">
        <meta charset="utf-8">
        <title>Início</title>
        <style>
            label {
                color: white;
            }
            h1 {
                text-shadow: -1px -1px #fb6, 1px 1px #d60, -3px 0 4px #000;
                font-family:"Segoe print", Arial, Helvetica, sans-serif;
                color: #FF9933; 
                font-weight:lighter;
                margin-bottom: 10px;
            }

            .form-check-input {
                display: none;
                }

            .form-check-label {
                display: flex;
                align-items: center;
                cursor: pointer;
            }

            .form-check-input:checked + .form-check-label i {
                color: #f96d00;
            }

            .form-check {
                display: inline-flex;
                align-items: center;
            }

        </style>

        <!-- Google Pie Chart -->
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">
            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(drawChart);
            google.charts.setOnLoadCallback(drawWeeklyChart);

            function drawChart() {
                var sleep_data = <?php echo $monthly_json;?>;
                var sleep_array = [['Quality', 'Count'], ['Outro', 0.01]];
                Object.keys(sleep_data).forEach(function(key){
                    sleep_array.push([key, Number(sleep_data[key])]);
                });
                var data = google.visualization.arrayToDataTable(sleep_array);
                var options = {
                    title: 'Distribuição Mensal da Qualidade do Sono:',
                    backgroundColor: 'transparent',
                    sliceVisibilityThreshold: 0,
                    titleTextStyle: {color: '#fff'},
                    legend: {textStyle: {color: '#fff'}},
                };
                var chart = new google.visualization.PieChart(document.getElementById('piechart'));
                chart.draw(data, options);
            }

            function drawWeeklyChart() {
                var sleep_data = <?php echo $weekly_json;?>;
                var sleep_array = [['Quality', 'Count'], ['Outro', 0.01]];
                Object.keys(sleep_data).forEach(function(key){
                    sleep_array.push([key, Number(sleep_data[key])]);
                });
                var data = google.visualization.arrayToDataTable(sleep_array);
                var options = {
                    title: 'Distribuição Semanal da Qualidade do Sono:',
                    backgroundColor: 'transparent',
                    sliceVisibilityThreshold: 0,
                    titleTextStyle: {color: '#fff'},
                    legend: {textStyle: {color: '#fff'}},
                };
                var chart = new google.visualization.PieChart(document.getElementById('weekly-piechart'));
                chart.draw(data, options);
            }
        </script>

    </head>
    <body>
        <?php include 'header.php'; ?>
        <main>
            <div class="container-fluid mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-4 data-collect text-center">
                    <h1>Bem vindo <?php echo $row["nome"]; ?>!</h1>
                        <form action="process_data.php" method="post">
                            <fieldset>
                                <legend class="fs-5 fw-normal" style="color: white;">Quantas horas você dormiu esta noite?</legend>
                                <div class="radio-grid">
                                <label><input type="radio" name="sleep-time" value="1" required> 1 hora&nbsp;&nbsp;</label>
                                <label><input type="radio" name="sleep-time" value="2" required> 2 horas</label>
                                <label><input type="radio" name="sleep-time" value="3" required> 3 horas</label>
                                <label><input type="radio" name="sleep-time" value="4" required> 4 horas</label>
                                <label><input type="radio" name="sleep-time" value="5" required> 5 horas</label>
                                <label><input type="radio" name="sleep-time" value="6" required> 6 horas</label>
                                <label><input type="radio" name="sleep-time" value="7" required> 7 horas</label>
                                <label><input type="radio" name="sleep-time" value="8" required> 8 horas</label>
                                <label><input type="radio" name="sleep-time" value="9" required> 9 horas</label>
                                </div>
                            </fieldset>
                            <fieldset>
                                <legend class="fs-5 fw-normal" style="color: white;">Quão bem você dormiu esta noite?</legend>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sleep-quality" id="sleep-quality-1" value="1" required>
                                    <label class="form-check-label" for="sleep-quality-1">
                                        <i class="bi bi-emoji-angry" style="font-size: 2rem;"></i>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sleep-quality" id="sleep-quality-2" value="2" required>
                                    <label class="form-check-label" for="sleep-quality-2">
                                        <i class="bi bi-emoji-frown" style="font-size: 2rem;"></i>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sleep-quality" id="sleep-quality-3" value="3" required>
                                    <label class="form-check-label" for="sleep-quality-3">
                                        <i class="bi bi-emoji-neutral" style="font-size: 2rem;"></i>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sleep-quality" id="sleep-quality-4" value="4" required>
                                    <label class="form-check-label" for="sleep-quality-4">
                                        <i class="bi bi-emoji-smile" style="font-size: 2rem;"></i>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sleep-quality" id="sleep-quality-5" value="5" required>
                                    <label class="form-check-label" for="sleep-quality-5">
                                        <i class="bi bi-emoji-laughing" style="font-size: 2rem;"></i>
                                    </label>
                                </div>
                            </fieldset>
                            <div id="quality-value" class="most-frequent">&nbsp;</div>
                            <button type="submit" class="btn btn-primary" style="margin: 10px 0; background-color: #f96d00;">Enviar</button>
                        </form>
                    </div>
                    <div class="col-md-4 data-display text-center" style="border-right: 2px solid #ccc;">
                        <h1>Mês</h1>
                        <div class="fs-5 fw-normal" style="color: white;">Seu sono mais frequente no mês é:</div>
                        <div class="most-frequent"><?php echo $most_frequent_monthly; ?> </div>
                        <div class="fs-5 fw-normal" style="color: white;">Mensalmente seu sono é em média:</div>
                        <div class="most-frequent"> <?php echo $monthly_average; ?> </div>
                        <div id="piechart" class="chart"></div>
                    </div>
                    <div class="col-md-4 data-display text-center">
                        <h1>Semana</h1>
                        <div class="fs-5 fw-normal" style="color: white;">Seu sono mais frequente na semana é:</div>
                        <div class="most-frequent"><?php echo $most_frequent_weekly; ?> </div>
                        <div class="fs-5 fw-normal" style="color: white;">Semanalmente seu sono é em média:</div>
                        <div class="most-frequent"> <?php echo $weekly_average; ?> </div>
                        <div id="weekly-piechart" class="chart"></div>
                    </div>
                </div>
                <div class="row" id="sleep-calc"></div>
            </div>
        </main>
        <?php include 'footer.php'; ?>
        <script>
            document.querySelectorAll('.form-check-input').forEach(quality => {
                quality.addEventListener('change', function() {
                    let assoc = {
                        1: "Muito Mal",
                        2: "Mal",
                        3: "Ok",
                        4: "Bem",
                        5: "Òtimo"
                    }
                    let quality_value = assoc[this.value];
                    document.getElementById('quality-value').textContent = quality_value;
                });
            });
        </script>
    </body>
</html>