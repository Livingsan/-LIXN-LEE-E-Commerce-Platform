<?php
@include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Smart Search</title>
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<section class="top-txt">
   <h1>LIXN LEE - Smart Search</h1>
</section>

<nav class="navbar">
   <div class="logo"><h1>LL</h1></div>
   <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="products.php">Shop</a></li>
   </ul>
</nav>

<div class="container">
   <h2>Search Products</h2>
   <form action="process_search.php" method="get">
      <input type="text" name="query" placeholder="e.g. I need a blue shirt in XL size for my brother" required style="width: 100%; padding: 10px; font-size: 1rem;">
      <button type="submit" style="padding: 10px 20px; margin-top: 10px;">Search</button>
   </form>
</div>

</body>
</html>
