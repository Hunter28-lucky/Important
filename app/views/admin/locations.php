<div class="locations-container">
    <?php if (empty($grouped_locations)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="fa-solid fa-location-dot"></i>
                    <h3>No visitor locations captured yet</h3>
                    <p class="text-muted">Insert a "Location Capture" block into one of your templates and publish it. When visitors grant permissions or trigger the block, their coordinates will appear here, grouped by link.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($grouped_locations as $slug => $group): ?>
            <div class="card mb-6">
                <div class="card-header">
                    <div>
                        <h3 class="mb-1"><?= htmlspecialchars($group['title']) ?></h3>
                        <a href="<?= BASE_URL ?>view/<?= htmlspecialchars($slug) ?>" target="_blank" class="slug-code" style="text-decoration: none;">
                            <i class="fa-solid fa-link"></i> /view/<?= htmlspecialchars($slug) ?>
                        </a>
                    </div>
                    <span class="badge badge-indigo"><?= count($group['items']) ?> Location Logs</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Status / GPS Coordinates</th>
                                    <th>Accuracy</th>
                                    <th>Visitor IP</th>
                                    <th>Captured At</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group['items'] as $item): 
                                    $hasGps = ($item['latitude'] !== null && $item['longitude'] !== null);
                                ?>
                                    <tr>
                                        <td class="font-semibold">
                                            <?php if ($hasGps): ?>
                                                <span style="color: var(--success);"><i class="fa-solid fa-circle-check"></i> Allowed GPS</span>
                                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; font-family: monospace;">
                                                    <?= number_format($item['latitude'], 6) ?>, <?= number_format($item['longitude'], 6) ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--danger);"><i class="fa-solid fa-circle-xmark"></i> Denied / IP Only</span>
                                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                                    GPS Coordinates Unavailable
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hasGps && $item['accuracy'] !== null): ?>
                                                <span class="badge badge-indigo"><?= number_format($item['accuracy'], 1) ?> meters</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="slug-code"><i class="fa-solid fa-network-wired"></i> <?= htmlspecialchars($item['visitor_ip'] ?? 'Unknown') ?></span>
                                        </td>
                                        <td class="text-muted">
                                            <i class="fa-solid fa-clock"></i> <?= date('M d, Y - H:i:s', strtotime($item['created_at'])) ?>
                                        </td>
                                        <td class="text-right">
                                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                                                <?php if ($hasGps): ?>
                                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $item['latitude'] ?>,<?= $item['longitude'] ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-secondary" 
                                                       style="padding: 0.35rem 0.6rem; font-size: 0.75rem;">
                                                        <i class="fa-solid fa-map-location-dot" style="color: var(--primary);"></i> Map View
                                                    </a>
                                                <?php endif; ?>
                                                <form action="<?= BASE_URL ?>admin/locations/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this location log permanently?');" style="display: inline; margin: 0;">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                    <input type="hidden" name="location_id" value="<?= $item['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" style="padding: 0.35rem 0.6rem; font-size: 0.75rem;">
                                                        <i class="fa-solid fa-trash-can"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
