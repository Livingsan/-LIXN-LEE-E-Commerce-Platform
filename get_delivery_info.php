<?php
header('Content-Type: application/json');

$delivery_map = [
    'colombo' => 300, 'gampaha' => 300, 'kalutara' => 300,
    'galle' => 500, 'matara' => 500, 'hambantota' => 500,
    'kandy' => 700, 'matale' => 700, 'nuwara eliya' => 700,
    'anuradhapura' => 900, 'polonnaruwa' => 900,
    'kurunegala' => 900, 'puttalam' => 900,
    'badulla' => 900, 'monaragala' => 900,
    'batticaloa' => 1000, 'trincomalee' => 1000, 'ampara' => 1000,
    'jaffna' => 1000, 'kilinochchi' => 1000, 'mannar' => 1000, 'vavuniya' => 1000, 'mullaitivu' => 1000,
    'ratnapura' => 1000, 'kegalle' => 1000
];

$delivery_days_map = [
    300 => 3, 500 => 5, 700 => 7, 900 => 10, 1000 => 12
];

if (isset($_GET['district'])) {
    $district = strtolower(trim($_GET['district']));

    $charge = $delivery_map[$district] ?? 1000;
    $days = $delivery_days_map[$charge] ?? 12;
    $date = date('Y-m-d', strtotime("+$days days"));

    echo json_encode([
        'charge' => $charge,
        'date' => $date
    ]);
} else {
    echo json_encode([
        'charge' => 0,
        'date' => ''
    ]);
}
?>
