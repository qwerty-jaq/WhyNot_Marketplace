<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/authentication.php';

// Fetch the 8 most recent active products to feature on the homepage
$featured = $pdo->query(
    "SELECT p.*, c.cat_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.category_id
     WHERE p.prod_status = 'active'
     ORDER BY p.created_at DESC
     LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories for the quick-link section
$cats = $pdo->query("SELECT * FROM categories ORDER BY CASE WHEN cat_name = 'Other' THEN 1 ELSE 0 END, cat_name")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'WhyNot? - South Africa\'s C2C Marketplace';
include __DIR__ . '/includes/header.php';
?>

<!-- =========== HERO SECTION =========== -->
<section class="vd-hero py-5 text-center text-white">
    <div class="container py-4">
        <h1 class="display-4 fw-bold mb-3">Buy/Sell - WhyNot?</h1>
        <p class="lead mb-4">
            South Africa's friendliest marketplace built for everyone.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="/whynot/browse.php" class="btn btn-light btn-lg">
                <i class="bi bi-search"></i> Buy Now
            </a>
            <?php if (is_logged_in()): ?>
                <a href="/whynot/sell.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-plus-circle"></i> Sell Now
                </a>
            <?php else: ?>
                <a href="/whynot/register.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-person-plus"></i> Get Started
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- =========== FEATURED PRODUCTS =========== -->
<section class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Latest Listings</h2>
        <a href="/whynot/browse.php" class="btn btn-outline-primary">
            View All <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <?php if (empty($featured)): ?>
        <div class="alert alert-info">
            No products listed yet. <a href="/whynot/register.php" class="alert-link">Sign up</a>
            and be the first to sell!
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($featured as $product): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- =========== CATEGORIES =========== -->
<section class="container py-5">
    <h2 class="mb-4 text-center">Shop by Category</h2>
    <div class="row g-3">
        <?php
        $iconMap = [
            'Electronics' => 'bi-phone',
            'Clothing' => 'bi-handbag',
            'Home & Garden' => 'bi-house',
            'Sports' => 'bi-bicycle',
            'Books' => 'bi-book',
            'Vehicles' => 'bi-car-front',
            'Furniture' => 'bi-house-door',
            'Beauty & Health' => 'bi-heart-pulse',
            'Baby & Kids' => 'bi-emoji-smile',
            'Collectibles' => 'bi-gem',
            'Music & Instruments' => 'bi-music-note',
            'Business & Industrial' => 'bi-building',
            'Toys & Games' => 'bi-controller',
            'Tools & DIY' => 'bi-tools',
            'Art & Crafts' => 'bi-palette',
            'Food & Beverages' => 'bi-cup-straw',
            'Travel & Luggage' => 'bi-suitcase',
            'Office & Stationery' => 'bi-journal',
            'Photography' => 'bi-camera',
            'Other' => 'bi-box-seam',
            // Add more category-icon mappings as needed
        ];
        ?>
        <?php foreach ($cats as $c): ?>
            <div class="col-6 col-md-3">
                <a href="/whynot/browse.php?cat=<?= (int)$c['category_id'] ?>" class="text-decoration-none">
                    <div class="card text-center vd-category-card h-100 shadow-sm">
                        <div class="card-body">
                            <i class="bi <?= $iconMap[$c['cat_name']] ?? 'bi-tag-fill' ?> fs-2 text-primary mb-2"></i>
                            <h6 class="card-title mb-0"><?= htmlspecialchars($c['cat_name']) ?></h6>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- =========== WHY WHYNOT? =========== -->
<section class="vd-why py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">About WhyNot?</h2>
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <i class="bi bi-shield-check fs-1 text-primary mb-3"></i>
                <h5>Verified Sellers</h5>
                <p class="text-muted small">Every seller goes through ID verification to build trust between buyers and sellers.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="bi bi-geo-alt fs-1 text-primary mb-3"></i>
                <h5>Locally Focused</h5>
                <p class="text-muted small">Designed for South African informal traders — low data usage, township-friendly delivery options.</p>
            </div>
            <div class="col-md-4 mb-4">
                <i class="bi bi-chat-dots fs-1 text-primary mb-3"></i>
                <h5>Direct Communication</h5>
                <p class="text-muted small">Built-in messaging lets buyers and sellers negotiate and arrange pick-ups directly.</p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
