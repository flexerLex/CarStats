<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header('Location: login.php');
    exit;
}

require_once 'connect_DB.php'; 
require_once 'StatistikService.php';

$conn = getDBConnection();
$service = new StatistikService($conn);

// 1. 获取筛选参数
$user_id = $_SESSION['user_id'];
$currentUnit = filter_input(INPUT_GET, 'unit') ?: 'month';
if (!in_array($currentUnit, ['day', 'month', 'year'])) $currentUnit = 'month';

// 2. 获取车辆列表和选定车辆
$cars = $service->getUserCars($user_id);
$car_id = $service->determineSelectedCar($cars);

// 3. 抓取统计结果
$results = $car_id ? $service->getCarStatistics($car_id, $user_id, $currentUnit) : $service->initializeEmptyResults();

// 4. 包含视图文件
include 'statistiken_view.php';