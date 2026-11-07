<?php
@include 'config.php';
session_start();

// Ensure admin authentication
if (!is_admin()) {
    header('Location: admin_login.php');
    exit();
}

$msg = "";

// Add Product Logic
if (isset($_POST['add_product'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $p_name = $_POST['p_name'];
    $p_price = $_POST['p_price'];
    $p_category = $_POST['p_category'];
    $p_stock = $_POST['p_stock'];
    $p_description = $_POST['p_description'] ?? '';
    $p_tags = $_POST['p_tags'] ?? '';
    
    $p_user = isset($_POST['p_user']) ? (is_array($_POST['p_user']) ? implode(', ', $_POST['p_user']) : $_POST['p_user']) : '';
    $p_size = isset($_POST['p_size']) ? (is_array($_POST['p_size']) ? implode(', ', $_POST['p_size']) : $_POST['p_size']) : '';
    $p_color = isset($_POST['p_color']) ? (is_array($_POST['p_color']) ? implode(', ', $_POST['p_color']) : $_POST['p_color']) : '';
    
    $hot_sale = isset($_POST['hot_sale']) ? "1" : "0";
    $new_arrival = isset($_POST['new_arrival']) ? "1" : "0";
    $top_sale = isset($_POST['top_sale']) ? "1" : "0";
    $free_delivery = isset($_POST['free_delivery']) ? 1 : 0;

    $p_image = '';
    if (!empty($_FILES['p_image']['name'])) {
        if (is_valid_image_upload($_FILES['p_image']['tmp_name'], $_FILES['p_image']['name'])) {
            $p_image = sanitize_image_filename($_FILES['p_image']['name']);
            move_uploaded_file($_FILES['p_image']['tmp_name'], 'uploaded_img/' . $p_image);
        } else {
            $msg = "<p class='msg error'>Invalid main image file format. Only JPG, PNG, WEBP, and GIF are allowed.</p>";
        }
    }

    $extra_images = [];
    if (isset($_FILES['p_extra_images']) && count($_FILES['p_extra_images']['name']) > 0) {
        foreach ($_FILES['p_extra_images']['name'] as $key => $extra_img_name) {
            $extra_img_tmp_name = $_FILES['p_extra_images']['tmp_name'][$key];
            if (!empty($extra_img_name) && is_valid_image_upload($extra_img_tmp_name, $extra_img_name)) {
                $safe_extra = sanitize_image_filename($extra_img_name);
                $extra_images[] = $safe_extra;
                move_uploaded_file($extra_img_tmp_name, 'uploaded_img/' . $safe_extra);
            }
        }
    }
    $extra_images_string = implode(',', $extra_images);

    $insert_query = execute_query($conn, "INSERT INTO products
    (name, price, category, user, size, color, hot_sale, new_arrival, top_sale, stock, image, extra_images, tags, description, free_delivery)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", "sdssssssssssssi", 
    [$p_name, $p_price, $p_category, $p_user, $p_size, $p_color, $hot_sale, $new_arrival, $top_sale, $p_stock, $p_image, $extra_images_string, $p_tags, $p_description, $free_delivery]);

    if ($insert_query) {
        $msg = "<p class='msg success'>Product added successfully!</p>";
    } else {
        $msg = "<p class='msg error'>Failed to add product.</p>";
    }
}

// Delete Product Logic
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $img_res = execute_query($conn, "SELECT image, extra_images FROM products WHERE id = ?", "i", [$id]);
    
    if ($img_res && mysqli_num_rows($img_res) > 0) {
        $img_row = mysqli_fetch_assoc($img_res);
        @unlink('uploaded_img/' . $img_row['image']);
        $extra_images_array = !empty($img_row['extra_images']) ? explode(',', $img_row['extra_images']) : [];
        foreach ($extra_images_array as $extra_img_name) {
            @unlink('uploaded_img/' . trim($extra_img_name));
        }
    }
    if (execute_query($conn, "DELETE FROM products WHERE id = ?", "i", [$id])) {
        $msg = "<p class='msg success'>Product deleted successfully!</p>";
    } else {
        $msg = "<p class='msg error'>Failed to delete product.</p>";
    }
}

// Update Product logic
if (isset($_POST['update_product'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }

    $update_id = intval($_POST['update_id']);
    $name = $_POST['update_p_name'];
    $price = $_POST['update_p_price'];
    $category = $_POST['update_p_category'];
    $stock = $_POST['update_p_stock'];
    $description = $_POST['update_p_description'] ?? '';
    $tags = $_POST['update_p_tags'] ?? '';
    
    $user = isset($_POST['update_p_user']) ? (is_array($_POST['update_p_user']) ? implode(', ', $_POST['update_p_user']) : $_POST['update_p_user']) : '';
    $size = isset($_POST['update_p_size']) ? (is_array($_POST['update_p_size']) ? implode(', ', $_POST['update_p_size']) : $_POST['update_p_size']) : '';
    $color = isset($_POST['update_p_color']) ? (is_array($_POST['update_p_color']) ? implode(', ', $_POST['update_p_color']) : $_POST['update_p_color']) : '';
    
    $hot = isset($_POST['hot_sale']) ? "1" : "0";
    $new = isset($_POST['new_arrival']) ? "1" : "0";
    $top = isset($_POST['top_sale']) ? "1" : "0";
    $free_delivery = isset($_POST['free_delivery']) ? 1 : 0;
    
    $old_data_query = execute_query($conn, "SELECT image, extra_images FROM products WHERE id = ?", "i", [$update_id]);
    $old_data = mysqli_fetch_assoc($old_data_query);

    $main_image = $old_data['image'];
    if (!empty($_FILES['update_p_image']['name'])) {
        if (is_valid_image_upload($_FILES['update_p_image']['tmp_name'], $_FILES['update_p_image']['name'])) {
            if (!empty($old_data['image']) && file_exists('uploaded_img/' . $old_data['image'])) {
                @unlink('uploaded_img/' . $old_data['image']);
            }
            $main_image = sanitize_image_filename($_FILES['update_p_image']['name']);
            move_uploaded_file($_FILES['update_p_image']['tmp_name'], 'uploaded_img/' . $main_image);
        } else {
            $msg = "<p class='msg error'>Invalid updated image format. Only JPG, PNG, WEBP, and GIF are allowed.</p>";
        }
    }

    $extra_images_string = $old_data['extra_images'];
    
    $update_query = execute_query($conn, "UPDATE products SET
        name=?, price=?, category=?, user=?, size=?, color=?,
        stock=?, tags=?, hot_sale=?, new_arrival=?, top_sale=?, free_delivery=?,
        image=?, description=? WHERE id=?", "sdsssssssssissi", 
        [$name, $price, $category, $user, $size, $color, $stock, $tags, $hot, $new, $top, $free_delivery, $main_image, $description, $update_id]);
    
    if ($update_query) {
        $msg = "<p class='msg success'>Product updated successfully!</p>";
    } else {
        $msg = "<p class='msg error'>Failed to update product.</p>";
    }
}

$edit_mode = false;
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    $edit_result = execute_query($conn, "SELECT * FROM products WHERE id = ?", "i", [$edit_id]);
    $edit_data = mysqli_fetch_assoc($edit_result);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - LIXN LEE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reusing some admin dash styles for consistency */
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; display:flex; margin:0; }
        .sidebar { width: 260px; background: linear-gradient(135deg, #83666e 0%, #6a4f56 100%); color: white; height: 100vh; position: fixed; }
        .main-content { flex: 1; margin-left: 260px; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .msg { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .msg.success { background: #d4edda; color: #155724; }
        .msg.error { background: #f8d7da; color: #721c24; }
        input, select, textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; }
        button { background: #83666e; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #83666e; color: white; }
        .action-btns a { margin-right: 10px; color: #83666e; text-decoration: none; }
    </style>
</head>
<body>

    <div class="sidebar">
        <!-- Re-using sidebar from admindash.php for brevity in this tool call -->
        <div style="padding:30px; text-align:center;">
            <h2>LIXN LEE</h2>
            <p>Admin Panel</p>
        </div>
        <div style="padding: 10px 0;">
            <a href="admindash.php" style="display:block; padding:15px 30px; color:white; text-decoration:none;">Dashboard</a>
            <a href="admin.php" style="display:block; padding:15px 30px; color:white; text-decoration:none; background:rgba(255,255,255,0.1);">Products</a>
            <a href="adminlogout.php" style="display:block; padding:15px 30px; color:white; text-decoration:none;">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>Product Management</h1>
        <?php echo $msg; ?>

        <div class="card">
            <h2><?php echo $edit_mode ? 'Edit Product' : 'Add New Product'; ?></h2>
            <form action="" method="post" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <?php if($edit_mode): ?>
                    <input type="hidden" name="update_id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label>Product Name</label>
                        <input type="text" name="<?php echo $edit_mode ? 'update_p_name' : 'p_name'; ?>" value="<?php echo $edit_data['name'] ?? ''; ?>" required>
                    </div>
                    <div>
                        <label>Price</label>
                        <input type="number" step="0.01" name="<?php echo $edit_mode ? 'update_p_price' : 'p_price'; ?>" value="<?php echo $edit_data['price'] ?? ''; ?>" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label>Category</label>
                        <select name="<?php echo $edit_mode ? 'update_p_category' : 'p_category'; ?>">
                            <option value="men" <?php echo ($edit_data['category'] ?? '') == 'men' ? 'selected' : ''; ?>>Men</option>
                            <option value="women" <?php echo ($edit_data['category'] ?? '') == 'women' ? 'selected' : ''; ?>>Women</option>
                            <option value="kids" <?php echo ($edit_data['category'] ?? '') == 'kids' ? 'selected' : ''; ?>>Kids</option>
                            <option value="tech" <?php echo ($edit_data['category'] ?? '') == 'tech' ? 'selected' : ''; ?>>Tech</option>
                            <option value="home appliance" <?php echo ($edit_data['category'] ?? '') == 'home appliance' ? 'selected' : ''; ?>>Home Appliance</option>
                        </select>
                    </div>
                    <div>
                        <label>Stock</label>
                        <input type="number" name="<?php echo $edit_mode ? 'update_p_stock' : 'p_stock'; ?>" value="<?php echo $edit_data['stock'] ?? ''; ?>" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label>User Type</label>
                        <select name="<?php echo $edit_mode ? 'update_p_user' : 'p_user'; ?>">
                            <option value="adult" <?php echo ($edit_data['user'] ?? '') == 'adult' ? 'selected' : ''; ?>>Adult</option>
                            <option value="child" <?php echo ($edit_data['user'] ?? '') == 'child' ? 'selected' : ''; ?>>Child</option>
                            <option value="unisex" <?php echo ($edit_data['user'] ?? '') == 'unisex' ? 'selected' : ''; ?>>Unisex</option>
                        </select>
                    </div>
                    <div>
                        <label>Tags (comma separated)</label>
                        <input type="text" name="<?php echo $edit_mode ? 'update_p_tags' : 'p_tags'; ?>" value="<?php echo $edit_data['tags'] ?? ''; ?>" placeholder="e.g. shirt, cotton, summer">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label>Available Sizes (comma separated)</label>
                        <input type="text" name="<?php echo $edit_mode ? 'update_p_size' : 'p_size'; ?>" value="<?php echo $edit_data['size'] ?? ''; ?>" placeholder="e.g. S, M, L, XL">
                    </div>
                    <div>
                        <label>Available Colors (comma separated)</label>
                        <input type="text" name="<?php echo $edit_mode ? 'update_p_color' : 'p_color'; ?>" value="<?php echo $edit_data['color'] ?? ''; ?>" placeholder="e.g. Red, Blue, Black">
                    </div>
                </div>

                <div style="display:flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; background: #f9f9f9; padding: 15px; border-radius: 8px;">
                    <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;">
                        <input type="checkbox" name="hot_sale" style="width:auto; margin-bottom:0;" <?php echo ($edit_data['hot_sale'] ?? '0') == '1' ? 'checked' : ''; ?>> Hot Sale
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;">
                        <input type="checkbox" name="new_arrival" style="width:auto; margin-bottom:0;" <?php echo ($edit_data['new_arrival'] ?? '0') == '1' ? 'checked' : ''; ?>> New Arrival
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;">
                        <input type="checkbox" name="top_sale" style="width:auto; margin-bottom:0;" <?php echo ($edit_data['top_sale'] ?? '0') == '1' ? 'checked' : ''; ?>> Top Sale
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; margin-bottom:0; cursor:pointer;">
                        <input type="checkbox" name="free_delivery" style="width:auto; margin-bottom:0;" <?php echo ($edit_data['free_delivery'] ?? 0) == 1 ? 'checked' : ''; ?>> Free Delivery
                    </label>
                </div>

                <label>Description</label>
                <textarea name="<?php echo $edit_mode ? 'update_p_description' : 'p_description'; ?>" rows="4"><?php echo $edit_data['description'] ?? ''; ?></textarea>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label>Main Image</label>
                        <input type="file" name="<?php echo $edit_mode ? 'update_p_image' : 'p_image'; ?>" <?php echo $edit_mode ? '' : 'required'; ?>>
                    </div>
                    <div>
                        <label>Extra Images (Hold Ctrl to select multiple)</label>
                        <input type="file" name="p_extra_images[]" multiple>
                    </div>
                </div>

                <button type="submit" name="<?php echo $edit_mode ? 'update_product' : 'add_product'; ?>" style="font-weight:600; font-size:1.1rem; width:100%; margin-top:10px;">
                    <?php echo $edit_mode ? 'Update Product' : 'Add Product'; ?>
                </button>
                <?php if($edit_mode): ?>
                    <a href="admin.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>Existing Products</h2>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $select_products = execute_query($conn, "SELECT * FROM products ORDER BY id DESC");
                    while($row = mysqli_fetch_assoc($select_products)):
                    ?>
                        <tr>
                            <td><img src="uploaded_img/<?php echo $row['image']; ?>" height="50"></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td>RS <?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo $row['stock']; ?></td>
                            <td class="action-btns">
                                <a href="?edit=<?php echo $row['id']; ?>"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
