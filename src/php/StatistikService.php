<?php
class StatistikService {
    private $conn;
    const TANK_CAPACITY = 50.0; 

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function initializeEmptyResults($carModel = '') {
        return [ 
            'kpis' => [
                'carModel' => $carModel, 
                'averageConsumption' => 0.00, 
                'fuelCostsPerKm' => 0.000,
                'mileage' => 0,
                'range' => 0,
                'monthlyTotalCosts' => 0.00,
                'annualTotalCosts' => 0.00,
            ], 
            'chartData' => ['labels' => [], 'values' => []], 
            'transactions' => [] 
        ];
    }
   
    public function getUserCars(int $user_id): array {
        try {
            $stmt = $this->conn->prepare("SELECT id, brand, model, licenseplate FROM garage WHERE user_id = :uid ORDER BY brand, model");
            $stmt->execute(['uid' => $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error fetching user cars: " . $e->getMessage());
            return []; 
        }
    }
    
    public function determineSelectedCar(array $cars): ?int {
        if (empty($cars)) return null;

        $requestedCarId = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
        
        $isUserCar = array_filter($cars, fn($car) => (int)$car['id'] === $requestedCarId); // PHP 7.4+ Kurzschreibweise
        
        return !empty($isUserCar) ? $requestedCarId : (int)$cars[0]['id'];
    }

    // Kombinierte Abfrage für Fahrzeugdetails und Kilometerstand
    private function getVehicleBaseData(int $car_id, int $user_id): array {
        $sql = "
            SELECT 
                g.brand, g.model, g.licenseplate,
                (SELECT mileage FROM expenses WHERE car_id = g.id ORDER BY date DESC, id DESC LIMIT 1) AS latest_mileage
            FROM garage g
            WHERE g.id = ? AND g.user_id = ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$car_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function getTimeBasedCosts(int $car_id): array {
        $total_costs_sql = "
            SELECT 
                COALESCE(SUM(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN amount END), 0.00) AS annual_total,
                COALESCE(SUM(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN amount END), 0.00) AS monthly_total
            FROM expenses WHERE car_id = ?
        ";
        $stmt = $this->conn->prepare($total_costs_sql);
        $stmt->execute([$car_id]);
        $time_costs = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'annualTotalCosts' => (float)$time_costs['annual_total'],
            'monthlyTotalCosts' => (float)$time_costs['monthly_total']
        ];
    }

    private function getFuelRecords(int $car_id): array {
        $sql = "
            SELECT date, amount, mileage, quantity 
            FROM expenses 
            WHERE car_id = ? AND category = 'Sprit'  
            ORDER BY date ASC, mileage ASC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$car_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function calculateConsumptionKPIs(int $car_id): array {
        $records = $this->getFuelRecords($car_id);
        $total_distance = 0.0;
        $total_quantity = 0.0;
        
        for ($i = 1; $i < count($records); $i++) {
            $distance = (float)$records[$i]['mileage'] - (float)$records[$i-1]['mileage'];
            if ($distance > 0) {
                $total_distance += $distance;
                $total_quantity += (float)$records[$i]['quantity']; 
            }
        }
        
        $stmt = $this->conn->prepare("SELECT SUM(amount) AS total_fuel_costs FROM expenses WHERE car_id = ? AND category = 'Sprit'");
        $stmt->execute([$car_id]);
        $total_fuel_costs = $stmt->fetchColumn() ?? 0.0;

        $avg_consumption = $total_distance > 0 ? ($total_quantity / $total_distance) * 100 : 0.00;
        $fuel_cost_per_km = $total_distance > 0 ? $total_fuel_costs / $total_distance : 0.000;
        
        return [
            'avg_consumption' => round($avg_consumption, 2),
            'fuel_cost_per_km' => round($fuel_cost_per_km, 3),
            'full_tank_records' => $records // Rückgabe für Chart-Berechnung
        ];
    }
    
    private function calculateRange(int $car_id, float $avg_consumption): int {
        // Findet das letzte Volltanken
        $stmt = $this->conn->prepare("SELECT date FROM expenses WHERE car_id = ? AND full_tank = 1 ORDER BY date DESC LIMIT 1");
        $stmt->execute([$car_id]);
        $last_full_tank_date = $stmt->fetchColumn();

        $total_fuel_added_since_full = 0.0;
        if ($last_full_tank_date) {
            // Berechnet seit dem Volltanken nachgefüllten Kraftstoff
            $sql = "SELECT SUM(quantity) AS total_added FROM expenses WHERE car_id = ? AND date > ? AND category = 'Sprit'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$car_id, $last_full_tank_date]);
            $total_fuel_added_since_full = (float)($stmt->fetchColumn() ?? 0.0);
        }
        
        $remaining_fuel = self::TANK_CAPACITY - $total_fuel_added_since_full;
        
        $range = ($avg_consumption > 0 && $remaining_fuel > 0) 
                 ? ($remaining_fuel / $avg_consumption) * 100 
                 : 0.00;

        return (int)round($range, 0); 
    }
    
    private function getConsumptionChartData(array $full_tank_records, string $unit): array {
        if (count($full_tank_records) < 2) return [];

        $date_format_php = match($unit) {
            'year' => 'Y',
            'month' => 'Y-m',
            default => 'Y-m-d',
        };

        $consumption_data_by_period = [];

        for ($i = 1; $i < count($full_tank_records); $i++) {
            $prev_mileage = (float)$full_tank_records[$i-1]['mileage'];
            $curr_mileage = (float)$full_tank_records[$i]['mileage'];
            $curr_quantity = (float)$full_tank_records[$i]['quantity'];
            $curr_date = $full_tank_records[$i]['date']; 

            $distance = $curr_mileage - $prev_mileage;
            
            if ($distance > 0) {
                $consumption = ($curr_quantity / $distance) * 100;
                $period_label = date($date_format_php, strtotime($curr_date));
                
                $consumption_data_by_period[$period_label]['sum'] = ($consumption_data_by_period[$period_label]['sum'] ?? 0) + $consumption;
                $consumption_data_by_period[$period_label]['count'] = ($consumption_data_by_period[$period_label]['count'] ?? 0) + 1;
            }
        }   

        ksort($consumption_data_by_period); 

        $chart_values = [];
        foreach ($consumption_data_by_period as $label => $data) {
            $chart_values[] = [
                'x' => $label, 
                'y' => round($data['sum'] / $data['count'], 2)
            ];
        }
        return $chart_values;
    }
    
    private function getLatestTransactions(int $car_id): array {
        $sql = "
            SELECT DATE_FORMAT(date, '%Y-%m-%d') as date, category, amount, notes 
            FROM expenses 
            WHERE car_id = ? 
            ORDER BY date DESC 
            LIMIT 4
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$car_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCarStatistics(int $car_id, int $user_id, string $unit): array {
        $vehicleData = $this->getVehicleBaseData($car_id, $user_id);
        
        if (empty($vehicleData)) {
            error_log("Fahrzeug nicht gefunden oder keine Berechtigung für car_id: " . $car_id);
            return $this->initializeEmptyResults();
        }

        try {
            $results = $this->initializeEmptyResults();
            $results['kpis']['carModel'] = $vehicleData['brand'] . ' ' . $vehicleData['model'] . ' (' . $vehicleData['licenseplate'] . ')';
            $results['kpis']['mileage'] = (int)$vehicleData['latest_mileage'];

            $time_costs = $this->getTimeBasedCosts($car_id);
            $results['kpis'] = array_merge($results['kpis'], $time_costs);

            $consumption_data = $this->calculateConsumptionKPIs($car_id);
            $avg_consumption = $consumption_data['avg_consumption'];
            
            $results['kpis']['averageConsumption'] = $avg_consumption; 
            $results['kpis']['fuelCostsPerKm'] = $consumption_data['fuel_cost_per_km'];
            
            $results['kpis']['range'] = $this->calculateRange($car_id, $avg_consumption);
            
            $results['chartData']['values'] = $this->getConsumptionChartData($consumption_data['full_tank_records'], $unit);

            $results['transactions'] = $this->getLatestTransactions($car_id);
            
            return $results;

        } catch (Exception $e) {
            error_log("Statistiken Fehler in Service: " . $e->getMessage());
            $modelName = $vehicleData['brand'] . ' ' . $vehicleData['model'] . ' (' . $vehicleData['licenseplate'] . ')';
            return $this->initializeEmptyResults($modelName);
        }
    }
}