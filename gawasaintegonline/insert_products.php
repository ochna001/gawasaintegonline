<?php
require_once 'includes/config.php';

// First, make sure the categories table is populated
$categories = [
    ['name' => 'breakfast', 'description' => 'Breakfast meals'],
    ['name' => 'lunch', 'description' => 'Lunch meals'],
    ['name' => 'dinner', 'description' => 'Dinner meals'],
    ['name' => 'desserts', 'description' => 'Desserts and sweets'],
    ['name' => 'beverages', 'description' => 'Drinks and beverages']
];

// Insert categories first
foreach ($categories as $category) {
    // Check if category already exists
    $check_query = "SELECT category_id FROM categories WHERE name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $category['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Insert new category
        $insert_query = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ss", $category['name'], $category['description']);
        $stmt->execute();
        echo "Added category: " . $category['name'] . "<br>";
    } else {
        echo "Category " . $category['name'] . " already exists<br>";
    }
}

// Get category IDs
$category_ids = [];
foreach ($categories as $category) {
    $query = "SELECT category_id FROM categories WHERE name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $category['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $category_ids[$category['name']] = $row['category_id'];
    }
}

// Products data from menu.html
$products = [
    // Breakfast
    [
        'name' => 'Tapsilog',
        'description' => 'Marinated beef, garlic rice, and fried egg',
        'price' => 120.00,
        'image_path' => 'assets/tapsilog.jpg',
        'category_id' => $category_ids['breakfast'],
        'available' => 1
    ],
    [
        'name' => 'Longsilog',
        'description' => 'Sweet pork sausage, garlic rice, and fried egg',
        'price' => 110.00,
        'image_path' => 'assets/longsilog.jpg',
        'category_id' => $category_ids['breakfast'],
        'available' => 1
    ],
    
    // Lunch
    [
        'name' => 'Chicken Adobo',
        'description' => 'Classic Filipino dish with tender chicken in soy-vinegar sauce',
        'price' => 150.00,
        'image_path' => 'assets/product-1.jpg',
        'category_id' => $category_ids['lunch'],
        'available' => 1
    ],
    [
        'name' => 'Sinigang na Baboy',
        'description' => 'Sour tamarind soup with pork and vegetables',
        'price' => 180.00,
        'image_path' => 'assets/product-2.jpg',
        'category_id' => $category_ids['lunch'],
        'available' => 1
    ],
    
    // Dinner
    [
        'name' => 'Kare-Kare',
        'description' => 'Rich peanut stew with oxtail and vegetables',
        'price' => 200.00,
        'image_path' => 'assets/product-3.jpg',
        'category_id' => $category_ids['dinner'],
        'available' => 1
    ],
    [
        'name' => 'Crispy Pata',
        'description' => 'Deep-fried pork knuckle served with special sauce',
        'price' => 350.00,
        'image_path' => 'assets/crispypata.jpg',
        'category_id' => $category_ids['dinner'],
        'available' => 1
    ],
    
    // Desserts
    [
        'name' => 'Halo-Halo',
        'description' => 'Mixed fruits, beans, and shaved ice with milk and leche flan',
        'price' => 90.00,
        'image_path' => 'assets/halohalo.jpg',
        'category_id' => $category_ids['desserts'],
        'available' => 1
    ],
    [
        'name' => 'Leche Flan',
        'description' => 'Classic Filipino caramel custard',
        'price' => 80.00,
        'image_path' => 'assets/lecheflan.jpg',
        'category_id' => $category_ids['desserts'],
        'available' => 1
    ],
    
    // Beverages
    [
        'name' => "Sago't Gulaman",
        'description' => 'Sweet brown sugar drink with sago pearls and gulaman',
        'price' => 50.00,
        'image_path' => 'assets/sagotgulaman.jpg',
        'category_id' => $category_ids['beverages'],
        'available' => 1
    ],
    [
        'name' => 'Fresh Buko Juice',
        'description' => 'Fresh coconut water with coconut meat',
        'price' => 60.00,
        'image_path' => 'assets/bukojuice.jpg',
        'category_id' => $category_ids['beverages'],
        'available' => 1
    ]
];

// Insert products
foreach ($products as $product) {
    // Check if product already exists
    $check_query = "SELECT product_id FROM products WHERE name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $product['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Insert new product
        $insert_query = "INSERT INTO products (name, description, price, image_path, category_id, available) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssdsii", 
            $product['name'], 
            $product['description'], 
            $product['price'], 
            $product['image_path'], 
            $product['category_id'], 
            $product['available']
        );
        $stmt->execute();
        echo "Added product: " . $product['name'] . "<br>";
    } else {
        echo "Product " . $product['name'] . " already exists<br>";
    }
}

echo "<p>Product insertion completed.</p>";
echo "<p><a href='menu.php'>View Menu</a></p>";
?>
