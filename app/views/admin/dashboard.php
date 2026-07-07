<div class="dashboard-grid">
    <!-- 1. Stats Counter Row -->
    <div class="stats-row">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-file-invoice"></i></div>
            <div class="stat-content">
                <span class="stat-label">Total Templates</span>
                <span class="stat-number"><?= $stats['total_templates'] ?></span>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fa-solid fa-eye"></i></div>
            <div class="stat-content">
                <span class="stat-label">Total Views</span>
                <span class="stat-number"><?= $stats['total_views'] ?></span>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-arrow-pointer"></i></div>
            <div class="stat-content">
                <span class="stat-label">Link Clicks</span>
                <span class="stat-number"><?= $stats['total_clicks'] ?></span>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-content">
                <span class="stat-label">Unique Visitors</span>
                <span class="stat-number"><?= $stats['unique_visitors'] ?></span>
            </div>
        </div>
    </div>

    <!-- 2. Chart Area -->
    <div class="card chart-card">
        <div class="card-header">
            <h3>Views Over Time</h3>
            <span class="badge badge-indigo">Last 7 Days</span>
        </div>
        <div class="card-body">
            <canvas id="viewsChart" style="max-height: 280px; width: 100%;"></canvas>
        </div>
    </div>

    <!-- 3. Dynamic Telemetry Details (Two Columns) -->
    <div class="split-row">
        <!-- Left: Popular templates -->
        <div class="card col-7">
            <div class="card-header">
                <h3>Popular Templates</h3>
                <a href="<?= BASE_URL ?>admin/templates" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($popular_templates)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-folder-open"></i>
                        <p>No templates created yet.</p>
                        <a href="<?= BASE_URL ?>admin/templates/create" class="btn btn-primary btn-sm">Create One</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Template Title</th>
                                    <th>Slug</th>
                                    <th>Status</th>
                                    <th class="text-right">Views</th>
                                    <th class="text-right">Clicks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_templates as $tmpl): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>admin/templates/edit?id=<?= $tmpl['id'] ?>" class="item-link">
                                                <strong><?= htmlspecialchars($tmpl['title']) ?></strong>
                                            </a>
                                        </td>
                                        <td><code class="slug-code">/view/<?= htmlspecialchars($tmpl['slug']) ?></code></td>
                                        <td>
                                            <span class="badge <?= $tmpl['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                                <?= ucfirst($tmpl['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-right font-semibold"><?= $tmpl['views_count'] ?></td>
                                        <td class="text-right font-semibold"><?= $tmpl['clicks_count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Recent Activity -->
        <div class="card col-5">
            <div class="card-header">
                <h3>Recent Viewer Activity</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recent_views)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-chart-line"></i>
                        <p>No activity tracked yet.</p>
                    </div>
                <?php else: ?>
                    <ul class="activity-list">
                        <?php foreach ($recent_views as $view): ?>
                            <li class="activity-item">
                                <div class="activity-icon"><i class="fa-solid fa-chevron-right"></i></div>
                                <div class="activity-details">
                                    <span class="activity-text">
                                        Template <a href="<?= BASE_URL ?>view/<?= htmlspecialchars($view['slug']) ?>" target="_blank" class="activity-link">
                                            <strong><?= htmlspecialchars($view['title']) ?></strong>
                                        </a> was viewed.
                                    </span>
                                    <div class="activity-meta">
                                        <span><i class="fa-solid fa-network-wired"></i> <?= htmlspecialchars($view['visitor_ip']) ?></span>
                                        <span><i class="fa-solid fa-clock"></i> <?= date('M d, H:i', strtotime($view['viewed_at'])) ?></span>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js CDN for interactive visual analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('viewsChart').getContext('2d');
        
        // Prepare data from PHP
        const rawHistory = <?= json_encode($views_history) ?>;
        
        // Format dates and values
        const labels = [];
        const dataValues = [];
        
        // Create last 7 days map
        const historyMap = {};
        rawHistory.forEach(item => {
            historyMap[item.view_date] = parseInt(item.view_count);
        });
        
        for (let i = 6; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            const dateStr = d.toISOString().split('T')[0];
            const displayStr = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
            
            labels.push(displayStr);
            dataValues.push(historyMap[dateStr] || 0);
        }

        // Initialize Chart.js
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Page Views',
                    data: dataValues,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#818cf8',
                    pointBorderColor: '#ffffff',
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { family: 'Outfit' } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { 
                            color: '#94a3b8', 
                            font: { family: 'Outfit' },
                            stepSize: 1,
                            precision: 0 
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
