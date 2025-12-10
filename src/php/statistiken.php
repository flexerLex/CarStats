<?php
session_start();
header('Content-Type: application/json');

// if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
//     http_response_code(401); 
//     echo json_encode(['error' => 'Nicht angemeldet.']);
//     exit;
// }
$user_id_debug = 1; 

require_once 'connect_DB.php'; 

$conn = getDBConnection();
// $user_id = $_SESSION['user_id'];
$user_id = $user_id_debug;

$car_id = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
$unit = filter_input(INPUT_GET, 'unit', FILTER_SANITIZE_STRING) ?? 'month'; 

$results = ['kpis' => [], 'chartData' => ['labels' => [], 'values' => []], 'transactions' => []];
$error = null;

try {
    if (!$car_id) {
         throw new Exception('Fehlende oder ungültige Fahrzeug-ID.');
    }
    
    $stmt = $conn->prepare("SELECT id, brand, model FROM garage WHERE id = ? AND user_id = ?");
    $stmt->execute([$car_id, $user_id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        throw new Exception('Fahrzeug nicht gefunden oder keine Berechtigung.');
    }
    
    $results['kpis']['carModel'] = $vehicle['brand'] . ' ' . $vehicle['model'];
    
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

    $consumption_sql = "
        SELECT date, amount, mileage, quantity 
        FROM expenses 
        WHERE car_id = ? AND category = 'Kraftstoff' AND full_tank = 1 
        ORDER BY date ASC, mileage ASC
    ";
    $stmt = $conn->prepare($consumption_sql);
    $stmt->execute([$car_id]);
    $full_tank_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_distance = 0;
    $total_quantity = 0;
    
    for ($i = 1; $i < count($full_tank_records); $i++) {
        $prev_mileage = (float)$full_tank_records[$i-1]['mileage'];
        $curr_mileage = (float)$full_tank_records[$i]['mileage'];
        $curr_quantity = (float)$full_tank_records[$i]['quantity'];
        
        $distance = $curr_mileage - $prev_mileage;
        $quantity = $curr_quantity; 
        
        if ($distance > 0) {
            $total_distance += $distance;
            $total_quantity += $quantity;
        }
    }
    
    $stmt = $conn->prepare("SELECT SUM(amount) AS total_fuel_costs FROM expenses WHERE car_id = ? AND category = 'Kraftstoff'");
    $stmt->execute([$car_id]);
    $fuel_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_fuel_costs = $fuel_data['total_fuel_costs'] ?? 0;


    $avg_consumption = 0.00; 
    $fuel_cost_per_km = 0.00; 
    
    if ($total_distance > 0) {
        $avg_consumption = ($total_quantity / $total_distance) * 100;
        $fuel_cost_per_km = $total_fuel_costs / $total_distance;
    }
    
    $results['kpis']['averageConsumption'] = round($avg_consumption, 2); 
    $results['kpis']['fuelCostsPerKm'] = round($fuel_cost_per_km, 2);

    $stmt = $conn->prepare("SELECT mileage FROM expenses WHERE car_id = ? ORDER BY date DESC, id DESC LIMIT 1");
    $stmt->execute([$car_id]);
    $latest_mileage = $stmt->fetchColumn();
    $results['kpis']['mileage'] = $latest_mileage ?? 0; 
    $TANK_CAPACITY = 50.0; 

    $last_full_tank_date_sql = "
        SELECT date 
        FROM expenses 
        WHERE car_id = ? AND full_tank = 1 
        ORDER BY date DESC 
        LIMIT 1
    ";
    $stmt = $conn->prepare($last_full_tank_date_sql);
    $stmt->execute([$car_id]);
    $last_full_tank_date = $stmt->fetchColumn();

    $total_fuel_added_since_full = 0.0;
    if ($last_full_tank_date) {
        $fuel_since_full_sql = "
            SELECT SUM(quantity) AS total_added 
            FROM expenses 
            WHERE car_id = ? AND date > ? AND full_tank = 0 AND category = 'Kraftstoff'
        ";
        $stmt = $conn->prepare($fuel_since_full_sql);
        $stmt->execute([$car_id, $last_full_tank_date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_fuel_added_since_full = (float)($result['total_added'] ?? 0.0);
    }

    // Remaining quantity
    $remaining_fuel = $TANK_CAPACITY - $total_fuel_added_since_full;

    // Reichweite = (Restliche Menge / Durchschnittsverbrauch) * 100
    $range = 0.00;
    if ($avg_consumption > 0 && $remaining_fuel > 0) {
        $range = ($remaining_fuel / $avg_consumption) * 100;
    }
    $results['kpis']['range'] = round($range, 0); 

    // --- Fuel consumption trend: L/100km ---

    $consumption_data_by_period = [];
    
    $unit = filter_input(INPUT_GET, 'unit', FILTER_SANITIZE_STRING) ?? 'month'; 
    $date_format_php = 'Y-m-d'; 

    switch ($unit) {
        case 'year':
            $date_format_php = 'Y';
            break;
        case 'month':
            $date_format_php = 'Y-m';
            break;
        case 'day':
        default:
            $date_format_php = 'Y-m-d';
            break;
    }

    if (count($full_tank_records) >= 2) {
        for ($i = 1; $i < count($full_tank_records); $i++) {
            $prev_mileage = (float)$full_tank_records[$i-1]['mileage'];
            $curr_mileage = (float)$full_tank_records[$i]['mileage'];
            $curr_quantity = (float)$full_tank_records[$i]['quantity'];
            $curr_date = $full_tank_records[$i]['date']; 

            $distance = $curr_mileage - $prev_mileage;
            
            if ($distance > 0) {
                $consumption_l_per_100km = ($curr_quantity / $distance) * 100;
                
                $period_label = date($date_format_php, strtotime($curr_date));
                
                if (!isset($consumption_data_by_period[$period_label])) {
                    $consumption_data_by_period[$period_label] = [
                        'sum' => 0, 
                        'count' => 0
                    ];
                }
                $consumption_data_by_period[$period_label]['sum'] += $consumption_l_per_100km;
                $consumption_data_by_period[$period_label]['count'] += 1;
            }
        }
    }   

    $chart_labels = [];
    $chart_values = [];
    ksort($consumption_data_by_period); 

    foreach ($consumption_data_by_period as $label => $data) {
        $average_consumption = round($data['sum'] / $data['count'], 2);
        $chart_labels[] = $label;
        $chart_values[] = $average_consumption;
    }
    $results['chartData']['labels'] = $chart_labels;
    $results['chartData']['values'] = $chart_values;

    if (empty($chart_labels)) {
        $results['chartData']['labels'] = [];
        $results['chartData']['values'] = [];
    }

    // --------------- last transactions ------------------------
    $transactions_sql = "
        SELECT date, category, amount, notes 
        FROM expenses 
        WHERE car_id = ? 
        ORDER BY date DESC 
        LIMIT 4
    ";
    $stmt = $conn->prepare($transactions_sql);
    $stmt->execute([$car_id]);
    $results['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --------------- return JSON ----------------
    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(400); 
    echo json_encode(['error' => $e->getMessage()]);
}

?>