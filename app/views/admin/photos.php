<div class="photos-container">
    <?php if (empty($grouped_photos)): ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="fa-solid fa-camera"></i>
                    <h3>No visitor photos captured yet</h3>
                    <p class="text-muted">Insert a "Webcam Capture" block into one of your templates and publish it. When visitors submit snapshots, they will appear here, grouped by link.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($grouped_photos as $slug => $group): ?>
            <div class="card mb-6">
                <div class="card-header">
                    <div>
                        <h3 class="mb-1"><?= htmlspecialchars($group['title']) ?></h3>
                        <a href="<?= BASE_URL ?>view/<?= htmlspecialchars($slug) ?>" target="_blank" class="slug-code" style="text-decoration: none;">
                            <i class="fa-solid fa-link"></i> /view/<?= htmlspecialchars($slug) ?>
                        </a>
                    </div>
                    <span class="badge badge-indigo"><?= count($group['items']) ?> Snapshots</span>
                </div>
                <div class="card-body">
                    <div class="media-grid">
                        <?php foreach ($group['items'] as $item): 
                            $photoUrl = BASE_URL . $item['photo_path'];
                        ?>
                            <div class="media-item-card">
                                <div class="media-preview" style="height: 140px;">
                                    <a href="<?= $photoUrl ?>" target="_blank" title="View Fullscreen">
                                        <img src="<?= $photoUrl ?>" alt="Visitor Selfie" style="transform: scaleX(-1);">
                                    </a>
                                </div>
                                <div class="media-details">
                                    <span class="media-name" style="font-size: 0.8rem;">
                                        <i class="fa-solid fa-network-wired"></i> <?= htmlspecialchars($item['visitor_ip'] ?? 'Unknown') ?>
                                    </span>
                                    <span class="media-meta" style="font-size: 0.7rem;">
                                        <i class="fa-solid fa-clock"></i> <?= date('M d, Y - H:i', strtotime($item['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="media-actions" style="padding: 0.5rem; justify-content: flex-end;">
                                    <form action="<?= BASE_URL ?>admin/photos/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this visitor snapshot permanently?');" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="photo_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="padding: 0.35rem 0.6rem; font-size: 0.75rem;">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
