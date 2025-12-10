<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === 'fuel_types.php') {
    header('Content-Type: application/json');
    echo json_encode([
        'petrol' => 'Benzin (E5, Super E5, Super E10, Super Plus)',
        'diesel' => 'Diesel (B7, XTL)',
        'electric' => 'Elektro',
        'gas' => 'Gas (LPG/CNG)',
        'ethanol' => 'Ethanol (E85)',
        'hydrogen' => 'Wasserstoff (H2)',
        'other' => 'Sonstige'
    ]);
    exit;
}
?>