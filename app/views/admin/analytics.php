<div class="analytics-container">
    <!-- Stat Counter Cards -->
    <div class="stats-row mb-4">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-eye"></i></div>
            <div class="stat-content">
                <span class="stat-label">Total Views</span>
                <span class="stat-number"><?= $stats['total_views'] ?></span>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-content">
                <span class="stat-label">Unique Visitors</span>
                <span class="stat-number"><?= $stats['unique_visitors'] ?></span>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-arrow-pointer"></i></div>
            <div class="stat-content">
                <span class="stat-label">Total Link Clicks</span>
                <span class="stat-number"><?= $stats['total_clicks'] ?></span>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fa-solid fa-percent"></i></div>
            <div class="stat-content">
                <span class="stat-label">Average CTR</span>
                <span class="stat-number">
                    <?= $stats['total_views'] > 0 ? round(($stats['total_clicks'] / $stats['total_views']) * 100, 1) : 0 ?>%
                </span>
            </div>
        </div>
    </div>

    <!-- 30 Days Trend Line Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Traffic History</h3>
            <span class="badge badge-indigo">Last 30 Days</span>
        </div>
        <div class="card-body">
            <canvas id="trafficTrendChart" style="max-height: 320px; width: 100%;"></canvas>
        </div>
    </div>

    <!-- Details splits -->
    <div class="split-row">
        <!-- Popular List -->
        <div class="card col-6">
            <div class="card-header">
                <h3>Template Performance Leaderboard</h3>
            </div>
            <div class="card-body">
                <?php if (empty($popular_templates)): ?>
                    <p class="text-muted">No logs recorded.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Template Name</th>
                                    <th>Views</th>
                                    <th>Clicks</th>
                                    <th>Conversion (CTR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_templates as $tmpl): 
                                    $ctr = $tmpl['views_count'] > 0 ? round(($tmpl['clicks_count'] / $tmpl['views_count']) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>admin/templates/edit?id=<?= $tmpl['id'] ?>" class="item-link">
                                                <strong><?= htmlspecialchars($tmpl['title']) ?></strong>
                                            </a>
                                            <div class="text-xs text-muted">/view/<?= htmlspecialchars($tmpl['slug']) ?></div>
                                        </td>
                                        <td class="font-semibold"><?= $tmpl['views_count'] ?></td>
                                        <td class="font-semibold"><?= $tmpl['clicks_count'] ?></td>
                                        <td>
                                            <div class="ctr-progress">
                                                <span class="ctr-text"><?= $ctr ?>%</span>
                                                <div class="progress-bar-bg">
                                                    <div class="progress-bar-fill" style="width: <?= min($ctr, 100) ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detail log stream -->
        <div class="card col-6">
            <div class="card-header">
                <h3>Viewer Event Log</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recent_views)): ?>
                    <p class="text-muted">No traffic recorded yet.</p>
                <?php else: ?>
                    <div class="log-stream-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Template</th>
                                    <th>IP / Agent</th>
                                    <th>Referrer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_views as $view): ?>
                                    <tr class="log-row">
                                        <td class="text-xs text-muted"><?= date('H:i:s M d', strtotime($view['viewed_at'])) ?></td>
                                        <td class="font-semibold"><?= htmlspecialchars($view['title']) ?></td>
                                        <td>
                                            <div class="text-xs"><code><?= htmlspecialchars($view['visitor_ip']) ?></code></div>
                                        </td>
                                        <td class="text-xs text-muted" title="<?= htmlspecialchars($view['referrer'] ?? 'Direct') ?>">
                                            <?= htmlspecialchars(!empty($view['referrer']) ? parse_url($view['referrer'], PHP_URL_HOST) : 'Direct/Search') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trafficTrendChart').getContext('2d');
        const rawHistory = <?= json_encode($views_history) ?>;
        
        const labels = [];
        const dataValues = [];
        const historyMap = {};
        
        rawHistory.forEach(item => {
            historyMap[item.view_date] = parseInt(item.view_count);
        });

        // Loop over the past 30 days
        for (let i = 29; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            const dateStr = d.toISOString().split('T')[0];
            const displayStr = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
            
            labels.push(displayStr);
            dataValues.push(historyMap[dateStr] || 0);
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Page Views',
                    data: dataValues,
                    borderColor: '#818cf8',
                    backgroundColor: 'rgba(129, 140, 248, 0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { family: 'Outfit', size: 10 } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8', font: { family: 'Outfit', size: 10 }, stepSize: 1, precision: 0 },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
