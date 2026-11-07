<?php
include 'config.php';

if (isset($_GET['query'])) {
    $query_raw = $_GET['query'];
    $query = strtolower($query_raw);

    $stop_words = [
        'a', 'an', 'and', 'the', 'in', 'on','need', 'for', 'with', 'to', 'i', 'want',
        'give', 'me', 'my', 'is', 'are', 'was', 'were', 'it', 'of', 'color', 'size'
    ];

    $words = explode(" ", $query);

    $words = array_filter($words, function($word) use ($stop_words) {
        return !in_array(trim($word), $stop_words) && !empty(trim($word));
    });

    $color_keywords = ['red', 'blue', 'white', 'black', 'green', 'yellow', 'pink', 'purple', 'orange'];
    $size_keywords = ['small', 'medium', 'large', 'xl', 'xxl'];
    $user_keywords = [
        'baby' => 'baby', 'child' => 'child', 'kid' => 'child', 'boy' => 'child', 'girl' => 'child',
        'adult' => 'adult', 'man' => 'adult', 'woman' => 'adult', 'father' => 'adult', 'mother' => 'adult',
        'dad' => 'adult', 'mom' => 'adult', 'brother' => 'adult', 'sister' => 'adult', 'men' => 'adult', 'women' => 'adult'
    ];

    $color = '';
    $size = '';
    $user = '';
    $name_keywords = [];

    foreach ($words as $word) {
        $word = trim($word);
        if (in_array($word, $color_keywords)) {
            $color = $word;
        } elseif (in_array($word, $size_keywords)) {
            $size = $word;
        } elseif (array_key_exists($word, $user_keywords)) {
            $user = $user_keywords[$word];
        } else {
            $name_keywords[] = $word;
        }
    }

    $conditions = [];
    $params = [];
    $types = '';

    if ($color) {
        $conditions[] = "LOWER(color) LIKE ?";
        $params[] = "%$color%";
        $types .= 's';
    }
    
    if ($size) {
        $conditions[] = "LOWER(size) LIKE ?";
        $params[] = "%$size%"; 
        $types .= 's';
    }
    
    if ($user) {
        $conditions[] = "LOWER(user) LIKE ?";
        $params[] = "%$user%";
        $types .= 's';
    }

    if (!empty($name_keywords)) {
        foreach ($name_keywords as $kw) {
            $conditions[] = "(LOWER(name) LIKE ? OR LOWER(tags) LIKE ?)";
            $params[] = "%$kw%";
            $params[] = "%$kw%";
            $types .= 'ss';
        }
    }

    if (empty($conditions)) {
        echo "Please enter a valid search term.";
        exit;
    }

    $sql = "SELECT * FROM products WHERE " . implode(" AND ", $conditions);
    
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total_results = $result ? mysqli_num_rows($result) : 0;

    // Start Session for header
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - LIXN LEE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .search-container { padding: 40px; margin-top: 120px; }
        .results-summary { background: #f9f9f9; padding: 20px; border-radius: 10px; margin-bottom: 30px; border-left: 5px solid var(--primary); }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        .product-card { background: #fff; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .product-image { height: 350px; position: relative; overflow: hidden; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-info { padding: 20px; text-align: center; }
        .product-info h3 { font-family: 'Playfair Display', serif; font-size: 1.2rem; margin-bottom: 10px; }
        .product-info .price { color: #83666e; font-weight: 700; font-size: 1.1rem; margin-bottom: 15px; }
        .btn-group { display: flex; gap: 10px; justify-content: center; }
        .btn-action { padding: 10px 20px; border-radius: 50px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: 0.3s; }
        .btn-details { background: #f0f0f0; color: #333; }
        .btn-cart { background: #333; color: #fff; }
        .btn-cart:hover { background: #000; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="search-container container">
        <h1 style="font-family: 'Playfair Display', serif; margin-bottom: 20px;">Search Results</h1>
        
        <?php if ($total_results > 0): ?>
            <div class="results-summary">
                <p>Found <strong><?php echo $total_results; ?></strong> items for "<?php echo htmlspecialchars($query_raw); ?>"</p>
            </div>

            <div class="products-grid">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="uploaded_img/<?php echo $row['image']; ?>" alt="">
                        </div>
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <div class="price">RS <?php echo number_format($row['price'], 2); ?></div>
                            <div class="btn-group">
                                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="btn-action btn-details">View Details</a>
                                <a href="cart.php?add=<?php echo $row['id']; ?>" class="btn-action btn-cart">Add to Cart</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 100px 0;">
                <i class="fas fa-search" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
                <h2>No products found</h2>
                <p>Try searching for something else or <a href="products.php">browse all products</a>.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>

<?php
} else {
    echo "No search query provided.";
}
?>