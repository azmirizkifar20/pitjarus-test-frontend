<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-3" id="area-section">
                <label>Select Area</label>
                <select class="selectpicker areas" id="area-section" multiple data-live-search="true"> -->
                    <option value="1">DKI jakarta</option>
                    <option value="2">Jawa Barat</option>
                    <option value="3">Kalimantan</option>
                    <option value="4">Jawa Tengah</option>
                    <option value="5">Bali</option>
                </select>
            </div>
            <div class="col-3 my-auto">
                <label>Select date range</label> <br>
                <input type="text" name="datefilter" value="" />
            </div>
            <div class="col-4 my-auto">
                <button id="view-data" type="button" class="btn btn-primary mt-4">View Data</button>
            </div>
        </div>

        <div class="row mt-4" id="chart-section">
            <canvas id="myChart" height="110"></canvas>
        </div>

        <div id="table-section" class="row mt-4">
        </div>
    </dv>
    
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    <script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" integrity="sha256-+8RZJua0aEWg+QVVKg4LEzEEm/8RFez5Tb4JBNiV5xA=" crossorigin="anonymous"></script>
    <script type="text/javascript" src="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>

    <script>
        let dateFrom = ''
        let dateTo = ''

        // init chartjs & select picker
        var myChart = null
        chartInit()
        $('select').selectpicker();

        // date range picker
        $('input[name="datefilter"]').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            }
        });

        $('input[name="datefilter"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
            dateFrom = picker.startDate.format('YYYY-MM-DD')
            dateTo = picker.endDate.format('YYYY-MM-DD')
        });

        $('input[name="datefilter"]').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            dateFrom = ''
            dateTo = ''
        });

        // button action
        $('#view-data').on('click', function() {
            // get value
            let areas = Array.from($(".areas").find(':selected')).map(function(item) {
                return $(item).val();
            });
            areas = areas.join('%20')

            // set filter params
            let areasFilter = ''
            let rangeFilter = ''

            if (areas != '')
                areasFilter += `areas=${areas}`
            if (dateFrom != '' && dateTo != '')
                rangeFilter += `&date_from=${dateFrom}&date_to=${dateTo}`

            // refresh data
            refreshDataChart(areasFilter, rangeFilter)
            refreshDataTable(areasFilter, rangeFilter)
        })

        function chartInit() {
            const ctx = document.getElementById('myChart').getContext('2d');
            const chartConfig = {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Nilai',
                        data: [],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(255, 159, 64, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
            myChart = new Chart(ctx, chartConfig);
        }

        function refreshDataChart(areasFilter, rangeFilter) {
            $.ajax({
                url: `http://localhost:3020/api/main/chart?${areasFilter}${rangeFilter}`,
                type: 'GET',
                success: function(response) {
                    let data = response.data
                    let chartValue = []

                    // remove section first
                    $('#myChart').remove()
                    $('#chart-section').append(
                        `<canvas id="myChart" height="110"></canvas>`
                    )

                    // re init chart
                    chartInit()

                    // set label & value
                    data.map(chartData => {
                        chartValue.push(chartData.calculation_value)
                        myChart.data.labels.push(chartData.area_name)
                    })
                    myChart.data.datasets[0].data = chartValue
                    myChart.update()
                }
            })
        }

        function refreshDataTable(areasFilter, rangeFilter) {
            $.ajax({
                url: `http://localhost:3020/api/main/table?${areasFilter}${rangeFilter}`,
                type: 'GET',
                success: function(response) {
                    let data = response.data

                    // reset table
                    $('#table-area').remove()
                    $('#table-section').append(
                        `<table id="table-area" class="table table-striped">
                            <thead id="table-head-area">
                                <tr>
                                    <th>Brand Name</th>
                                    <th id="head-dki-jakarta">DKI Jakarta</th>
                                    <th id="head-jawa-barat">Jawa Barat</th>
                                    <th id="head-kalimantan">Kalimantan</th>
                                    <th id="head-jawa-tengah">Jawa Tengah</th>
                                    <th id="head-bali">Bali</th>
                                </tr>
                            </thead>
                            <tbody id="table-body-area">
                            
                            </tbody>
                        </table>`
                    )
                    
                    // update table
                    let bodyArea = $('#table-body-area')
                    
                    for (const chartData of data) {
                        bodyArea.append(
                            `<tr>
                                <td>${chartData.brand_name}</td>
                                <td id="body-dki-jakarta">${Math.ceil(chartData.dki_jakarta)}%</td>
                                <td id="body-jawa-barat">${Math.ceil(chartData.jawa_barat)}%</td>
                                <td id="body-kalimantan">${Math.ceil(chartData.kalimantan)}%</td>
                                <td id="body-jawa-tengah">${Math.ceil(chartData.jawa_tengah)}%</td>
                                <td id="body-bali">${Math.ceil(chartData.bali)}%</td>
                            </tr>`
                        )

                        if (chartData.brand_name == undefined)
                            $('#table-area').remove()

                        if (chartData.dki_jakarta == undefined) {
                            $('#head-dki-jakarta').remove()
                            $('#body-dki-jakarta').remove()
                        } 

                        if (chartData.jawa_barat == undefined) {
                            $('#head-jawa-barat').remove()
                            $('#body-jawa-barat').remove()
                        } 

                        if (chartData.kalimantan == undefined) {
                            $('#head-kalimantan').remove()
                            $('#body-kalimantan').remove()
                        } 

                        if (chartData.jawa_tengah == undefined) {
                            $('#head-jawa-tengah').remove()
                            $('#body-jawa-tengah').remove()
                        } 

                        if (chartData.jawa_tengah == undefined) {
                            $('#head-jawa-tengah').remove()
                            $('#body-jawa-tengah').remove()
                        } 

                        if (chartData.bali == undefined) {
                            $('#head-bali').remove()
                            $('#body-bali').remove()
                        } 
                    }
                }
            })
        }

    </script>
    
</body>
</html>