<?php
class StatistikService {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

// Initializes an empty data structure to avoid frontend errors.
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
            'chartData' => ['values' => []],
            'transactions' => []
        ];
    }

//  Retrieves all of the user's vehicles.
    public function getUserCars(int $user_id): array {
        $stmt = $this->conn->prepare("SELECT id, brand, model, licenseplate FROM garage WHERE user_id = :uid ORDER BY brand, model");
        $stmt->execute(['uid' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function determineSelectedCar(array $cars): ?int {
        if (empty($cars)) return null;
        $requestedCarId = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
        foreach ($cars as $car) {
            if ((int)$car['id'] === $requestedCarId) return $requestedCarId;
        }
        return (int)$cars[0]['id'];
    }

    
    public function getCarStatistics(int $car_id, int $user_id, string $unit): array {
        // 1. Receive basic vehicle information and the current mileage.
        $stmt = $this->conn->prepare("
            SELECT brand, model, licenseplate, tankvolume, 
            (SELECT mileage FROM expenses WHERE car_id = g.id ORDER BY mileage DESC LIMIT 1) as latest_mileage
            FROM garage g WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$car_id, $user_id]);
        $vehicleData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehicleData) return $this->initializeEmptyResults();

        $results = $this->initializeEmptyResults($vehicleData['brand'] . ' ' . $vehicleData['model']);
        $results['kpis']['mileage'] = (int)$vehicleData['latest_mileage'];

        // 2. Calculate cost KPIs (monthly/annually)
        $stmt = $this->conn->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN amount END), 0) as annual,
                COALESCE(SUM(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN amount END), 0) as monthly
            FROM expenses WHERE car_id = ?
        ");
        $stmt->execute([$car_id]);
        $costs = $stmt->fetch(PDO::FETCH_ASSOC);
        $results['kpis']['annualTotalCosts'] = (float)$costs['annual'];
        $results['kpis']['monthlyTotalCosts'] = (float)$costs['monthly'];

        // 3. Calculate fuel consumption and create diagrams (based on interval algorithms)
        $consumptionData = $this->calculateFuelLogic($car_id, $unit);
        $results['kpis']['averageConsumption'] = round($consumptionData['avg'], 2);
        $results['kpis']['fuelCostsPerKm'] = round($consumptionData['cost_per_km'], 3);
        $results['chartData']['values'] = $consumptionData['chart'];

        // 4. Calculate range (based on tank volume)
        $results['kpis']['range'] = $this->calculateRange($car_id, $consumptionData['avg'], (float)$vehicleData['tankvolume']);

        // 5. Recent transaction records
        $stmt = $this->conn->prepare("SELECT date, category, amount FROM expenses WHERE car_id = ? ORDER BY date DESC, id DESC LIMIT 4");
        $stmt->execute([$car_id]);
        $results['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }

    // Core fuel consumption logic: Handles the fulltank flag
    private function calculateFuelLogic(int $car_id, string $unit): array {
        $stmt = $this->conn->prepare("SELECT date, mileage, quantity, amount, full_tank FROM expenses WHERE car_id = ? AND category = 'Sprit' ORDER BY mileage ASC");
        $stmt->execute([$car_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalDist = 0; $totalQty = 0; $totalAmt = 0;
        $tempQty = 0; $firstFullFound = false; $lastFullMileage = 0;
        $chartPoints = [];

        foreach ($records as $r) {
            $totalAmt += (float)$r['amount'];
            $currMileage = (float)$r['mileage'];
            $isFull = (int)$r['full_tank'] === 1;

            if ($isFull) {
                if (!$firstFullFound) {
                    $firstFullFound = true;
                } else {
                    $dist = $currMileage - $lastFullMileage;
                    $combinedQty = $tempQty + (float)$r['quantity'];
                    if ($dist > 0) {
                        $totalDist += $dist;
                        $totalQty += $combinedQty;

                        $chartPoints[] = ['date' => $r['date'], 'val' => ($combinedQty / $dist) * 100];
                    }
                }
                $lastFullMileage = $currMileage;
                $tempQty = 0;
            } else {
                if ($firstFullFound) $tempQty += (float)$r['quantity'];
            }
        }

        return [
            'avg' => $totalDist > 0 ? ($totalQty / $totalDist) * 100 : 0,
            'cost_per_km' => $totalDist > 0 ? $totalAmt / $totalDist : 0,
            'chart' => $this->formatChart($chartPoints, $unit)
        ];
    }

    private function calculateRange(int $car_id, float $avg, float $tankVolume): int {
        if ($avg <= 0 || $tankVolume <= 0) return 0;

        // Find the final full tank mileage
        $stmt = $this->conn->prepare("SELECT mileage FROM expenses WHERE car_id = ? AND full_tank = 1 ORDER BY mileage DESC LIMIT 1");
        $stmt->execute([$car_id]);
        $lastFullMileage = $stmt->fetchColumn();
        if (!$lastFullMileage) return 0;

        // Calculate the total consumption and replenishment amount after filling the tank.
        $stmt = $this->conn->prepare("
            SELECT MAX(mileage) as now, SUM(CASE WHEN full_tank = 0 THEN quantity ELSE 0 END) as extra 
            FROM expenses WHERE car_id = ? AND mileage >= ?
        ");
        $stmt->execute([$car_id, $lastFullMileage]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $driven = (float)$row['now'] - (float)$lastFullMileage;
        $rem = $tankVolume - ($driven * $avg / 100) + (float)$row['extra'];
        $rem = max(0, min($rem, $tankVolume));

        return (int)round(($rem / $avg) * 100);
    }

    private function formatChart($points, $unit) {
        $fmt = match($unit) { 'year' => 'Y', 'month' => 'Y-m', default => 'Y-m-d' };
        $tmp = [];
        foreach ($points as $p) {
            $key = date($fmt, strtotime($p['date']));
            $tmp[$key][] = $p['val'];
        }
        $res = [];
        foreach ($tmp as $k => $v) $res[] = ['x' => $k, 'y' => round(array_sum($v)/count($v), 2)];
        return $res;
    }
}