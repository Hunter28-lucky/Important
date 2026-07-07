<div class="media-container">
    <!-- Upload Area Card -->
    <div class="card upload-card">
        <div class="card-header">
            <h3>Upload New File</h3>
            <span class="text-muted text-sm">Max upload size: 10MB</span>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/media/upload" method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="dropzone" id="dropzone">
                    <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
                    <p class="dropzone-text">Drag and drop file here, or click to browse</p>
                    <input type="file" name="media_file" id="media_file" required style="display: none;">
                    <div id="file-name-preview" class="file-name-preview"></div>
                </div>
                
                <div class="upload-actions">
                    <button type="submit" class="btn btn-primary" id="btnSubmitUpload" disabled>
                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Upload File
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Media Library Grid -->
    <div class="card media-grid-card">
        <div class="card-header">
            <h3>Uploaded Files</h3>
        </div>
        <div class="card-body">
            <?php if (empty($assets)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-photo-film"></i>
                    <p>No media files uploaded yet.</p>
                </div>
            <?php else: ?>
                <div class="media-grid">
                    <?php foreach ($assets as $asset): 
                        $isImage = strpos($asset['file_type'], 'image/') === 0;
                        $isPdf = $asset['file_type'] === 'application/pdf';
                        $isVideo = strpos($asset['file_type'], 'video/') === 0;
                        $fullUrl = BASE_URL . $asset['file_path'];
                        $fileSizeFriendly = round($asset['file_size'] / 1024, 1);
                        $fileSizeUnit = 'KB';
                        if ($fileSizeFriendly > 1024) {
                            $fileSizeFriendly = round($fileSizeFriendly / 1024, 1);
                            $fileSizeUnit = 'MB';
                        }
                    ?>
                        <div class="media-item-card">
                            <div class="media-preview">
                                <?php if ($isImage): ?>
                                    <img src="<?= $fullUrl ?>" alt="<?= htmlspecialchars($asset['file_name']) ?>" loading="lazy">
                                <?php elseif ($isPdf): ?>
                                    <div class="file-icon pdf"><i class="fa-solid fa-file-pdf"></i></div>
                                <?php elseif ($isVideo): ?>
                                    <div class="file-icon video"><i class="fa-solid fa-file-video"></i></div>
                                <?php else: ?>
                                    <div class="file-icon doc"><i class="fa-solid fa-file"></i></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="media-details">
                                <span class="media-name" title="<?= htmlspecialchars($asset['file_name']) ?>">
                                    <?= htmlspecialchars($asset['file_name']) ?>
                                </span>
                                <span class="media-meta">
                                    <?= strtoupper(explode('/', $asset['file_type'])[1] ?? 'FILE') ?> &bull; <?= $fileSizeFriendly . ' ' . $fileSizeUnit ?>
                                </span>
                            </div>
                            
                            <div class="media-actions">
                                <button type="button" class="btn btn-sm btn-secondary btn-copy" data-url="<?= $fullUrl ?>" title="Copy Link to Clipboard">
                                    <i class="fa-solid fa-copy"></i> Copy Link
                                </button>
                                <form action="<?= BASE_URL ?>admin/media/delete" method="POST" class="delete-media-form" onsubmit="return confirm('Are you sure you want to delete this file permanently?');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="media_id" value="<?= $asset['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-delete-media" title="Delete File">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('media_file');
    const submitBtn = document.getElementById('btnSubmitUpload');
    const filePreview = document.getElementById('file-name-preview');

    // Make dropzone clickable
    dropzone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function() {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            filePreview.textContent = `Selected: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
            filePreview.style.display = 'block';
            submitBtn.removeAttribute('disabled');
        } else {
            filePreview.textContent = '';
            filePreview.style.display = 'none';
            submitBtn.setAttribute('disabled', 'true');
        }
    });

    // Drag over styling
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('dragover');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            // trigger change listener
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }
    });

    // Copy Link functionality
    const copyButtons = document.querySelectorAll('.btn-copy');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            navigator.clipboard.writeText(url).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                this.classList.add('success');
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('success');
                }, 2000);
            }).catch(err => {
                alert('Could not copy text: ', err);
            });
        });
    });
});
</script>
