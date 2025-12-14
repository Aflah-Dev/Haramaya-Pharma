<?php


//HARAMAYA PHARMA - Add Medicines to Stock

//This script adds 50+ medicines with stock batches to the database

echo "Adding medicines to Haramaya Pharma stock...\n";

// Include database configuration
$pdo = require __DIR__ . '/config/database.php';

// Comprehensive list of medicines
$medicines = [
    // Antibiotics
    ['AMOX500', 'Amoxicillin 500mg', 'Amoxicillin', 1, '500mg', 'Capsule', 18.50, 50],
    ['AZITH250', 'Azithromycin 250mg', 'Azithromycin', 1, '250mg', 'Tablet', 45.00, 30],
    ['CIPRO500', 'Ciprofloxacin 500mg', 'Ciprofloxacin', 1, '500mg', 'Tablet', 25.00, 40],
    ['DOXY100', 'Doxycycline 100mg', 'Doxycycline', 1, '100mg', 'Capsule', 12.00, 60],
    ['CEPH250', 'Cephalexin 250mg', 'Cephalexin', 1, '250mg', 'Capsule', 22.00, 45],
    ['METRO400', 'Metronidazole 400mg', 'Metronidazole', 1, '400mg', 'Tablet', 8.50, 80],
    ['CLARI500', 'Clarithromycin 500mg', 'Clarithromycin', 1, '500mg', 'Tablet', 55.00, 25],
    ['FLUCON150', 'Fluconazole 150mg', 'Fluconazole', 1, '150mg', 'Capsule', 35.00, 30],

    // Pain Relief & Anti-inflammatory
    ['PARA500', 'Paracetamol 500mg', 'Paracetamol', 2, '500mg', 'Tablet', 2.50, 200],
    ['IBU400', 'Ibuprofen 400mg', 'Ibuprofen', 2, '400mg', 'Tablet', 5.50, 150],
    ['ASPIR100', 'Aspirin 100mg', 'Acetylsalicylic Acid', 2, '100mg', 'Tablet', 3.00, 180],
    ['DICLO50', 'Diclofenac 50mg', 'Diclofenac', 2, '50mg', 'Tablet', 8.00, 100],
    ['NAPRO250', 'Naproxen 250mg', 'Naproxen', 2, '250mg', 'Tablet', 12.00, 80],
    ['INDO25', 'Indomethacin 25mg', 'Indomethacin', 2, '25mg', 'Capsule', 15.00, 60],
    ['TRAM50', 'Tramadol 50mg', 'Tramadol', 2, '50mg', 'Capsule', 18.00, 40],

    // Vitamins & Supplements
    ['VITC100', 'Vitamin C 100mg', 'Ascorbic Acid', 3, '100mg', 'Tablet', 5.00, 300],
    ['VITD1000', 'Vitamin D3 1000IU', 'Cholecalciferol', 3, '1000IU', 'Tablet', 12.00, 200],
    ['VITB12', 'Vitamin B12 1000mcg', 'Cyanocobalamin', 3, '1000mcg', 'Tablet', 15.00, 150],
    ['FOLIC5', 'Folic Acid 5mg', 'Folic Acid', 3, '5mg', 'Tablet', 3.50, 250],
    ['IRON65', 'Iron 65mg', 'Ferrous Sulfate', 3, '65mg', 'Tablet', 8.00, 180],
    ['CALC600', 'Calcium 600mg', 'Calcium Carbonate', 3, '600mg', 'Tablet', 10.00, 200],
    ['MULTIVIT', 'Multivitamin', 'Mixed Vitamins', 3, 'Complex', 'Tablet', 20.00, 100],

    // Cardiovascular
    ['ATEN50', 'Atenolol 50mg', 'Atenolol', 4, '50mg', 'Tablet', 15.00, 80],
    ['AMLO5', 'Amlodipine 5mg', 'Amlodipine', 4, '5mg', 'Tablet', 12.00, 100],
    ['LISIN10', 'Lisinopril 10mg', 'Lisinopril', 4, '10mg', 'Tablet', 18.00, 70],
    ['METRO25', 'Metoprolol 25mg', 'Metoprolol', 4, '25mg', 'Tablet', 20.00, 60],
    ['HYDRO25', 'Hydrochlorothiazide 25mg', 'HCTZ', 4, '25mg', 'Tablet', 8.00, 120],
    ['SIMVA20', 'Simvastatin 20mg', 'Simvastatin', 4, '20mg', 'Tablet', 25.00, 50],

    // Respiratory
    ['SALBU2', 'Salbutamol 2mg', 'Salbutamol', 5, '2mg', 'Tablet', 6.00, 100],
    ['PRED5', 'Prednisolone 5mg', 'Prednisolone', 5, '5mg', 'Tablet', 4.50, 150],
    ['DEXTRO15', 'Dextromethorphan 15mg', 'Dextromethorphan', 5, '15mg', 'Syrup', 12.00, 80],
    ['GUAIF100', 'Guaifenesin 100mg', 'Guaifenesin', 5, '100mg', 'Tablet', 8.00, 120],
    ['LORA10', 'Loratadine 10mg', 'Loratadine', 5, '10mg', 'Tablet', 15.00, 90],

    // Digestive
    ['OMEP20', 'Omeprazole 20mg', 'Omeprazole', 6, '20mg', 'Capsule', 22.00, 80],
    ['RANI150', 'Ranitidine 150mg', 'Ranitidine', 6, '150mg', 'Tablet', 12.00, 100],
    ['SIMETH40', 'Simethicone 40mg', 'Simethicone', 6, '40mg', 'Tablet', 6.00, 150],
    ['LOPERA2', 'Loperamide 2mg', 'Loperamide', 6, '2mg', 'Capsule', 8.00, 80],
    ['BISMU262', 'Bismuth Subsalicylate 262mg', 'Bismuth', 6, '262mg', 'Tablet', 10.00, 100],

    // Diabetes
    ['METF500', 'Metformin 500mg', 'Metformin', 7, '500mg', 'Tablet', 8.50, 120],
    ['GLIB5', 'Glibenclamide 5mg', 'Glibenclamide', 7, '5mg', 'Tablet', 12.00, 80],
    ['INSUL10', 'Insulin 10ml', 'Human Insulin', 7, '100IU/ml', 'Injection', 85.00, 20],

    // Dermatology
    ['HYDRO1', 'Hydrocortisone 1%', 'Hydrocortisone', 8, '1%', 'Cream', 15.00, 60],
    ['CLOTRI1', 'Clotrimazole 1%', 'Clotrimazole', 8, '1%', 'Cream', 18.00, 50],
    ['CALAMINE', 'Calamine Lotion', 'Calamine', 8, '15%', 'Lotion', 8.00, 80],

    // Additional Common Medicines
    ['CETIRIZ10', 'Cetirizine 10mg', 'Cetirizine', 5, '10mg', 'Tablet', 12.00, 100],
    ['DOMPER10', 'Domperidone 10mg', 'Domperidone', 6, '10mg', 'Tablet', 8.00, 120],
    ['PARACET125', 'Paracetamol 125mg Syrup', 'Paracetamol', 2, '125mg/5ml', 'Syrup', 15.00, 60],
    ['AMBROX30', 'Ambroxol 30mg', 'Ambroxol', 5, '30mg', 'Tablet', 10.00, 100],
    ['CHLOR4', 'Chlorpheniramine 4mg', 'Chlorpheniramine', 5, '4mg', 'Tablet', 3.50, 200],
    ['MAGNE400', 'Magnesium 400mg', 'Magnesium Oxide', 3, '400mg', 'Tablet', 12.00, 150],
    ['ZINC20', 'Zinc 20mg', 'Zinc Sulfate', 3, '20mg', 'Tablet', 8.00, 180],
    ['PROBIO', 'Probiotic Capsules', 'Lactobacillus', 6, '10B CFU', 'Capsule', 25.00, 80],
    ['GLYCER', 'Glycerin Suppository', 'Glycerin', 6, '2g', 'Suppository', 5.00, 100],
    ['BETAD', 'Betadine Solution', 'Povidone Iodine', 8, '10%', 'Solution', 12.00, 50],
    ['SALINE', 'Normal Saline', 'Sodium Chloride', 8, '0.9%', 'Solution', 8.00, 100],
    ['GLUCOSE5', 'Glucose 5%', 'Dextrose', 6, '5%', 'Solution', 15.00, 40]
];

try {
    $pdo->beginTransaction();
    
    // Insert products
    $product_stmt = $pdo->prepare("
        INSERT INTO products (product_code, product_name, generic_name, category_id, strength, dosage_form, unit_price, reorder_level) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        product_name = VALUES(product_name),
        unit_price = VALUES(unit_price),
        reorder_level = VALUES(reorder_level)
    ");
    
    foreach ($medicines as $medicine) {
        $product_stmt->execute($medicine);
        echo "Added product: {$medicine[1]}\n";
    }
    
    // Get all products for stock batch creation
    $products = $pdo->query("SELECT product_id, product_code, product_name FROM products")->fetchAll();
    
    // Insert stock batches for each product
    $batch_stmt = $pdo->prepare("
        INSERT INTO stock_batches (product_id, supplier_id, batch_number, quantity_received, quantity_remaining, unit_cost, expiry_date, received_date, received_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $suppliers = [1, 2]; // We have 2 suppliers
    $batch_counter = 1;
    
    foreach ($products as $product) {
        // Create 1-3 batches per product
        $num_batches = rand(1, 3);
        
        for ($i = 0; $i < $num_batches; $i++) {
            $supplier_id = $suppliers[array_rand($suppliers)];
            $batch_number = $product['product_code'] . str_pad($batch_counter++, 3, '0', STR_PAD_LEFT);
            $quantity = rand(50, 500); // Random quantity between 50-500
            $unit_cost = rand(50, 95) / 100; // Cost is 50-95% of selling price
            
            // Random expiry date between 6 months to 3 years from now
            $expiry_months = rand(6, 36);
            $expiry_date = date('Y-m-d', strtotime("+{$expiry_months} months"));
            
            // Random received date within last 6 months
            $received_days = rand(1, 180);
            $received_date = date('Y-m-d', strtotime("-{$received_days} days"));
            
            $batch_stmt->execute([
                $product['product_id'],
                $supplier_id,
                $batch_number,
                $quantity,
                $quantity - rand(0, min(50, $quantity)), // Some items may be sold
                $unit_cost,
                $expiry_date,
                $received_date
            ]);
            
            echo "Added stock batch: {$batch_number} for {$product['product_name']} (Qty: {$quantity})\n";
        }
    }
    
    $pdo->commit();
    
    // Get final count
    $product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $batch_count = $pdo->query("SELECT COUNT(*) FROM stock_batches")->fetchColumn();
    $total_stock = $pdo->query("SELECT SUM(quantity_remaining) FROM stock_batches")->fetchColumn();
    
    echo "\n✅ Successfully added medicines to stock!\n";
    echo "📊 Summary:\n";
    echo "   - Total Products: {$product_count}\n";
    echo "   - Total Stock Batches: {$batch_count}\n";
    echo "   - Total Items in Stock: {$total_stock}\n";
    echo "\nYou can now view the stock in your application at: http://localhost:8080\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n Failed to add medicines: " . $e->getMessage() . "\n";
    exit(1);
}
