<?php include('../config/conexion.php'); ?>
<?php include_once("../components/header.php"); ?>

<?php
$sql = "SELECT 
products.id,
products.title,
products.description,
products.price,
product_images.image_url

FROM products
LEFT JOIN product_images 
ON products.id = product_images.product_id";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">


    <link rel="stylesheet" href="../output.css">
</head>

<body>

    <div class="px-6 py-8  w-4/5 mx-auto mt-10">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">All Products</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-5">

            <?php while ($row = mysqli_fetch_assoc($result)):

                $image = $row['image_url']
                    ? "./productos/uploads/" . $row['image_url']
                    : 'https://via.placeholder.com/300x200';
            ?>

                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow duration-200 relative group cursor-pointer">

                    <!-- Image -->
                    <div class="h-48 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <img src="<?= htmlspecialchars($image) ?>"
                            alt="<?= htmlspecialchars($row['title']) ?>"
                            class="h-36 object-contain transition-transform duration-300 group-hover:scale-105">
                    </div>

                    <!-- Info -->
                    <div class="p-3.5">

                        <h3 class="text-sm font-medium text-gray-800 leading-snug mb-2">
                            <?= htmlspecialchars($row['title']) ?>
                        </h3>

                        <!-- Stars -->


                        <!-- Price -->
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-lg font-bold text-gray-900">
                                $<?= number_format($row['price']) ?>
                            </span>
                            <button>

                            </button>
                        </div>

                    </div>
                </div>

            <?php endwhile; ?>

        </div>
    </div>

</body>

</html>