<?php
require_once __DIR__ . '/../../models/Plugin.php';

$plugins = Plugin::all();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plugin Marketplace - Chat App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .plugin-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .plugin-card:hover {
            transform: translateY(-5px);
        }
        .plugin-icon {
            width: 64px;
            height: 64px;
            object-fit: cover;
        }
        .rating {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>Plugin Marketplace</h1>
                <p class="text-muted">Discover and install plugins to enhance your chat experience</p>
            </div>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <a href="/admin/plugin_developer_guide.php" class="btn btn-primary">
                <i class="fas fa-code"></i> Developer Guide
            </a>
            <?php endif; ?>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Filter Plugins</h5>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <option value="formatting">Formatting</option>
                                <option value="integration">Integration</option>
                                <option value="enhancement">Enhancement</option>
                                <option value="utility">Utility</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select class="form-select" id="sortBy">
                                <option value="popular">Most Popular</option>
                                <option value="rating">Highest Rated</option>
                                <option value="newest">Newest</option>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="officialOnly">
                            <label class="form-check-label">
                                Official Plugins Only
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="pluginsList">
                    <?php foreach ($plugins as $plugin): ?>
                    <div class="col">
                        <div class="card plugin-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo htmlspecialchars($plugin['icon_url']); ?>" alt="Plugin icon" class="plugin-icon me-3">
                                    <div>
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($plugin['name']); ?></h5>
                                        <p class="text-muted mb-0">v<?php echo htmlspecialchars($plugin['version']); ?></p>
                                    </div>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars($plugin['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="rating">
                                        <?php
                                        $rating = floatval($plugin['rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - 0.5 <= $rating) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                        <span class="ms-1">(<?php echo $plugin['total_reviews']; ?>)</span>
                                    </div>
                                    <span class="badge bg-<?php echo $plugin['is_official'] ? 'primary' : 'secondary'; ?>">
                                        <?php echo $plugin['is_official'] ? 'Official' : 'Community'; ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-download"></i> <?php echo number_format($plugin['downloads']); ?>
                                    </small>
                                    <?php if ($plugin['price'] > 0): ?>
                                    <span class="badge bg-success">$<?php echo number_format($plugin['price'], 2); ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-info">Free</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-grid gap-2">
                                    <?php if (isset($_SESSION['installed_plugins']) && in_array($plugin['id'], $_SESSION['installed_plugins'])): ?>
                                    <button class="btn btn-outline-danger uninstall-plugin" data-plugin-id="<?php echo $plugin['id']; ?>">
                                        <i class="fas fa-trash"></i> Uninstall
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-primary install-plugin" data-plugin-id="<?php echo $plugin['id']; ?>">
                                        <i class="fas fa-download"></i> Install
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Install plugin
            document.querySelectorAll('.install-plugin').forEach(btn => {
                btn.addEventListener('click', function() {
                    const pluginId = this.dataset.pluginId;
                    fetch('/api/marketplace/plugins.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'install',
                            plugin_id: pluginId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error installing plugin: ' + data.message);
                        }
                    });
                });
            });

            // Uninstall plugin
            document.querySelectorAll('.uninstall-plugin').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to uninstall this plugin?')) {
                        const pluginId = this.dataset.pluginId;
                        fetch('/api/marketplace/plugins.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'uninstall',
                                plugin_id: pluginId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Error uninstalling plugin: ' + data.message);
                            }
                        });
                    }
                });
            });

            // Filter functionality
            const categoryFilter = document.getElementById('categoryFilter');
            const sortBy = document.getElementById('sortBy');
            const officialOnly = document.getElementById('officialOnly');

            function applyFilters() {
                const category = categoryFilter.value;
                const sort = sortBy.value;
                const official = officialOnly.checked;

                fetch(`/api/marketplace/plugins.php?category=${category}&sort=${sort}&official=${official}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update plugins list
                            const pluginsList = document.getElementById('pluginsList');
                            // Implementation of updating the list with new data
                        }
                    });
            }

            categoryFilter.addEventListener('change', applyFilters);
            sortBy.addEventListener('change', applyFilters);
            officialOnly.addEventListener('change', applyFilters);
        });
    </script>
</body>
</html> 