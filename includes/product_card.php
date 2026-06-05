<?php
if (!isset($product)) return;
$img = !empty($product['image_url']) ? "/whynot/uploads/" . htmlspecialchars($product['image_url']) : "/whynot/uploads/placeholder.jpg";
?>
<div class="col-md-6 col-lg-4 col-xl-3 mb-4">
    <div class="card vd-product-card h-100 shadow-sm">
        <a href="/whynot/product.php?id=<?= (int)$product['product_id'] ?>" class="text-decoration-none">
            <img src="<?= $img ?>" class="card-img-top vd-product-img" alt="<?= htmlspecialchars($product['prod_title']) ?>">
        </a>
        <div class="card-body d-flex flex-column">
            <h6 class="card-title text-truncate">
                <a href="/whynot/product.php?id=<?= (int)$product['product_id'] ?>" class="text-dark text-decoration-none">
                    <?= htmlspecialchars($product['prod_title']) ?>
                </a>
            </h6>
            <p class="text-muted small mb-2">
                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($product['location_user'] ?? 'SA') ?>
            </p>
            <p class="vd-price mb-2">R<?= number_format((float)$product['price'], 2) ?></p>
            <span class="badge vd-badge-condition mb-2"><?= htmlspecialchars($product['condition_status']) ?></span>
            <a href="/whynot/product.php?id=<?= (int)$product['product_id'] ?>" class="btn btn-sm btn-outline-primary mt-auto">View</a>
        </div>
    </div>
</div>