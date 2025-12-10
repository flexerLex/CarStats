<!-- in tableinit.sql
 CREATE TABLE expenses (
    id INT(11) NOT NULL AUTO_INCREMENT,
    car_id INT(11) NOT NULL,
    date DATE NOT NULL,
    category VARCHAR(50) NOT NULL, -- Kraftstoff, Service, Reparatur, usw.
    amount DECIMAL(10, 2) NOT NULL,
    mileage INT(11),
    notes TEXT,
    
    fuel_type VARCHAR(20),  -- Benzin, Diesel, Elektro
    quantity DECIMAL(8, 3), -- Getankte/Geladene Menge (Liter/kWh)
    full_tank BOOLEAN,      -- Volltank / Vollgeladen
    
    PRIMARY KEY (id),
    
    FOREIGN KEY (car_id) REFERENCES garage(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; -->

<?php
session_start();
// 设置响应头，确保前端JS知道返回的是JSON格式数据
header('Content-Type: application/json');

// --- 0. 身份验证和初始化 ---

// 检查用户是否登录
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    http_response_code(401); // 401 Unauthorized
    echo json_encode(['error' => 'Nicht angemeldet.']);
    exit;
}

// 引入数据库连接文件
require_once 'connect_DB.php'; // 假设这是你的数据库连接文件

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// 从前端获取参数：选择的车辆ID 和 图表时间单位
$car_id = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
$unit = filter_input(INPUT_GET, 'unit', FILTER_SANITIZE_STRING) ?? 'month'; // 默认按月统计

$results = ['kpis' => [], 'chartData' => ['labels' => [], 'values' => []], 'transactions' => []];
$error = null;

try {
    // 检查 car_id 是否有效且属于当前用户
    if (!$car_id) {
         throw new Exception('Fehlende oder ungültige Fahrzeug-ID.');
    }
    
    $stmt = $conn->prepare("SELECT id, brand, model FROM garage WHERE id = ? AND user_id = ?");
    $stmt->execute([$car_id, $user_id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        throw new Exception('Fahrzeug nicht gefunden oder keine Berechtigung.');
    }
    
    // --- I. 提取原始数据：用于复杂的计算和图表 ---

    // 获取所有费用记录（需要足够的数据来计算油耗，所以不限制）
    $stmt = $conn->prepare("
        SELECT date, category, amount, mileage, quantity, full_tank 
        FROM expenses 
        WHERE car_id = ? 
        ORDER BY date ASC, mileage ASC
    ");
    $stmt->execute([$car_id]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- II. 计算 KPIs ---
    
    // 你的前端需要这些KPIs: Verbrauch, Spritkosten, Monatskosten, Jahreskosten...
    
    // 1. 车辆信息
    $results['kpis']['carModel'] = $vehicle['brand'] . ' ' . $vehicle['model'];
    
    // 2. 总费用和时间成本
    $total_costs_sql = "
        SELECT 
            SUM(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN amount ELSE 0 END) AS annual_total,
            SUM(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN amount ELSE 0 END) AS monthly_total
        FROM expenses WHERE car_id = ?
    ";
    $stmt = $conn->prepare($total_costs_sql);
    $stmt->execute([$car_id]);
    $time_costs = $stmt->fetch(PDO::FETCH_ASSOC);

    $results['kpis']['annualTotalCosts'] = $time_costs['annual_total'] ?? 0.00;
    $results['kpis']['monthlyTotalCosts'] = $time_costs['monthly_total'] ?? 0.00;

    // TODO: ❗ 更复杂的计算：平均油耗 (averageConsumption) 和每公里成本 (fuelCostsPerKm) ❗
    // 这部分需要在 PHP 中对 $expenses 数组进行迭代和复杂计算，涉及到只使用 full_tank=1 的记录。
    // 为了简化，这里先设置一个占位符。
    // --- II. 计算 KPIs ---
    
    // ... (保持原有的车辆信息和总费用查询代码不变) ...

    $results['kpis']['annualTotalCosts'] = $time_costs['annual_total'] ?? 0.00;
    $results['kpis']['monthlyTotalCosts'] = $time_costs['monthly_total'] ?? 0.00;

    // ----------------------------------------------------------------------
    // ❗ 复杂的计算：平均油耗 (averageConsumption) 和 每公里总成本 (fuelCostsPerKm) ❗
    // ----------------------------------------------------------------------
    
    // 1. 获取所有用于油耗计算的“加满”记录
    $consumption_sql = "
        SELECT amount, mileage, quantity 
        FROM expenses 
        WHERE car_id = ? AND category = 'Kraftstoff' AND full_tank = 1 
        ORDER BY date ASC, mileage ASC
    ";
    $stmt = $conn->prepare($consumption_sql);
    $stmt->execute([$car_id]);
    $full_tank_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_distance = 0;
    $total_quantity = 0;
    
    // 遍历所有满箱记录，计算里程差和油量消耗
    for ($i = 1; $i < count($full_tank_records); $i++) {
        $prev_mileage = (float)$full_tank_records[$i-1]['mileage'];
        $curr_mileage = (float)$full_tank_records[$i]['mileage'];
        $curr_quantity = (float)$full_tank_records[$i]['quantity'];
        
        // 里程差 (距离)
        $distance = $curr_mileage - $prev_mileage;
        
        // 消耗油量 (本次加的油量，用于计算上次加满到本次加满之间的消耗)
        $quantity = $curr_quantity; 
        
        if ($distance > 0) {
            $total_distance += $distance;
            $total_quantity += $quantity;
        }
    }
    
    // 从步骤 I 获取的总费用数据 (需要重新运行这个查询或从 $kpi_data 中获取)
    // 假设你已在步骤 I 运行了总费用查询，并将其存储在 $kpi_data 中，但你原代码中没有 $kpi_data，因此我们再次运行一次。
    $stmt = $conn->prepare("SELECT SUM(amount) AS total_amount FROM expenses WHERE car_id = ?");
    $stmt->execute([$car_id]);
    $kpi_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_costs = $kpi_data['total_amount'] ?? 0;


    $avg_consumption = 0.00; // L/100km
    $cost_per_km = 0.00; // €/km
    
    if ($total_distance > 0) {
        // 平均油耗 = (总油量 / 总距离) * 100
        $avg_consumption = ($total_quantity / $total_distance) * 100;
        
        // 每公里总成本 = 总费用 / 总距离
        $cost_per_km = $total_costs / $total_distance;
    }
    
    // ----------------------------------------------------------------------
    
    // 3. 将计算结果添加到 KPIs
    $results['kpis']['averageConsumption'] = round($avg_consumption, 2); 
    $results['kpis']['fuelCostsPerKm'] = round($cost_per_km, 2);
    
    // 获取最近的里程 (从你原代码的 'end($expenses)' 逻辑中提取)
    $stmt = $conn->prepare("SELECT mileage FROM expenses WHERE car_id = ? ORDER BY date DESC, id DESC LIMIT 1");
    $stmt->execute([$car_id]);
    $latest_mileage = $stmt->fetchColumn();

    $results['kpis']['mileage'] = $latest_mileage ?? 0; 
    $results['kpis']['range'] = 0.00; // 假设数据（可能需要更复杂逻辑或用户输入）


    // --- III. 图表数据 (Trend des Kraftstoffverbrauchs) ---
    
    // ... (以下部分保持不变，但请注意，图表数据目前还是 'SUM(amount)'，如果需要真正的油耗趋势图，需要更复杂的逻辑) ...

    // --- III. 图表数据 (Trend des Kraftstoffverbrauchs) ---
    
    // 这里的 SQL 必须根据 $unit (day, month, year) 动态分组
    $date_format = match($unit) {
        'year' => '%Y',
        'month' => '%Y-%m',
        default => '%Y-%m-%d', // day
    };
    
    // 注意：油耗趋势图需要的不是总金额，而是油耗（升/100km）。
    // 这里我们先模拟按时间单位统计总金额作为图表数据，等你实现复杂油耗计算后再替换。
    $chart_sql = "
        SELECT 
            DATE_FORMAT(date, '{$date_format}') AS chart_label,
            SUM(amount) AS chart_value -- 暂时使用金额，应该换成计算后的 L/100km
        FROM expenses 
        WHERE car_id = ? 
        GROUP BY chart_label 
        ORDER BY chart_label ASC
    ";
    
    $stmt = $conn->prepare($chart_sql);
    $stmt->execute([$car_id]);
    $chart_data_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results['chartData']['labels'] = array_column($chart_data_raw, 'chart_label');
    $results['chartData']['values'] = array_column($chart_data_raw, 'chart_value');


    // --- IV. 交易记录 (Letzte Ausgaben) ---
    $transactions_sql = "
        SELECT date, category, amount, notes 
        FROM expenses 
        WHERE car_id = ? 
        ORDER BY date DESC 
        LIMIT 10
    ";
    $stmt = $conn->prepare($transactions_sql);
    $stmt->execute([$car_id]);
    $results['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // --- V. 返回最终 JSON ---
    echo json_encode($results);

} catch (Exception $e) {
    // 捕获所有错误并返回 JSON 错误信息
    http_response_code(400); 
    echo json_encode(['error' => $e->getMessage()]);
}

?>