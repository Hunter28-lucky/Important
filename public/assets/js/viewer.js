/**
 * TemplateLink Builder - Client-side Viewer Interactivity & Telemetry
 */

const TemplateViewer = {
    templateId: null,
    baseUrl: '',
    
    init: function(config) {
        this.templateId = config.templateId;
        this.baseUrl = config.baseUrl;
        
        this.initAccordions();
        this.initCarousels();
        this.initClickTracking();
        this.initWebcam();
    },
    
    // 1. Accordion Toggle Logic
    initAccordions: function() {
        const headers = document.querySelectorAll('.tmpl-accordion-header');
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const item = this.parentElement;
                
                // Toggle current item
                item.classList.toggle('active');
                
                // Close siblings (Optional, standard Accordion behavior)
                const siblings = item.parentElement.querySelectorAll('.tmpl-accordion-item');
                siblings.forEach(sib => {
                    if (sib !== item) {
                        sib.classList.remove('active');
                    }
                });
            });
        });
    },
    
    // 2. Carousel Slider Logic
    initCarousels: function() {
        const carousels = document.querySelectorAll('.tmpl-carousel');
        carousels.forEach(carousel => {
            const track = carousel.querySelector('.tmpl-carousel-track');
            const slides = carousel.querySelectorAll('.tmpl-carousel-slide');
            const nextBtn = carousel.querySelector('.tmpl-carousel-btn.next');
            const prevBtn = carousel.querySelector('.tmpl-carousel-btn.prev');
            
            if (slides.length <= 1) {
                if (nextBtn) nextBtn.style.display = 'none';
                if (prevBtn) prevBtn.style.display = 'none';
                return;
            }
            
            let currentIndex = 0;
            const maxIndex = slides.length - 1;
            
            const updateSlidePosition = () => {
                track.style.transform = `translateX(-${currentIndex * 100}%)`;
            };
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    if (currentIndex < maxIndex) {
                        currentIndex++;
                    } else {
                        currentIndex = 0; // Wrap around
                    }
                    updateSlidePosition();
                });
            }
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    if (currentIndex > 0) {
                        currentIndex--;
                    } else {
                        currentIndex = maxIndex; // Wrap around to end
                    }
                    updateSlidePosition();
                });
            }
            
            // Optional: Auto slide every 5 seconds
            let interval = setInterval(() => {
                if (nextBtn) nextBtn.click();
            }, 5000);
            
            // Pause auto slide on hover
            carousel.addEventListener('mouseenter', () => clearInterval(interval));
            carousel.addEventListener('mouseleave', () => {
                interval = setInterval(() => {
                    if (nextBtn) nextBtn.click();
                }, 5000);
            });
        });
    },
    
    // 3. Click Telemetry Tracking
    initClickTracking: function() {
        const self = this;
        document.body.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;
            
            const href = link.getAttribute('href');
            // Do not track anchors, empty, or javascript links
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            
            // Send click log beacon before leaving
            if (navigator.sendBeacon) {
                const formData = new FormData();
                formData.append('template_id', self.templateId);
                formData.append('link_url', href);
                navigator.sendBeacon(self.baseUrl + 'api/track-click', formData);
            } else {
                // Fallback for older browsers
                fetch(self.baseUrl + 'api/track-click', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `template_id=${self.templateId}&link_url=${encodeURIComponent(href)}`,
                    keepalive: true
                });
            }
        });
    },

    // 4. User-Authorized Webcam Capture & Submission
    initWebcam: function() {
        const self = this;
        const btnStart = document.getElementById('btnStartWebcam');
        if (!btnStart) return; // Webcam block not present

        const viewerBox = document.getElementById('webcamViewerBox');
        const video = document.getElementById('webcamVideo');
        const canvas = document.getElementById('webcamCanvas');
        const preview = document.getElementById('capturedPhotoPreview');
        const statusMsg = document.getElementById('webcamStatusMessage');
        
        const btnCapture = document.getElementById('btnCaptureFrame');
        const btnSubmit = document.getElementById('btnSubmitPhoto');
        const btnRetake = document.getElementById('btnRetakePhoto');
        
        let stream = null;
        let base64Image = null;
        
        // Trigger webcam stream
        btnStart.addEventListener('click', async function() {
            statusMsg.textContent = 'Requesting camera access...';
            statusMsg.style.color = '#6366f1';
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: 640, height: 480, facingMode: 'user' }, 
                    audio: false 
                });
                
                video.srcObject = stream;
                viewerBox.style.display = 'block';
                video.style.display = 'block';
                
                btnStart.style.display = 'none';
                btnCapture.style.display = 'inline-flex';
                statusMsg.textContent = 'Webcam active. Click Capture Snapshot when ready.';
                statusMsg.style.color = '#10b981';
            } catch (err) {
                console.error('Webcam permission error:', err);
                statusMsg.textContent = 'Webcam access denied. Please grant camera permissions to verify access.';
                statusMsg.style.color = '#ef4444';
            }
        });
        
        // Take Snapshot
        btnCapture.addEventListener('click', function() {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw current video stream frame to canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Parse to base64 jpg data URL
            base64Image = canvas.toDataURL('image/jpeg', 0.85);
            
            // Show preview & hide video
            preview.src = base64Image;
            preview.style.display = 'block';
            video.style.display = 'none';
            
            btnCapture.style.display = 'none';
            btnSubmit.style.display = 'inline-flex';
            btnRetake.style.display = 'inline-flex';
            
            statusMsg.textContent = 'Selfie snapped! Submit photo to verify.';
            statusMsg.style.color = '#10b981';
        });
        
        // Retake Snapshot
        btnRetake.addEventListener('click', function() {
            preview.style.display = 'none';
            video.style.display = 'block';
            
            btnSubmit.style.display = 'none';
            btnRetake.style.display = 'none';
            btnCapture.style.display = 'inline-flex';
            
            base64Image = null;
            statusMsg.textContent = 'Position your camera and capture again.';
            statusMsg.style.color = '#10b981';
        });
        
        // Submit Photo Telemetry
        btnSubmit.addEventListener('click', function() {
            if (!base64Image) return;
            
            btnSubmit.setAttribute('disabled', 'true');
            btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
            statusMsg.textContent = 'Saving snapshot...';
            statusMsg.style.color = '#6366f1';
            
            const formData = new FormData();
            formData.append('template_id', self.templateId);
            formData.append('photo_data', base64Image);
            
            fetch(self.baseUrl + 'api/submit-photo', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) return res.json().then(e => { throw e; });
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    statusMsg.textContent = 'Authorized! Access granted.';
                    statusMsg.style.color = '#10b981';
                    
                    // Stop camera stream tracks
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                    
                    setTimeout(() => {
                        viewerBox.style.display = 'none';
                        btnSubmit.style.display = 'none';
                        btnRetake.style.display = 'none';
                        
                        const card = btnStart.closest('.tmpl-webcam-card');
                        if (card) {
                            card.innerHTML = `
                                <div style="color: #10b981; font-weight: 600; padding: 1rem 0;">
                                    <i class="fa-solid fa-circle-check" style="font-size: 3rem; margin-bottom: 0.5rem;"></i>
                                    <h3>Identity Verified</h3>
                                    <p style="font-weight: normal; color: #64748b; font-size: 0.9rem; margin-top: 0.5rem;">Verification successful. Access token registered.</p>
                                </div>
                            `;
                        }
                    }, 1200);
                } else {
                    throw data;
                }
            })
            .catch(err => {
                console.error(err);
                statusMsg.textContent = err.error || 'Network error. Capture failed to submit.';
                statusMsg.style.color = '#ef4444';
                btnSubmit.removeAttribute('disabled');
                btnSubmit.innerHTML = '<i class="fa-solid fa-check"></i> Submit Verification';
            });
        });
    }
};

window.TemplateViewer = TemplateViewer;
