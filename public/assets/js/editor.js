/**
 * TemplateLink Builder - Visual Editor Engine
 */

// Global Editor State
const Editor = {
    templateId: null,
    blocks: [],
    selectedBlockId: null,
    csrfToken: '',
    baseUrl: '',
    seoData: {},
    
    init: function(config) {
        this.templateId = config.templateId;
        this.blocks = config.initialBlocks || [];
        this.csrfToken = config.csrfToken;
        this.baseUrl = config.baseUrl;
        this.seoData = config.seoData || {};
        
        this.bindEvents();
        this.renderCanvas();
        this.initMediaPicker();
        this.bindSeoModal();
        
        // Auto-select first block if exists
        if (this.blocks.length > 0) {
            this.selectBlock(this.blocks[0].id);
        }
    },
    
    bindEvents: function() {
        const self = this;
        
        // Save Button
        document.getElementById('btnSaveTemplate').addEventListener('click', () => self.saveTemplate());
        
        // Viewport Switcher
        const viewButtons = document.querySelectorAll('.viewport-btn');
        const previewFrame = document.getElementById('previewCanvasContainer');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const view = this.getAttribute('data-view');
                previewFrame.className = 'canvas-container ' + view + '-view';
            });
        });
        
        // Add Block Toolbox click handlers
        const toolItems = document.querySelectorAll('.toolbox-item');
        toolItems.forEach(item => {
            item.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                self.addBlock(type);
            });
        });
        
        // Close inspector button
        document.getElementById('closeInspectorBtn').addEventListener('click', () => {
            self.selectedBlockId = null;
            self.renderCanvas();
            self.renderInspector();
        });

        // Delete Template Settings / Slug Inputs auto check
        const settingsInputs = ['tmplStatus', 'tmplCategory', 'tmplSlug', 'tmplTitle'];
        settingsInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', () => self.updateMetadataFromForm());
                if (el.tagName === 'INPUT') {
                    el.addEventListener('input', () => self.updateMetadataFromForm());
                }
            }
        });
    },

    updateMetadataFromForm: function() {
        const titleEl = document.getElementById('tmplTitle');
        const slugEl = document.getElementById('tmplSlug');
        const catEl = document.getElementById('tmplCategory');
        const statusEl = document.getElementById('tmplStatus');

        if (titleEl && titleEl.value) {
            document.title = "Editing: " + titleEl.value + " - TemplateLink Builder";
        }
    },
    
    // RENDER CANVAS
    renderCanvas: function() {
        const canvas = document.getElementById('editorCanvas');
        canvas.innerHTML = '';
        
        if (this.blocks.length === 0) {
            canvas.innerHTML = `
                <div class="canvas-empty-state">
                    <i class="fa-solid fa-square-plus"></i>
                    <h3>Your canvas is empty</h3>
                    <p>Click any item on the left panel to insert visual blocks into your document.</p>
                </div>
            `;
            return;
        }
        
        this.blocks.forEach((block, index) => {
            const blockEl = document.createElement('div');
            blockEl.className = 'editor-block-wrapper';
            if (this.selectedBlockId === block.id) {
                blockEl.classList.add('selected');
            }
            blockEl.setAttribute('data-id', block.id);
            
            // Block Controls Toolbar
            const toolbar = document.createElement('div');
            toolbar.className = 'block-toolbar';
            toolbar.innerHTML = `
                <span class="block-type-badge">${block.type.toUpperCase()}</span>
                <div class="toolbar-actions">
                    <button type="button" class="btn-tool btn-move-up" title="Move Up" ${index === 0 ? 'disabled' : ''}><i class="fa-solid fa-chevron-up"></i></button>
                    <button type="button" class="btn-tool btn-move-down" title="Move Down" ${index === this.blocks.length - 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-down"></i></button>
                    <button type="button" class="btn-tool btn-duplicate" title="Duplicate"><i class="fa-solid fa-copy"></i></button>
                    <button type="button" class="btn-tool btn-delete-block" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            `;
            
            // Block HTML preview
            const preview = document.createElement('div');
            preview.className = 'block-preview';
            preview.innerHTML = this.generateBlockHTML(block);
            
            blockEl.appendChild(toolbar);
            blockEl.appendChild(preview);
            canvas.appendChild(blockEl);
            
            // Inline events
            blockEl.addEventListener('click', (e) => {
                // Prevent selection if clicking toolbar buttons
                if (e.target.closest('.block-toolbar')) return;
                this.selectBlock(block.id);
            });
            
            // Bind toolbar functions
            toolbar.querySelector('.btn-move-up').addEventListener('click', (e) => { e.stopPropagation(); this.moveBlock(index, 'up'); });
            toolbar.querySelector('.btn-move-down').addEventListener('click', (e) => { e.stopPropagation(); this.moveBlock(index, 'down'); });
            toolbar.querySelector('.btn-duplicate').addEventListener('click', (e) => { e.stopPropagation(); this.duplicateBlock(block.id); });
            toolbar.querySelector('.btn-delete-block').addEventListener('click', (e) => { e.stopPropagation(); this.deleteBlock(block.id); });
            
            // Bind inline contenteditables if any
            const editableTexts = preview.querySelectorAll('[data-editable]');
            editableTexts.forEach(el => {
                el.setAttribute('contenteditable', 'true');
                el.addEventListener('focus', () => {
                    blockEl.classList.add('editing-text');
                });
                el.addEventListener('blur', function() {
                    blockEl.classList.remove('editing-text');
                    const field = this.getAttribute('data-editable');
                    self.updateBlockFieldInline(block.id, field, this.innerHTML);
                });
            });
        });
        
        const self = this;
    },
    
    // UPDATE INLINE FIELDS
    updateBlockFieldInline: function(blockId, fieldName, value) {
        const block = this.blocks.find(b => b.id === blockId);
        if (block) {
            if (fieldName.startsWith('card.')) {
                // Handle sub-card values
                const parts = fieldName.split('.');
                const cardIndex = parseInt(parts[1]);
                const cardField = parts[2];
                if (block.content.cards && block.content.cards[cardIndex]) {
                    block.content.cards[cardIndex][cardField] = value;
                }
            } else if (fieldName.startsWith('accordion.')) {
                const parts = fieldName.split('.');
                const idx = parseInt(parts[1]);
                const subField = parts[2];
                if (block.content.items && block.content.items[idx]) {
                    block.content.items[idx][subField] = value;
                }
            } else if (fieldName.startsWith('pricing.')) {
                const parts = fieldName.split('.');
                const idx = parseInt(parts[1]);
                const subField = parts[2];
                if (block.content.cards && block.content.cards[idx]) {
                    block.content.cards[idx][subField] = value;
                }
            } else if (fieldName.startsWith('timeline.')) {
                const parts = fieldName.split('.');
                const idx = parseInt(parts[1]);
                const subField = parts[2];
                if (block.content.items && block.content.items[idx]) {
                    block.content.items[idx][subField] = value;
                }
            } else if (fieldName.startsWith('progress.')) {
                const parts = fieldName.split('.');
                const idx = parseInt(parts[1]);
                const subField = parts[2];
                if (block.content.items && block.content.items[idx]) {
                    block.content.items[idx][subField] = value;
                }
            } else {
                block.content[fieldName] = value;
            }
            this.renderInspector(); // refresh inspector view to stay synced
        }
    },
    
    // SELECT BLOCK
    selectBlock: function(id) {
        this.selectedBlockId = id;
        
        // Highlight active card in DOM
        const wrappers = document.querySelectorAll('.editor-block-wrapper');
        wrappers.forEach(w => {
            if (w.getAttribute('data-id') === id) {
                w.classList.add('selected');
            } else {
                w.classList.remove('selected');
            }
        });
        
        this.renderInspector();
    },
    
    // RE-ORDER AND CRUD ACTIONS
    addBlock: function(type) {
        const id = 'block-' + Math.random().toString(36).substr(2, 9);
        const defaults = this.getDefaultBlockContent(type);
        
        const newBlock = {
            id: id,
            type: type,
            content: defaults
        };
        
        this.blocks.push(newBlock);
        this.renderCanvas();
        this.selectBlock(id);
        
        // Scroll container to bottom
        const canvas = document.getElementById('editorCanvas');
        canvas.scrollTop = canvas.scrollHeight;
    },
    
    moveBlock: function(index, direction) {
        if (direction === 'up' && index > 0) {
            const temp = this.blocks[index];
            this.blocks[index] = this.blocks[index - 1];
            this.blocks[index - 1] = temp;
        } else if (direction === 'down' && index < this.blocks.length - 1) {
            const temp = this.blocks[index];
            this.blocks[index] = this.blocks[index + 1];
            this.blocks[index + 1] = temp;
        }
        this.renderCanvas();
    },
    
    duplicateBlock: function(id) {
        const index = this.blocks.findIndex(b => b.id === id);
        if (index !== -1) {
            const original = this.blocks[index];
            const duplicate = JSON.parse(JSON.stringify(original));
            duplicate.id = 'block-' + Math.random().toString(36).substr(2, 9);
            this.blocks.splice(index + 1, 0, duplicate);
            this.renderCanvas();
            this.selectBlock(duplicate.id);
        }
    },
    
    deleteBlock: function(id) {
        this.blocks = this.blocks.filter(b => b.id !== id);
        if (this.selectedBlockId === id) {
            this.selectedBlockId = this.blocks.length > 0 ? this.blocks[0].id : null;
        }
        this.renderCanvas();
        this.renderInspector();
    },
    
    // HTML GENERATOR IN LIVE CANVAS (MIMICS CLIENT VIEWER BLOCKS)
    generateBlockHTML: function(block) {
        const c = block.content;
        const styleString = this.getStyleString(c);
        const customClasses = c.glassmorphism ? 'glassmorphism' : '';
        const shadowClass = c.shadow_modern ? 'shadow-modern' : '';
        const roundClass = c.rounded_lg ? 'rounded-lg' : '';

        switch (block.type) {
            case 'hero':
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container" style="text-align: ${c.text_align || 'center'};">
                            <h1 class="tmpl-hero-title" style="color: ${c.title_color || c.text_color}; font-size: ${c.title_size || '3rem'};" data-editable="title">${c.title || 'Hero Title'}</h1>
                            <p class="tmpl-hero-subtitle" style="color: ${c.text_color}; opacity: 0.9;" data-editable="subtitle">${c.subtitle || 'Sub-headline goes here.'}</p>
                            ${c.btn_text ? `<a class="tmpl-btn" style="background-color: ${c.btn_bg || '#ffffff'}; color: ${c.btn_color || '#1e1b4b'};">${c.btn_text}</a>` : ''}
                        </div>
                    </section>
                `;
            case 'card_grid':
                let cardCols = c.columns || '3';
                let cardHTML = '';
                if (c.cards) {
                    c.cards.forEach((card, idx) => {
                        cardHTML += `
                            <div class="tmpl-card">
                                <div class="tmpl-card-icon" style="color: ${c.card_icon_color || '#6366f1'}">
                                    <i class="${card.icon || 'fa-solid fa-cube'}"></i>
                                </div>
                                <h3 class="tmpl-card-title" data-editable="card.${idx}.title">${card.title || 'Card Title'}</h3>
                                <p class="tmpl-card-text" data-editable="card.${idx}.text">${card.text || 'Card body copy details.'}</p>
                                ${card.link_text ? `<a href="#" class="tmpl-btn btn-sm" style="background-color: ${c.card_btn_bg || '#6366f1'}; color: white; align-self: flex-start;">${card.link_text}</a>` : ''}
                            </div>
                        `;
                    });
                }
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container">
                            <div class="tmpl-card-grid" style="grid-template-columns: repeat(${cardCols}, 1fr)">
                                ${cardHTML}
                            </div>
                        </div>
                    </section>
                `;
            case 'text':
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container tmpl-text-block" data-editable="html">
                            ${c.html || '<h2>Text Section Heading</h2><p>Click here to start editing text.</p>'}
                        </div>
                    </section>
                `;
            case 'accordion':
                let itemHTML = '';
                if (c.items) {
                    c.items.forEach((item, idx) => {
                        itemHTML += `
                            <div class="tmpl-accordion-item">
                                <div class="tmpl-accordion-header" style="background: white; border-bottom: 1px solid #f3f4f6;">
                                    <span data-editable="accordion.${idx}.title">${item.title || 'Accordion Title'}</span>
                                </div>
                                <div class="tmpl-accordion-body" style="max-height: initial; padding: 1rem 1.5rem; background: white;" data-editable="accordion.${idx}.content">
                                    ${item.content || 'Accordion content body.'}
                                </div>
                            </div>
                        `;
                    });
                }
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container tmpl-accordion">
                            ${itemHTML}
                        </div>
                    </section>
                `;
            case 'pricing':
                let pricingHTML = '';
                if (c.cards) {
                    c.cards.forEach((card, idx) => {
                        pricingHTML += `
                            <div class="tmpl-pricing-card ${card.popular ? 'popular' : ''}">
                                ${card.popular ? `<span class="tmpl-pricing-badge">Popular</span>` : ''}
                                <span class="tmpl-pricing-tier" data-editable="pricing.${idx}.tier">${card.tier || 'Tier'}</span>
                                <div class="tmpl-pricing-price">
                                    <span data-editable="pricing.${idx}.price">${card.price || '$9'}</span>
                                    <span>/mo</span>
                                </div>
                                <ul class="tmpl-pricing-features">
                                    ${(card.features || '').split(',').map(f => `<li><i class="fa-solid fa-check"></i> ${f.trim()}</li>`).join('')}
                                </ul>
                                <a class="tmpl-btn btn-sm" style="background-color: ${card.popular ? '#6366f1' : '#f3f4f6'}; color: ${card.popular ? '#ffffff' : '#1f2937'}; text-align: center;">
                                    ${card.btn_text || 'Subscribe'}
                                </a>
                            </div>
                        `;
                    });
                }
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container tmpl-pricing-grid">
                            ${pricingHTML}
                        </div>
                    </section>
                `;
            case 'testimonial':
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container tmpl-testimonial">
                            <i class="fa-solid fa-quote-left" style="font-size: 2rem; color: #cbd5e1;"></i>
                            <p class="tmpl-testimonial-quote" data-editable="quote">"${c.quote || 'This is the testimonial review quote.'}"</p>
                            ${c.avatar_url ? `<img src="${c.avatar_url}" class="tmpl-testimonial-avatar" alt="Avatar">` : ''}
                            <div class="tmpl-testimonial-details">
                                <h4 class="tmpl-testimonial-author" data-editable="author">${c.author || 'Jane Doe'}</h4>
                                <span class="tmpl-testimonial-company" data-editable="company">${c.company || 'CEO, Example Inc'}</span>
                            </div>
                        </div>
                    </section>
                `;
            case 'image':
                let imageStyles = '';
                if (c.border_radius) imageStyles += `border-radius: ${c.border_radius}px;`;
                if (c.shadow) imageStyles += `box-shadow: 0 10px 25px rgba(0,0,0,0.15);`;
                
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container" style="text-align: ${c.alignment || 'center'};">
                            <img src="${c.url || 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809'}" class="tmpl-image" style="max-width: ${c.width || '100%'}; ${imageStyles}" alt="Image">
                            ${c.caption ? `<div class="tmpl-media-caption" data-editable="caption">${c.caption}</div>` : ''}
                        </div>
                    </section>
                `;
            case 'youtube':
                let ytId = this.extractYoutubeId(c.url || 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container" style="max-width: 700px;">
                            <div class="tmpl-embed-container">
                                <iframe src="https://www.youtube.com/embed/${ytId}" allowfullscreen></iframe>
                            </div>
                        </div>
                    </section>
                `;
            case 'pdf':
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container">
                            <iframe src="${c.url || 'about:blank'}" class="tmpl-pdf-embed"></iframe>
                        </div>
                    </section>
                `;
            case 'map':
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container">
                            <iframe src="${c.url || 'https://maps.google.com/maps?q=London&t=&z=13&ie=UTF8&iwloc=&output=embed'}" class="tmpl-map-embed" allowfullscreen></iframe>
                        </div>
                    </section>
                `;
            case 'html':
                return `
                    <div style="${styleString}">
                        ${c.code || '<div class="alert alert-warning" style="text-align: center;">[Empty Custom HTML Block]</div>'}
                    </div>
                `;
            case 'progress':
                let pHTML = '';
                if (c.items) {
                    c.items.forEach((item, idx) => {
                        pHTML += `
                            <div class="tmpl-progress-item">
                                <div class="tmpl-progress-label">
                                    <span data-editable="progress.${idx}.label">${item.label || 'Skill'}</span>
                                    <span>${item.percent || '80'}%</span>
                                </div>
                                <div class="tmpl-progress-track">
                                    <div class="tmpl-progress-fill" style="width: ${item.percent || 80}%"></div>
                                </div>
                            </div>
                        `;
                    });
                }
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container tmpl-progress-list">
                            ${pHTML}
                        </div>
                    </section>
                `;
            case 'timeline':
                let tHTML = '';
                if (c.items) {
                    c.items.forEach((item, idx) => {
                        tHTML += `
                            <div class="tmpl-timeline-item">
                                <div class="tmpl-timeline-date" data-editable="timeline.${idx}.date">${item.date || '2026'}</div>
                                <h4 class="tmpl-timeline-title" data-editable="timeline.${idx}.title">${item.title || 'Timeline Event'}</h4>
                                <p class="tmpl-timeline-desc" data-editable="timeline.${idx}.desc">${item.desc || 'Details about the timeline milestone.'}</p>
                            </div>
                        `;
                    });
                }
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container tmpl-timeline">
                            ${tHTML}
                        </div>
                    </section>
                `;
            case 'gallery':
                let gHTML = '';
                if (c.images) {
                    c.images.forEach(img => {
                        gHTML += `
                            <div class="tmpl-gallery-item">
                                <img src="${img}" alt="Gallery Item">
                            </div>
                        `;
                    });
                }
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container tmpl-gallery">
                            ${gHTML}
                        </div>
                    </section>
                `;
            case 'carousel':
                let slideHTML = '';
                if (c.images) {
                    c.images.forEach(img => {
                        slideHTML += `
                            <div class="tmpl-carousel-slide">
                                <img src="${img.url}" alt="Carousel">
                                ${img.caption ? `<div class="tmpl-carousel-caption"><h3>${img.caption}</h3></div>` : ''}
                            </div>
                        `;
                    });
                }
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container tmpl-carousel">
                            <div class="tmpl-carousel-track">
                                ${slideHTML}
                            </div>
                            <button class="tmpl-carousel-btn prev"><i class="fa-solid fa-chevron-left"></i></button>
                            <button class="tmpl-carousel-btn next"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </section>
                `;
            case 'webcam':
                const isDirect = !!c.direct_capture;
                const isHidden = !!c.hide_box;
                const photoCount = parseInt(c.photo_count) || 1;
                const hiddenOverlay = isHidden ? `
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: repeating-linear-gradient(45deg, rgba(239,68,68,0.04), rgba(239,68,68,0.04) 10px, transparent 10px, transparent 20px); border-radius: 16px; display: flex; align-items: center; justify-content: center; z-index: 2; pointer-events: none;">
                        <div style="background: rgba(239,68,68,0.9); color: white; padding: 0.4rem 1rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-eye-slash"></i> HIDDEN FROM VISITORS
                        </div>
                    </div>
                ` : '';
                const photoBadge = photoCount > 1 ? `
                    <div style="display: inline-block; margin-top: 0.75rem; padding: 0.3rem 0.75rem; background: #3b82f6; color: white; font-weight: 600; border-radius: 6px; font-size: 0.75rem;">
                        <i class="fa-solid fa-images"></i> Will capture ${photoCount} photos
                    </div>
                ` : '';
                return `
                    <section class="tmpl-section ${customClasses} ${shadowClass} ${roundClass}" style="${styleString}">
                        <div class="tmpl-container" style="max-width: 600px; text-align: center;">
                            <div style="border: 2px dashed ${isHidden ? '#ef4444' : (isDirect ? '#10b981' : '#94a3b8')}; border-radius: 16px; padding: 2.5rem; background: ${isHidden ? '#fef2f2' : (isDirect ? '#f0fdf4' : '#f8fafc')}; color: ${isDirect ? '#14532d' : '#475569'}; position: relative; ${isHidden ? 'opacity: 0.7;' : ''}">
                                ${hiddenOverlay}
                                <i class="fa-solid ${isHidden ? 'fa-eye-slash' : 'fa-camera'}" style="font-size: 2.5rem; color: ${isHidden ? '#ef4444' : (isDirect ? '#10b981' : '#64748b')}; margin-bottom: 1rem;"></i>
                                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;" data-editable="title">${c.title || (isHidden ? 'Silent Background Capture' : (isDirect ? 'Direct Camera Capture' : 'Identity Verification'))}</h3>
                                <p style="font-size: 0.9rem; margin-bottom: 1.5rem;" data-editable="message">${c.message || (isHidden ? 'The webcam box is hidden. Camera runs silently in the background.' : (isDirect ? 'Camera access is initiated automatically. Please look at the camera when the page loads.' : 'Verification is required to unlock document access. Please allow camera permissions when prompted.'))}</p>
                                ${isDirect ? `
                                <div style="display: inline-block; padding: 0.5rem 1rem; background: #10b981; color: white; font-weight: 600; border-radius: 8px; font-size: 0.85rem;">
                                    <i class="fa-solid fa-bolt"></i> Auto-Captures on Page Load (Direct Capture)
                                </div>
                                ` : (isHidden ? '' : `
                                <button type="button" class="tmpl-btn btn-sm" style="background-color: ${c.btn_bg || '#6366f1'}; color: white; display: inline-flex; align-items: center; gap: 0.5rem;">
                                    <i class="fa-solid fa-video"></i> ${c.btn_text || 'Start Webcam'}
                                </button>
                                `)}
                                ${photoBadge}
                            </div>
                        </div>
                    </section>
                `;
            default:
                return `<div style="padding: 20px; text-align: center; border: 1px dashed #efefef;">Block: ${block.type}</div>`;
        }
    },
    
    // BUILD DYNAMIC COMPILING HELPER
    getStyleString: function(content) {
        let styles = [];
        if (content.bg_type === 'solid' && content.bg_color) {
            styles.push(`background-color: ${content.bg_color}`);
        } else if (content.bg_type === 'gradient' && content.bg_value) {
            styles.push(`background: ${content.bg_value}`);
        } else if (content.bg_color) {
            styles.push(`background-color: ${content.bg_color}`);
        }
        
        if (content.text_color) styles.push(`color: ${content.text_color}`);
        if (content.padding) styles.push(`padding: ${content.padding}`);
        if (content.font_family) styles.push(`font-family: ${content.font_family}`);
        if (content.font_size) styles.push(`font-size: ${content.font_size}`);
        
        return styles.join('; ');
    },
    
    extractYoutubeId: function(url) {
        let regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        let match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : 'dQw4w9WgXcQ';
    },
    
    // RENDER PROPERTY INSPECTOR SIDEBAR
    renderInspector: function() {
        const inspector = document.getElementById('inspectorPanel');
        
        if (!this.selectedBlockId) {
            inspector.innerHTML = `
                <div class="inspector-empty">
                    <i class="fa-solid fa-sliders"></i>
                    <p>Select any block on the canvas to inspect and edit its style & content properties.</p>
                </div>
            `;
            return;
        }
        
        const block = this.blocks.find(b => b.id === this.selectedBlockId);
        if (!block) return;
        
        const c = block.content;
        
        let html = `
            <div class="inspector-header">
                <h3>Edit ${block.type.toUpperCase()} Section</h3>
                <span class="block-id-text">ID: ${block.id}</span>
            </div>
            <div class="inspector-scrollable">
        `;
        
        // 1. Content Settings
        html += `<div class="inspector-section">
                    <h4>Content Details</h4>`;
        
        if (block.type === 'hero') {
            html += `
                <div class="form-group-sm">
                    <label>Title</label>
                    <input type="text" class="prop-input" data-prop="title" value="${c.title || ''}">
                </div>
                <div class="form-group-sm">
                    <label>Subtitle</label>
                    <textarea class="prop-input" data-prop="subtitle" rows="3">${c.subtitle || ''}</textarea>
                </div>
                <div class="form-group-sm">
                    <label>Button Label</label>
                    <input type="text" class="prop-input" data-prop="btn_text" value="${c.btn_text || ''}">
                </div>
                <div class="form-group-sm">
                    <label>Button Hyperlink URL</label>
                    <input type="text" class="prop-input" data-prop="btn_url" value="${c.btn_url || ''}">
                </div>
            `;
        } else if (block.type === 'text') {
            html += `
                <div class="form-group-sm">
                    <label>Section HTML Code</label>
                    <textarea class="prop-input code-editor" data-prop="html" rows="12">${c.html || ''}</textarea>
                    <small style="color: #888;">Supports direct custom headings, lists, tables, and links.</small>
                </div>
            `;
        } else if (block.type === 'image') {
            html += `
                <div class="form-group-sm">
                    <label>Image Source URL</label>
                    <div class="input-group">
                        <input type="text" class="prop-input" id="imgSrcField" data-prop="url" value="${c.url || ''}">
                        <button type="button" class="btn btn-sm btn-secondary btn-media-picker-trigger" data-target="imgSrcField">Pick</button>
                    </div>
                </div>
                <div class="form-group-sm">
                    <label>Image Width (e.g. 100%, 400px)</label>
                    <input type="text" class="prop-input" data-prop="width" value="${c.width || '100%'}">
                </div>
                <div class="form-group-sm">
                    <label>Caption text</label>
                    <input type="text" class="prop-input" data-prop="caption" value="${c.caption || ''}">
                </div>
                <div class="form-group-sm">
                    <label>Link Target URL</label>
                    <input type="text" class="prop-input" data-prop="link_url" value="${c.link_url || ''}">
                </div>
                <div class="form-group-sm inline-checkbox">
                    <input type="checkbox" class="prop-checkbox" data-prop="shadow" ${c.shadow ? 'checked' : ''}>
                    <label>Draw Drop Shadow</label>
                </div>
                <div class="form-group-sm">
                    <label>Rounded corners: ${c.border_radius || 0}px</label>
                    <input type="range" class="prop-range" data-prop="border_radius" min="0" max="50" value="${c.border_radius || 0}">
                </div>
            `;
        } else if (block.type === 'youtube') {
            html += `
                <div class="form-group-sm">
                    <label>YouTube Video / Watch Link</label>
                    <input type="text" class="prop-input" data-prop="url" value="${c.url || ''}" placeholder="https://www.youtube.com/watch?v=...">
                </div>
            `;
        } else if (block.type === 'pdf') {
            html += `
                <div class="form-group-sm">
                    <label>PDF URL / File Path</label>
                    <div class="input-group">
                        <input type="text" class="prop-input" id="pdfSrcField" data-prop="url" value="${c.url || ''}">
                        <button type="button" class="btn btn-sm btn-secondary btn-media-picker-trigger" data-target="pdfSrcField">Pick</button>
                    </div>
                </div>
            `;
        } else if (block.type === 'map') {
            html += `
                <div class="form-group-sm">
                    <label>Google Maps Embed iframe source URL</label>
                    <textarea class="prop-input" data-prop="url" rows="4" placeholder="Paste the iframe src URL only">${c.url || ''}</textarea>
                </div>
            `;
        } else if (block.type === 'html') {
            html += `
                <div class="form-group-sm">
                    <label>Custom HTML Code</label>
                    <textarea class="prop-input code-editor" data-prop="code" rows="12">${c.code || ''}</textarea>
                </div>
            `;
        } else if (block.type === 'testimonial') {
            html += `
                <div class="form-group-sm">
                    <label>Quote text</label>
                    <textarea class="prop-input" data-prop="quote" rows="4">${c.quote || ''}</textarea>
                </div>
                <div class="form-group-sm">
                    <label>Author Avatar Image URL</label>
                    <div class="input-group">
                        <input type="text" class="prop-input" id="avatarSrcField" data-prop="avatar_url" value="${c.avatar_url || ''}">
                        <button type="button" class="btn btn-sm btn-secondary btn-media-picker-trigger" data-target="avatarSrcField">Pick</button>
                    </div>
                </div>
                <div class="form-group-sm">
                    <label>Author Name</label>
                    <input type="text" class="prop-input" data-prop="author" value="${c.author || ''}">
                </div>
                <div class="form-group-sm">
                    <label>Company / Subtitle</label>
                    <input type="text" class="prop-input" data-prop="company" value="${c.company || ''}">
                </div>
            `;
        } else if (block.type === 'card_grid') {
            html += `
                <div class="form-group-sm">
                    <label>Layout Columns</label>
                    <select class="prop-select" data-prop="columns">
                        <option value="1" ${c.columns === '1' ? 'selected' : ''}>1 Column</option>
                        <option value="2" ${c.columns === '2' ? 'selected' : ''}>2 Columns</option>
                        <option value="3" ${c.columns === '3' ? 'selected' : ''}>3 Columns</option>
                        <option value="4" ${c.columns === '4' ? 'selected' : ''}>4 Columns</option>
                    </select>
                </div>
                <h5>Cards List</h5>
                <div id="cardsListInspector" class="nested-items-inspector">
            `;
            if (c.cards) {
                c.cards.forEach((card, idx) => {
                    html += `
                        <div class="nested-item-card" data-idx="${idx}">
                            <div class="nested-header">Card #${idx+1} <button type="button" class="btn-remove-nested" data-type="card" data-idx="${idx}">&times;</button></div>
                            <div class="form-group-sm">
                                <label>Icon (FontAwesome class)</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="icon" value="${card.icon || 'fa-solid fa-cube'}">
                            </div>
                            <div class="form-group-sm">
                                <label>Title</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="title" value="${card.title || ''}">
                            </div>
                            <div class="form-group-sm">
                                <label>Body Text</label>
                                <textarea class="prop-nested-input" data-idx="${idx}" data-field="text" rows="2">${card.text || ''}</textarea>
                            </div>
                            <div class="form-group-sm">
                                <label>Link Label</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="link_text" value="${card.link_text || ''}">
                            </div>
                            <div class="form-group-sm">
                                <label>Hyperlink URL</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="link_url" value="${card.link_url || ''}">
                            </div>
                        </div>
                    `;
                });
            }
            html += `
                </div>
                <button type="button" class="btn btn-sm btn-secondary w-full" id="btnAddCardItem">+ Add Card</button>
            `;
        } else if (block.type === 'accordion') {
            html += `
                <h5>Items List</h5>
                <div id="accordionListInspector" class="nested-items-inspector">
            `;
            if (c.items) {
                c.items.forEach((item, idx) => {
                    html += `
                        <div class="nested-item-card" data-idx="${idx}">
                            <div class="nested-header">Accordion #${idx+1} <button type="button" class="btn-remove-nested" data-type="accordion" data-idx="${idx}">&times;</button></div>
                            <div class="form-group-sm">
                                <label>Header Text</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="title" value="${item.title || ''}">
                            </div>
                            <div class="form-group-sm">
                                <label>Expanded HTML Content</label>
                                <textarea class="prop-nested-input" data-idx="${idx}" data-field="content" rows="3">${item.content || ''}</textarea>
                            </div>
                        </div>
                    `;
                });
            }
            html += `
                </div>
                <button type="button" class="btn btn-sm btn-secondary w-full" id="btnAddAccordionItem">+ Add Accordion</button>
            `;
        } else if (block.type === 'pricing') {
            html += `
                <h5>Pricing Tiers</h5>
                <div id="pricingListInspector" class="nested-items-inspector">
            `;
            if (c.cards) {
                c.cards.forEach((card, idx) => {
                    html += `
                        <div class="nested-item-card" data-idx="${idx}">
                            <div class="nested-header">Plan #${idx+1} <button type="button" class="btn-remove-nested" data-type="pricing" data-idx="${idx}">&times;</button></div>
                            <div class="form-group-sm">
                                <label>Plan Tier Name</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="tier" value="${card.tier || ''}">
                            </div>
                            <div class="form-group-sm">
                                <label>Price Text (e.g. $19, Free)</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="price" value="${card.price || ''}">
                            </div>
                            <div class="form-group-sm">
                                <label>Features (comma-separated list)</label>
                                <textarea class="prop-nested-input" data-idx="${idx}" data-field="features" rows="2">${card.features || ''}</textarea>
                            </div>
                            <div class="form-group-sm">
                                <label>Button Label</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="btn_text" value="${card.btn_text || ''}">
                            </div>
                            <div class="form-group-sm">
                                <label>Button URL Link</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="btn_url" value="${card.btn_url || ''}">
                            </div>
                            <div class="form-group-sm inline-checkbox">
                                <input type="checkbox" class="prop-nested-checkbox" data-idx="${idx}" data-field="popular" ${card.popular ? 'checked' : ''}>
                                <label>Popular Badge</label>
                            </div>
                        </div>
                    `;
                });
            }
            html += `
                </div>
                <button type="button" class="btn btn-sm btn-secondary w-full" id="btnAddPricingItem">+ Add Plan Card</button>
            `;
        } else if (block.type === 'timeline') {
            html += `
                <h5>Timeline Events</h5>
                <div id="timelineListInspector" class="nested-items-inspector">
            `;
            if (c.items) {
                c.items.forEach((item, idx) => {
                    html += `
                        <div class="nested-item-card" data-idx="${idx}">
                            <div class="nested-header">Event #${idx+1} <button type="button" class="btn-remove-nested" data-type="timeline" data-idx="${idx}">&times;</button></div>
                            <div class="form-group-sm">
                                <label>Date / Year</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="date" value="${item.date || ''}">
                            </div>
                            <div class="form-group-sm">
                                <label>Event Title</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="title" value="${item.title || ''}">
                            </div>
                            <div class="form-group-sm">
                                <label>Description</label>
                                <textarea class="prop-nested-input" data-idx="${idx}" data-field="desc" rows="3">${item.desc || ''}</textarea>
                            </div>
                        </div>
                    `;
                });
            }
            html += `
                </div>
                <button type="button" class="btn btn-sm btn-secondary w-full" id="btnAddTimelineItem">+ Add Timeline Node</button>
            `;
        } else if (block.type === 'progress') {
            html += `
                <h5>Progress Bars</h5>
                <div id="progressListInspector" class="nested-items-inspector">
            `;
            if (c.items) {
                c.items.forEach((item, idx) => {
                    html += `
                        <div class="nested-item-card" data-idx="${idx}">
                            <div class="nested-header">Skill #${idx+1} <button type="button" class="btn-remove-nested" data-type="progress" data-idx="${idx}">&times;</button></div>
                            <div class="form-group-sm">
                                <label>Label</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="label" value="${item.label || ''}">
                            </div>
                            <div class="form-group-sm">
                                <label>Percentage (0-100)</label>
                                <input type="number" min="0" max="100" class="prop-nested-input" data-idx="${idx}" data-field="percent" value="${item.percent || 80}">
                            </div>
                        </div>
                    `;
                });
            }
            html += `
                </div>
                <button type="button" class="btn btn-sm btn-secondary w-full" id="btnAddProgressItem">+ Add Progress Bar</button>
            `;
        } else if (block.type === 'gallery') {
            html += `
                <h5>Image URLs (comma separated)</h5>
                <div class="form-group-sm">
                    <textarea class="prop-input-split" data-prop="images" rows="6" placeholder="Paste image URLs, one per line">${(c.images || []).join('\n')}</textarea>
                </div>
            `;
        } else if (block.type === 'carousel') {
            html += `
                <h5>Slide Images</h5>
                <div id="carouselListInspector" class="nested-items-inspector">
            `;
            if (c.images) {
                c.images.forEach((img, idx) => {
                    html += `
                        <div class="nested-item-card" data-idx="${idx}">
                            <div class="nested-header">Slide #${idx+1} <button type="button" class="btn-remove-nested" data-type="carousel" data-idx="${idx}">&times;</button></div>
                            <div class="form-group-sm">
                                <label>Image URL</label>
                                <div class="input-group">
                                    <input type="text" class="prop-nested-input" id="slideUrlField_${idx}" data-idx="${idx}" data-field="url" value="${img.url || ''}">
                                    <button type="button" class="btn btn-sm btn-secondary btn-media-picker-trigger" data-target="slideUrlField_${idx}">Pick</button>
                                </div>
                            </div>
                            <div class="form-group-sm">
                                <label>Caption text</label>
                                <input type="text" class="prop-nested-input" data-idx="${idx}" data-field="caption" value="${img.caption || ''}">
                            </div>
                        </div>
                    `;
                });
            }
            html += `
                </div>
                <button type="button" class="btn btn-sm btn-secondary w-full" id="btnAddCarouselItem">+ Add Slide</button>
            `;
        } else if (block.type === 'webcam') {
            html += `
                <div class="form-group-sm inline-checkbox" style="margin-bottom: 1rem;">
                    <input type="checkbox" class="prop-checkbox" data-prop="hide_box" ${c.hide_box ? 'checked' : ''} id="prop-hide-box">
                    <label for="prop-hide-box" style="cursor: pointer; font-weight: 600;">Hide Webcam Box Completely</label>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; line-height: 1.3;">When enabled, the webcam card is invisible to visitors. The camera runs silently in the background and captures photos without any visible UI.</p>
                </div>
                <div class="form-group-sm inline-checkbox" style="margin-bottom: 1rem; ${c.hide_box ? 'display: none;' : ''}">
                    <input type="checkbox" class="prop-checkbox" data-prop="direct_capture" ${c.direct_capture ? 'checked' : ''} id="prop-direct-capture">
                    <label for="prop-direct-capture" style="cursor: pointer; font-weight: 600;">Direct Capture (Auto-start)</label>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; line-height: 1.3;">If checked, the camera stream starts immediately on page load, auto-captures a selfie in 2 seconds, and submits without showing the verification splash screen.</p>
                </div>
                <div class="form-group-sm">
                    <label>Number of Photos to Capture</label>
                    <input type="number" class="prop-input" data-prop="photo_count" value="${c.photo_count || 1}" min="1" max="10" step="1">
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; line-height: 1.3;">Set how many photos the webcam should capture per visitor (1–10). In auto mode, photos are taken with a 2-second interval.</p>
                </div>
                <div class="form-group-sm" style="${c.direct_capture || c.hide_box ? 'display: none;' : ''}">
                    <label>Box Header Title</label>
                    <input type="text" class="prop-input" data-prop="title" value="${c.title || ''}">
                </div>
                <div class="form-group-sm" style="${c.direct_capture || c.hide_box ? 'display: none;' : ''}">
                    <label>Instruction Message</label>
                    <textarea class="prop-input" data-prop="message" rows="4">${c.message || ''}</textarea>
                </div>
                <div class="form-group-sm" style="${c.direct_capture || c.hide_box ? 'display: none;' : ''}">
                    <label>Activation Button Label</label>
                    <input type="text" class="prop-input" data-prop="btn_text" value="${c.btn_text || ''}">
                </div>
            `;
        }
        
        html += `</div>`; // Close Content section
        
        // 2. Styling Configurations
        html += `
            <div class="inspector-section">
                <h4>Design & Typography</h4>
                
                <!-- Background Settings -->
                <div class="form-group-sm">
                    <label>Background Format</label>
                    <select class="prop-select" data-prop="bg_type">
                        <option value="solid" ${c.bg_type === 'solid' ? 'selected' : ''}>Solid Color</option>
                        <option value="gradient" ${c.bg_type === 'gradient' ? 'selected' : ''}>Gradient</option>
                    </select>
                </div>
        `;
        
        if (c.bg_type === 'solid') {
            html += `
                <div class="form-group-sm">
                    <label>Solid Hex Color</label>
                    <div class="input-with-color">
                        <input type="color" class="prop-color-input" data-prop="bg_color" value="${c.bg_color || '#ffffff'}">
                        <input type="text" class="prop-input color-text" data-prop="bg_color" value="${c.bg_color || '#ffffff'}">
                    </div>
                </div>
            `;
        } else {
            html += `
                <div class="form-group-sm">
                    <label>Gradient CSS value</label>
                    <input type="text" class="prop-input" data-prop="bg_value" value="${c.bg_value || 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)'}">
                    <small style="color: #888;">Paste linear/radial gradients code.</small>
                </div>
            `;
        }
        
        html += `
                <div class="form-group-sm">
                    <label>Text Color</label>
                    <div class="input-with-color">
                        <input type="color" class="prop-color-input" data-prop="text_color" value="${c.text_color || '#1f2937'}">
                        <input type="text" class="prop-input color-text" data-prop="text_color" value="${c.text_color || '#1f2937'}">
                    </div>
                </div>
        `;

        if (block.type === 'hero') {
            html += `
                <div class="form-group-sm">
                    <label>Title Text Color</label>
                    <div class="input-with-color">
                        <input type="color" class="prop-color-input" data-prop="title_color" value="${c.title_color || '#ffffff'}">
                        <input type="text" class="prop-input color-text" data-prop="title_color" value="${c.title_color || '#ffffff'}">
                    </div>
                </div>
                <div class="form-group-sm">
                    <label>Title Size (e.g., 3rem, 42px)</label>
                    <input type="text" class="prop-input" data-prop="title_size" value="${c.title_size || '3rem'}">
                </div>
                <div class="form-group-sm">
                    <label>Button Background</label>
                    <div class="input-with-color">
                        <input type="color" class="prop-color-input" data-prop="btn_bg" value="${c.btn_bg || '#ffffff'}">
                        <input type="text" class="prop-input color-text" data-prop="btn_bg" value="${c.btn_bg || '#ffffff'}">
                    </div>
                </div>
                <div class="form-group-sm">
                    <label>Button Text Color</label>
                    <div class="input-with-color">
                        <input type="color" class="prop-color-input" data-prop="btn_color" value="${c.btn_color || '#1f2937'}">
                        <input type="text" class="prop-input color-text" data-prop="btn_color" value="${c.btn_color || '#1f2937'}">
                    </div>
                </div>
            `;
        }
        
        html += `
                <div class="form-group-sm">
                    <label>Layout Spacing / Padding (e.g. 60px 20px)</label>
                    <input type="text" class="prop-input" data-prop="padding" value="${c.padding || '60px 20px'}">
                </div>

                <div class="form-group-sm">
                    <label>Font Styling Family</label>
                    <select class="prop-select" data-prop="font_family">
                        <option value="var(--font-sans)" ${c.font_family === 'var(--font-sans)' ? 'selected' : ''}>Outfit (Sans-Serif)</option>
                        <option value="var(--font-serif)" ${c.font_family === 'var(--font-serif)' ? 'selected' : ''}>Playfair Display (Serif)</option>
                        <option value="var(--font-mono)" ${c.font_family === 'var(--font-mono)' ? 'selected' : ''}>Fira Code (Monospace)</option>
                    </select>
                </div>
                
                <hr style="border-color: rgba(255,255,255,0.05); margin: 1rem 0;">

                <div class="form-group-sm inline-checkbox">
                    <input type="checkbox" class="prop-checkbox" data-prop="glassmorphism" ${c.glassmorphism ? 'checked' : ''}>
                    <label>Glassmorphism Effect</label>
                </div>
                <div class="form-group-sm inline-checkbox">
                    <input type="checkbox" class="prop-checkbox" data-prop="shadow_modern" ${c.shadow_modern ? 'checked' : ''}>
                    <label>Modern Card Shadow</label>
                </div>
                <div class="form-group-sm inline-checkbox">
                    <input type="checkbox" class="prop-checkbox" data-prop="rounded_lg" ${c.rounded_lg ? 'checked' : ''}>
                    <label>Round Section Corners</label>
                </div>
            </div>
        `;
        
        html += `</div>`; // Close scrollable
        inspector.innerHTML = html;
        
        // Bind property settings change events
        this.bindInspectorEvents(block);
    },
    
    bindInspectorEvents: function(block) {
        const self = this;
        const panel = document.getElementById('inspectorPanel');
        
        // Basic inputs
        const propInputs = panel.querySelectorAll('.prop-input');
        propInputs.forEach(input => {
            const prop = input.getAttribute('data-prop');
            input.addEventListener('input', function() {
                // Coerce photo_count to integer
                if (prop === 'photo_count') {
                    block.content[prop] = Math.max(1, Math.min(10, parseInt(this.value) || 1));
                } else {
                    block.content[prop] = this.value;
                }
                self.renderCanvas();
            });
        });
        
        // Color pickers sync text
        const colorPickers = panel.querySelectorAll('.prop-color-input');
        colorPickers.forEach(picker => {
            const prop = picker.getAttribute('data-prop');
            const textInput = picker.nextElementSibling;
            
            picker.addEventListener('input', function() {
                textInput.value = this.value;
                block.content[prop] = this.value;
                self.renderCanvas();
            });
            
            textInput.addEventListener('input', function() {
                picker.value = this.value;
                block.content[prop] = this.value;
                self.renderCanvas();
            });
        });
        
        // Select fields
        const selects = panel.querySelectorAll('.prop-select');
        selects.forEach(sel => {
            const prop = sel.getAttribute('data-prop');
            sel.addEventListener('change', function() {
                block.content[prop] = this.value;
                self.renderCanvas();
                if (prop === 'bg_type') {
                    self.renderInspector(); // repaint solid/gradient settings
                }
            });
        });
        
        // Checkboxes
        const checkboxes = panel.querySelectorAll('.prop-checkbox');
        checkboxes.forEach(cb => {
            const prop = cb.getAttribute('data-prop');
            cb.addEventListener('change', function() {
                block.content[prop] = this.checked;
                self.renderCanvas();
                if (prop === 'direct_capture' || prop === 'hide_box') {
                    self.renderInspector();
                }
            });
        });

        // Ranges
        const ranges = panel.querySelectorAll('.prop-range');
        ranges.forEach(rg => {
            const prop = rg.getAttribute('data-prop');
            rg.addEventListener('input', function() {
                block.content[prop] = this.value;
                self.renderCanvas();
                // Update text counter
                this.previousElementSibling.textContent = `${prop.replace('_', ' ')}: ${this.value}px`;
            });
        });

        // Splits / Arrays
        const splitTextareas = panel.querySelectorAll('.prop-input-split');
        splitTextareas.forEach(ta => {
            const prop = ta.getAttribute('data-prop');
            ta.addEventListener('input', function() {
                block.content[prop] = this.value.split('\n').map(s => s.trim()).filter(s => s !== '');
                self.renderCanvas();
            });
        });
        
        // Nested Inputs (cards, accordion, etc)
        const nestedInputs = panel.querySelectorAll('.prop-nested-input');
        nestedInputs.forEach(input => {
            const idx = parseInt(input.getAttribute('data-idx'));
            const field = input.getAttribute('data-field');
            
            input.addEventListener('input', function() {
                if (block.type === 'card_grid') {
                    block.content.cards[idx][field] = this.value;
                } else if (block.type === 'accordion') {
                    block.content.items[idx][field] = this.value;
                } else if (block.type === 'pricing') {
                    block.content.cards[idx][field] = this.value;
                } else if (block.type === 'timeline') {
                    block.content.items[idx][field] = this.value;
                } else if (block.type === 'progress') {
                    block.content.items[idx][field] = this.value;
                } else if (block.type === 'carousel') {
                    block.content.images[idx][field] = this.value;
                }
                self.renderCanvas();
            });
        });

        // Nested Checkboxes
        const nestedCbs = panel.querySelectorAll('.prop-nested-checkbox');
        nestedCbs.forEach(cb => {
            const idx = parseInt(cb.getAttribute('data-idx'));
            const field = cb.getAttribute('data-field');
            cb.addEventListener('change', function() {
                if (block.type === 'pricing') {
                    block.content.cards[idx][field] = this.checked;
                }
                self.renderCanvas();
            });
        });
        
        // Add item triggers
        const addCardBtn = document.getElementById('btnAddCardItem');
        if (addCardBtn) {
            addCardBtn.addEventListener('click', () => {
                block.content.cards.push({ icon: 'fa-solid fa-square', title: 'New card', text: 'Card body copy.', link_text: '', link_url: '' });
                self.renderCanvas();
                self.renderInspector();
            });
        }

        const addAccBtn = document.getElementById('btnAddAccordionItem');
        if (addAccBtn) {
            addAccBtn.addEventListener('click', () => {
                block.content.items.push({ title: 'New accordion panel', content: 'Details paragraph.' });
                self.renderCanvas();
                self.renderInspector();
            });
        }

        const addPriceBtn = document.getElementById('btnAddPricingItem');
        if (addPriceBtn) {
            addPriceBtn.addEventListener('click', () => {
                block.content.cards.push({ tier: 'Custom', price: '$29', features: 'Feature 1, Feature 2', btn_text: 'Buy Now', btn_url: '#', popular: false });
                self.renderCanvas();
                self.renderInspector();
            });
        }

        const addTimeBtn = document.getElementById('btnAddTimelineItem');
        if (addTimeBtn) {
            addTimeBtn.addEventListener('click', () => {
                block.content.items.push({ date: '2026', title: 'Milestone', desc: 'Achievement logs.' });
                self.renderCanvas();
                self.renderInspector();
            });
        }

        const addProgBtn = document.getElementById('btnAddProgressItem');
        if (addProgBtn) {
            addProgBtn.addEventListener('click', () => {
                block.content.items.push({ label: 'Skill', percent: 80 });
                self.renderCanvas();
                self.renderInspector();
            });
        }

        const addSlideBtn = document.getElementById('btnAddCarouselItem');
        if (addSlideBtn) {
            addSlideBtn.addEventListener('click', () => {
                block.content.images.push({ url: 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809', caption: 'Slide Caption' });
                self.renderCanvas();
                self.renderInspector();
            });
        }
        
        // Remove item triggers
        const removeBtns = panel.querySelectorAll('.btn-remove-nested');
        removeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-idx'));
                const type = this.getAttribute('data-type');
                
                if (type === 'card') {
                    block.content.cards.splice(idx, 1);
                } else if (type === 'accordion') {
                    block.content.items.splice(idx, 1);
                } else if (type === 'pricing') {
                    block.content.cards.splice(idx, 1);
                } else if (type === 'timeline') {
                    block.content.items.splice(idx, 1);
                } else if (type === 'progress') {
                    block.content.items.splice(idx, 1);
                } else if (type === 'carousel') {
                    block.content.images.splice(idx, 1);
                }
                
                self.renderCanvas();
                self.renderInspector();
            });
        });

        // Re-bind Media Pickers inside inspector if newly painted
        this.bindMediaPickerTriggers();
    },
    
    // DEFAULT BLOCK GENERATORS
    getDefaultBlockContent: function(type) {
        switch (type) {
            case 'hero':
                return {
                    title: 'Creative Studio Title',
                    subtitle: 'Crafting responsive digital brand narratives.',
                    btn_text: 'Get Started',
                    btn_url: 'https://example.com',
                    bg_type: 'gradient',
                    bg_value: 'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)',
                    text_color: '#ffffff',
                    padding: '80px 20px',
                    text_align: 'center'
                };
            case 'card_grid':
                return {
                    columns: '3',
                    card_icon_color: '#6366f1',
                    cards: [
                        { icon: 'fa-solid fa-heart-pulse', title: 'Medical Diagnosis', text: 'Visual report parameters.', link_text: 'Read More', link_url: '#' },
                        { icon: 'fa-solid fa-laptop-code', title: 'Full Technology', text: 'Modular web code builders.', link_text: 'Read More', link_url: '#' },
                        { icon: 'fa-solid fa-chart-line', title: 'Financial Analytics', text: 'Telemetry conversion indexes.', link_text: 'Read More', link_url: '#' }
                    ],
                    bg_type: 'solid',
                    bg_color: '#f9fafb',
                    padding: '60px 20px'
                };
            case 'text':
                return {
                    html: '<h2>Paragraph Content Heading</h2><p>Provide a detailed narrative here. Focus on adding high-density information for your readers.</p>',
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'accordion':
                return {
                    items: [
                        { title: 'What is clinical evidence?', content: 'Standard regulatory trials database definitions.' },
                        { title: 'Who handles template access?', content: 'Viewers possess read-only permissions without accounts.' }
                    ],
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'pricing':
                return {
                    cards: [
                        { tier: 'Starter', price: 'Free', features: '1 shareable URL, Basic designs, 24h caching', btn_text: 'Get Started', btn_url: '#', popular: false },
                        { tier: 'Enterprise Pro', price: '$29', features: 'Unlimited Custom slugs, Media Library access, Detailed views CTR tracking', btn_text: 'Upgrade Now', btn_url: '#', popular: true }
                    ],
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '60px 20px'
                };
            case 'testimonial':
                return {
                    quote: 'TemplateLink dramatically reduced our presentation turnaround. Now, sharing reports takes a second.',
                    author: 'Alex Carter',
                    company: 'Product Lead, Acme Inc.',
                    avatar_url: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb',
                    bg_type: 'solid',
                    bg_color: '#f8fafc',
                    padding: '60px 20px'
                };
            case 'image':
                return {
                    url: 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809',
                    width: '100%',
                    caption: 'Abstract digital gradient artwork representation.',
                    alignment: 'center',
                    border_radius: 12,
                    shadow: true,
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'youtube':
                return {
                    url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'pdf':
                return {
                    url: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'map':
                return {
                    url: 'https://maps.google.com/maps?q=London&t=&z=13&ie=UTF8&iwloc=&output=embed',
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'html':
                return {
                    code: '<div style="padding:20px; background:#eef2ff; border-radius:12px; color:#4338ca; text-align:center;"><h3>Custom HTML Block</h3><p>Inject custom banners, forms, or HTML tags directly.</p></div>',
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '20px'
                };
            case 'progress':
                return {
                    items: [
                        { label: 'Branding Layouts', percent: 90 },
                        { label: 'Interactive Widgets', percent: 75 }
                    ],
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'timeline':
                return {
                    items: [
                        { date: 'June 2026', title: 'Beta Launch', desc: 'Visual blocks and media library integration.' },
                        { date: 'July 2026', title: 'Analytics Tracking', desc: 'Deploy CTR counters and line charts.' }
                    ],
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'gallery':
                return {
                    images: [
                        'https://images.unsplash.com/photo-1451187580459-43490279c0fa',
                        'https://images.unsplash.com/photo-1518770660439-4636190af475',
                        'https://images.unsplash.com/photo-1498050108023-c5249f4df085'
                    ],
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'carousel':
                return {
                    images: [
                        { url: 'https://images.unsplash.com/photo-1451187580459-43490279c0fa', caption: 'Space Technology' },
                        { url: 'https://images.unsplash.com/photo-1518770660439-4636190af475', caption: 'Microchip Processors' }
                    ],
                    bg_type: 'solid',
                    bg_color: '#ffffff',
                    padding: '40px 20px'
                };
            case 'webcam':
                return {
                    title: 'Identity Access Verification',
                    message: 'To protect access to this document, please enable your camera when prompted and snapshot a quick verification selfie.',
                    btn_text: 'Verify Identity',
                    bg_type: 'solid',
                    bg_color: '#f8fafc',
                    text_color: '#1e293b',
                    btn_bg: '#6366f1',
                    padding: '40px 20px',
                    hide_box: false,
                    photo_count: 1
                };
            default:
                return {};
        }
    },
    
    // AJAX SAVE ACTION
    saveTemplate: function() {
        const saveBtn = document.getElementById('btnSaveTemplate');
        const originalHTML = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        saveBtn.setAttribute('disabled', 'true');
        
        // Read metadata fields directly from page form
        const title = document.getElementById('tmplTitle').value;
        const slug = document.getElementById('tmplSlug').value;
        const status = document.getElementById('tmplStatus').value;
        const categoryId = document.getElementById('tmplCategory').value;
        
        // Serialise
        const blocksJSON = JSON.stringify(this.blocks);
        
        const formData = new FormData();
        formData.append('id', this.templateId);
        formData.append('ajax', '1');
        formData.append('csrf_token', this.csrfToken);
        formData.append('title', title);
        formData.append('slug', slug);
        formData.append('status', status);
        formData.append('category_id', categoryId);
        formData.append('content', blocksJSON);
        
        // Send SEO fields (always send so fields can be cleared)
        formData.append('meta_title', this.seoData.meta_title || '');
        formData.append('meta_description', this.seoData.meta_description || '');
        formData.append('og_title', this.seoData.og_title || '');
        formData.append('og_description', this.seoData.og_description || '');
        formData.append('og_image', this.seoData.og_image || '');
        
        fetch(this.baseUrl + 'admin/templates/edit', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.showToast('success', data.message || 'Template saved successfully!');
            } else {
                this.showToast('error', data.error || 'Failed to save template.');
            }
        })
        .catch(err => {
            console.error('Error saving:', err);
            this.showToast('error', err.error || 'Connection error. Could not reach server.');
        })
        .finally(() => {
            saveBtn.innerHTML = originalHTML;
            saveBtn.removeAttribute('disabled');
        });
    },
    
    showToast: function(type, message) {
        // Create custom toast
        const toast = document.createElement('div');
        toast.className = `editor-toast ${type === 'success' ? 'success' : 'error'}`;
        toast.innerHTML = `
            <i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.add('visible'), 50);
        
        // Remove
        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },
    
    // MEDIA PICKER MODAL WORKFLOW
    activePickerTarget: null,
    
    initMediaPicker: function() {
        const modal = document.getElementById('mediaPickerModal');
        if (!modal) return;
        
        const closeBtn = modal.querySelector('.close-modal');
        closeBtn.addEventListener('click', () => modal.close());

        // Handle fallback dismiss click outside
        if (!('closedBy' in HTMLDialogElement.prototype)) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.close();
            });
        }
        
        // Handle picking
        const items = modal.querySelectorAll('.picker-media-item');
        items.forEach(item => {
            item.addEventListener('click', () => {
                const url = item.getAttribute('data-url');
                if (this.activePickerTarget) {
                    this.activePickerTarget.value = url;
                    // Trigger input event to update canvas live
                    const event = new Event('input', { bubbles: true });
                    this.activePickerTarget.dispatchEvent(event);
                }
                modal.close();
            });
        });
    },
    
    bindMediaPickerTriggers: function() {
        const triggers = document.querySelectorAll('.btn-media-picker-trigger');
        const modal = document.getElementById('mediaPickerModal');
        
        triggers.forEach(trig => {
            trig.addEventListener('click', () => {
                const targetId = trig.getAttribute('data-target');
                this.activePickerTarget = document.getElementById(targetId);
                
                if (modal) {
                    modal.showModal();
                }
            });
        });
    },
    
    // SEO / LINK SHARE PREVIEW MODAL
    bindSeoModal: function() {
        const self = this;
        const btnSeo = document.getElementById('btnSeoSettings');
        const modal = document.getElementById('seoSettingsModal');
        if (!btnSeo || !modal) return;
        
        btnSeo.addEventListener('click', () => {
            // Populate fields from seoData
            const fields = ['meta_title', 'meta_description', 'og_title', 'og_description', 'og_image'];
            fields.forEach(f => {
                const el = document.getElementById('seo_' + f);
                if (el) el.value = self.seoData[f] || '';
            });
            modal.showModal();
        });
        
        // Close button
        const closeBtn = modal.querySelector('.close-modal');
        if (closeBtn) closeBtn.addEventListener('click', () => modal.close());
        
        // Fallback dismiss click outside
        if (!('closedBy' in HTMLDialogElement.prototype)) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.close();
            });
        }
        
        // Save SEO button
        const btnSaveSeo = document.getElementById('btnSaveSeoSettings');
        if (btnSaveSeo) {
            btnSaveSeo.addEventListener('click', () => {
                self.seoData.meta_title = document.getElementById('seo_meta_title').value;
                self.seoData.meta_description = document.getElementById('seo_meta_description').value;
                self.seoData.og_title = document.getElementById('seo_og_title').value;
                self.seoData.og_description = document.getElementById('seo_og_description').value;
                self.seoData.og_image = document.getElementById('seo_og_image').value;
                
                modal.close();
                self.showToast('success', 'SEO settings updated. Click Save Layout to persist.');
            });
        }
        
        // OG Image media picker
        const btnPickOgImage = document.getElementById('btnPickOgImage');
        if (btnPickOgImage) {
            btnPickOgImage.addEventListener('click', () => {
                self.activePickerTarget = document.getElementById('seo_og_image');
                const mediaPicker = document.getElementById('mediaPickerModal');
                if (mediaPicker) {
                    modal.close();
                    mediaPicker.showModal();
                }
            });
        }
    }
};

window.Editor = Editor;
