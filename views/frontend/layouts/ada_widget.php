<?php
// views/frontend/layouts/ada_widget.php — ADA & WCAG 2.1 AA Compliance Suite
$basePath = (isset($base)) ? $base : (function_exists('base_url') ? base_url() : '');
?>
<!-- ═══════════════ ADA ACCESSIBILITY SUITE ═══════════════ -->
<div id="adaAccessibilityWrapper" role="region" aria-label="Accessibility Tools">

    <!-- 1. Keyboard Skip Link -->
    <a href="#main-content" class="ada-skip-link" id="adaSkipLink">
        <i class="fas fa-forward" aria-hidden="true"></i> Skip to main content
    </a>

    <!-- 2. Reading Guide Ruler -->
    <div id="adaReadingGuide" aria-hidden="true"></div>

    <!-- 3. Reading Mask Overlays -->
    <div id="adaReadingMaskTop" aria-hidden="true"></div>
    <div id="adaReadingMaskBottom" aria-hidden="true"></div>

    <!-- 4. Floating Trigger Button -->
    <button type="button" id="adaTriggerBtn" aria-label="Open Accessibility Menu (Alt+A)" aria-haspopup="dialog" aria-expanded="false" title="Accessibility Tools (Alt+A)">
        <i class="fas fa-universal-access" aria-hidden="true"></i>
        <span class="ada-badge">ADA</span>
    </button>

    <!-- 5. Modal Overlay -->
    <div id="adaModalOverlay" aria-hidden="true"></div>

    <!-- 6. ADA Control Center Panel -->
    <aside id="adaPanel" role="dialog" aria-modal="true" aria-label="Accessibility Menu" aria-hidden="true">
        
        <!-- Header -->
        <div class="ada-panel-header">
            <div class="ada-panel-title">
                <i class="fas fa-universal-access" style="font-size:24px; color:#E9C46A;" aria-hidden="true"></i>
                <div>
                    <h3>Accessibility Suite</h3>
                    <span>WCAG 2.1 Level AA Compliant</span>
                </div>
            </div>
            <button type="button" class="ada-close-btn" id="adaCloseBtn" aria-label="Close Accessibility Menu">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="ada-panel-body">

            <!-- Section: Accessibility Profiles -->
            <div>
                <div class="ada-section-title">
                    <i class="fas fa-user-check" aria-hidden="true"></i> Accessibility Profiles
                </div>
                <div class="ada-grid-2">
                    <button type="button" class="ada-btn-card" data-ada-action="profile-vision" aria-pressed="false">
                        <i class="fas fa-eye ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Vision Impaired</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="profile-dyslexia" aria-pressed="false">
                        <i class="fas fa-font ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Dyslexia Friendly</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="profile-adhd" aria-pressed="false">
                        <i class="fas fa-brain ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">ADHD & Cognitive</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="profile-seizure" aria-pressed="false">
                        <i class="fas fa-shield-alt ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Seizure Safe</span>
                    </button>
                </div>
            </div>

            <!-- Section: Content & Reading Adjustments -->
            <div>
                <div class="ada-section-title">
                    <i class="fas fa-text-height" aria-hidden="true"></i> Content & Typography
                </div>
                
                <!-- Font Resizer -->
                <div class="ada-counter-strip" style="margin-bottom:12px;">
                    <span style="font-size:12px; font-weight:600; color:#334155;">Adjust Font Size</span>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <button type="button" class="ada-counter-btn" id="adaFontDec" aria-label="Decrease Font Size">−</button>
                        <span class="ada-counter-val" id="adaFontVal">100%</span>
                        <button type="button" class="ada-counter-btn" id="adaFontInc" aria-label="Increase Font Size">+</button>
                    </div>
                </div>

                <div class="ada-grid-3">
                    <button type="button" class="ada-btn-card" data-ada-action="dyslexic-font" aria-pressed="false">
                        <i class="fas fa-book-reader ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Dyslexic Font</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="line-height" aria-pressed="false">
                        <i class="fas fa-arrows-alt-v ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Line Height</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="letter-spacing" aria-pressed="false">
                        <i class="fas fa-arrows-alt-h ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Letter Spacing</span>
                    </button>
                </div>

                <div class="ada-grid-2" style="margin-top:10px;">
                    <button type="button" class="ada-btn-card" data-ada-action="highlight-links" aria-pressed="false">
                        <i class="fas fa-link ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Highlight Links</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="highlight-headings" aria-pressed="false">
                        <i class="fas fa-heading ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Highlight Titles</span>
                    </button>
                </div>
            </div>

            <!-- Section: Color & Contrast -->
            <div>
                <div class="ada-section-title">
                    <i class="fas fa-palette" aria-hidden="true"></i> Color & Contrast
                </div>
                <div class="ada-grid-3">
                    <button type="button" class="ada-btn-card" data-ada-action="high-contrast" aria-pressed="false">
                        <i class="fas fa-adjust ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">High Contrast</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="invert-colors" aria-pressed="false">
                        <i class="fas fa-moon ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Invert Colors</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="monochrome" aria-pressed="false">
                        <i class="fas fa-tint-slash ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Monochrome</span>
                    </button>
                </div>
                <div class="ada-grid-2" style="margin-top:10px;">
                    <button type="button" class="ada-btn-card" data-ada-action="high-saturation" aria-pressed="false">
                        <i class="fas fa-sun ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">High Saturation</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="light-contrast" aria-pressed="false">
                        <i class="fas fa-circle ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Light Contrast</span>
                    </button>
                </div>
            </div>

            <!-- Section: Reading Aids & Orientation -->
            <div>
                <div class="ada-section-title">
                    <i class="fas fa-compass" aria-hidden="true"></i> Reading Tools & Audio
                </div>
                <div class="ada-grid-3">
                    <button type="button" class="ada-btn-card" data-ada-action="reading-guide" aria-pressed="false">
                        <i class="fas fa-ruler-horizontal ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Reading Guide</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="reading-mask" aria-pressed="false">
                        <i class="fas fa-vector-square ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Reading Mask</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="text-to-speech" aria-pressed="false">
                        <i class="fas fa-volume-up ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Screen Reader</span>
                    </button>
                </div>
                <div class="ada-grid-3" style="margin-top:10px;">
                    <button type="button" class="ada-btn-card" data-ada-action="big-cursor" aria-pressed="false">
                        <i class="fas fa-mouse-pointer ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Big Cursor</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="stop-animations" aria-pressed="false">
                        <i class="fas fa-pause-circle ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Stop Motion</span>
                    </button>
                    <button type="button" class="ada-btn-card" data-ada-action="hide-images" aria-pressed="false">
                        <i class="fas fa-image ada-card-icon" aria-hidden="true"></i>
                        <span class="ada-card-title">Hide Images</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="ada-panel-footer">
            <button type="button" class="ada-reset-btn" id="adaResetBtn">
                <i class="fas fa-undo" aria-hidden="true"></i> Reset Settings
            </button>
            <a href="javascript:void(0)" class="ada-statement-link" id="adaStatementBtn">
                Accessibility Statement
            </a>
        </div>
    </aside>

    <!-- 7. ADA Accessibility Statement Modal -->
    <div id="adaStatementModal" style="display:none; position:fixed; inset:0; background:rgba(15, 76, 92, 0.7); z-index:999999; align-items:center; justify-content:center; padding:16px;" role="dialog" aria-modal="true" aria-labelledby="adaStatementTitle">
        <div style="background:#FFFFFF; border-radius:16px; max-width:650px; width:100%; max-height:85vh; overflow-y:auto; padding:28px; box-shadow:0 20px 50px rgba(0,0,0,0.3); font-family:'Inter', sans-serif; position:relative;">
            <button type="button" onclick="document.getElementById('adaStatementModal').style.display='none'" style="position:absolute; right:20px; top:20px; background:#f1f5f9; border:none; width:34px; height:34px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; color:#475569;">
                <i class="fas fa-times"></i>
            </button>
            <h2 id="adaStatementTitle" style="font-family:'Cinzel', serif; color:#0F4C5C; font-size:22px; margin-bottom:14px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-universal-access" style="color:#E9C46A;"></i> ADA Accessibility Statement
            </h2>
            <div style="font-size:13.5px; line-height:1.7; color:#334155;">
                <p style="margin-bottom:12px;"><strong>Laguna Vibe Atelier</strong> is committed to ensuring digital accessibility for all users, including individuals with disabilities. We continually improve our user experience and apply relevant accessibility standards compliant with <strong>WCAG 2.1 Level AA</strong> and the <strong>Americans with Disabilities Act (ADA)</strong>.</p>
                
                <h4 style="color:#0F4C5C; margin:16px 0 6px; font-size:15px;">Measures to Support Accessibility</h4>
                <ul style="padding-left:20px; margin-bottom:12px;">
                    <li>Integrated on-page Accessibility Suite with customizable visual, reading, and motor aids.</li>
                    <li>Semantic HTML5 structure, ARIA landmarks, and keyboard navigable checkout flow.</li>
                    <li>High contrast compliance, screen-reader text alternatives, and scalable fonts.</li>
                    <li>Continuous audits and compatibility checks across assistive technologies.</li>
                </ul>

                <h4 style="color:#0F4C5C; margin:16px 0 6px; font-size:15px;">Feedback & Assistance</h4>
                <p style="margin-bottom:12px;">If you experience any difficulty accessing any content on our website, or require assistance with an order, please reach out to our dedicated support team:</p>
                <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:12px 16px; margin-top:8px;">
                    <div><strong>Email:</strong> <a href="mailto:support@lagunavibe.com" style="color:#0F4C5C;">support@lagunavibe.com</a></div>
                    <div style="margin-top:4px;"><strong>Toll-Free Phone:</strong> +1 (888) 420-1965</div>
                    <div style="margin-top:4px;"><strong>Standard:</strong> Web Content Accessibility Guidelines (WCAG) 2.1 Level AA</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════ ADA ENGINE JAVASCRIPT ═══════════════ -->
<script>
(function() {
    'use strict';

    const STORAGE_KEY = 'lvb_ada_preferences';
    const body = document.body;
    const html = document.documentElement;

    let adaState = {
        fontSize: 100,
        activeActions: {},
        activeProfile: null
    };

    // Load saved preferences
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            adaState = Object.assign(adaState, JSON.parse(saved));
        }
    } catch(e) {}

    // Elements
    const triggerBtn = document.getElementById('adaTriggerBtn');
    const panel = document.getElementById('adaPanel');
    const overlay = document.getElementById('adaModalOverlay');
    const closeBtn = document.getElementById('adaCloseBtn');
    const resetBtn = document.getElementById('adaResetBtn');
    const statementBtn = document.getElementById('adaStatementBtn');
    const statementModal = document.getElementById('adaStatementModal');
    const fontInc = document.getElementById('adaFontInc');
    const fontDec = document.getElementById('adaFontDec');
    const fontVal = document.getElementById('adaFontVal');
    const readingGuide = document.getElementById('adaReadingGuide');
    const readingMaskTop = document.getElementById('adaReadingMaskTop');
    const readingMaskBottom = document.getElementById('adaReadingMaskBottom');

    // ── Open / Close Panel ──
    function openAdaPanel() {
        if (!panel) return;
        panel.classList.add('active');
        if (overlay) overlay.classList.add('active');
        panel.setAttribute('aria-hidden', 'false');
        if (triggerBtn) triggerBtn.setAttribute('aria-expanded', 'true');
    }

    function closeAdaPanel() {
        if (!panel) return;
        panel.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        panel.setAttribute('aria-hidden', 'true');
        if (triggerBtn) triggerBtn.setAttribute('aria-expanded', 'false');
    }

    if (triggerBtn) triggerBtn.addEventListener('click', openAdaPanel);
    if (closeBtn) closeBtn.addEventListener('click', closeAdaPanel);
    if (overlay) overlay.addEventListener('click', closeAdaPanel);

    // Global keyboard shortcut: Alt + A or Option + A
    document.addEventListener('keydown', function(e) {
        if (e.altKey && (e.key === 'a' || e.key === 'A')) {
            e.preventDefault();
            if (panel && panel.classList.contains('active')) {
                closeAdaPanel();
            } else {
                openAdaPanel();
            }
        }
        if (e.key === 'Escape') {
            if (panel && panel.classList.contains('active')) closeAdaPanel();
            if (statementModal && statementModal.style.display === 'flex') statementModal.style.display = 'none';
        }
    });

    if (statementBtn) {
        statementBtn.addEventListener('click', function() {
            closeAdaPanel();
            if (statementModal) statementModal.style.display = 'flex';
        });
    }

    // ── Action Handlers ──
    const ACTION_MAP = {
        'dyslexic-font':      { target: 'body', cls: 'ada-dyslexic-font' },
        'line-height':        { target: 'body', cls: 'ada-line-height' },
        'letter-spacing':     { target: 'body', cls: 'ada-letter-spacing' },
        'highlight-links':    { target: 'body', cls: 'ada-highlight-links' },
        'highlight-headings': { target: 'body', cls: 'ada-highlight-headings' },
        'high-contrast':      { target: 'body', cls: 'ada-high-contrast', mutex: ['invert-colors', 'monochrome', 'light-contrast'] },
        'invert-colors':      { target: 'html', cls: 'ada-invert', mutex: ['high-contrast', 'monochrome', 'light-contrast'] },
        'monochrome':         { target: 'html', cls: 'ada-monochrome', mutex: ['high-contrast', 'invert-colors', 'high-saturation'] },
        'high-saturation':    { target: 'html', cls: 'ada-high-saturation', mutex: ['monochrome'] },
        'light-contrast':     { target: 'body', cls: 'ada-light-contrast', mutex: ['high-contrast', 'invert-colors'] },
        'stop-animations':    { target: 'body', cls: 'ada-stop-animations' },
        'hide-images':        { target: 'body', cls: 'ada-hide-images' },
        'big-cursor':         { target: 'body', cls: 'ada-cursor-big-dark' },
        'reading-guide':      { custom: toggleReadingGuide },
        'reading-mask':       { custom: toggleReadingMask },
        'text-to-speech':     { custom: toggleTextToSpeech }
    };

    function applyAction(actionKey, forceState) {
        const config = ACTION_MAP[actionKey];
        if (!config) return;

        const isCurrentlyActive = !!adaState.activeActions[actionKey];
        const nextState = (typeof forceState !== 'undefined') ? forceState : !isCurrentlyActive;

        if (nextState && config.mutex) {
            config.mutex.forEach(mKey => {
                if (adaState.activeActions[mKey]) {
                    applyAction(mKey, false);
                }
            });
        }

        adaState.activeActions[actionKey] = nextState;

        if (config.cls) {
            const el = (config.target === 'html') ? html : body;
            if (nextState) {
                el.classList.add(config.cls);
            } else {
                el.classList.remove(config.cls);
            }
        }

        if (config.custom) {
            config.custom(nextState);
        }

        // Sync button UI
        const btn = document.querySelector(`[data-ada-action="${actionKey}"]`);
        if (btn) {
            if (nextState) {
                btn.classList.add('active');
                btn.setAttribute('aria-pressed', 'true');
            } else {
                btn.classList.remove('active');
                btn.setAttribute('aria-pressed', 'false');
            }
        }

        savePreferences();
    }

    // ── Profiles ──
    const PROFILES = {
        'profile-vision': ['high-contrast', 'highlight-headings', 'highlight-links'],
        'profile-dyslexia': ['dyslexic-font', 'line-height', 'letter-spacing', 'highlight-links'],
        'profile-adhd': ['reading-mask', 'stop-animations', 'highlight-headings'],
        'profile-seizure': ['stop-animations', 'monochrome']
    };

    function applyProfile(profileKey) {
        const profileActions = PROFILES[profileKey];
        if (!profileActions) return;

        const isCurrentlyActive = (adaState.activeProfile === profileKey);
        
        // Deactivate current profile if clicking again
        if (isCurrentlyActive) {
            profileActions.forEach(act => applyAction(act, false));
            adaState.activeProfile = null;
        } else {
            // Clear other profile buttons
            document.querySelectorAll('[data-ada-action^="profile-"]').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
            profileActions.forEach(act => applyAction(act, true));
            adaState.activeProfile = profileKey;
        }

        const pBtn = document.querySelector(`[data-ada-action="${profileKey}"]`);
        if (pBtn) {
            if (adaState.activeProfile === profileKey) {
                pBtn.classList.add('active');
                pBtn.setAttribute('aria-pressed', 'true');
            } else {
                pBtn.classList.remove('active');
                pBtn.setAttribute('aria-pressed', 'false');
            }
        }

        savePreferences();
    }

    // ── Font Resizer ──
    function setFontSize(val) {
        val = Math.max(100, Math.min(150, val));
        adaState.fontSize = val;

        // Clear existing font size classes
        body.classList.remove('ada-font-110', 'ada-font-120', 'ada-font-130', 'ada-font-140', 'ada-font-150');
        if (val > 100) {
            body.classList.add(`ada-font-${val}`);
        }

        if (fontVal) fontVal.textContent = `${val}%`;
        savePreferences();
    }

    if (fontInc) fontInc.addEventListener('click', () => setFontSize(adaState.fontSize + 10));
    if (fontDec) fontDec.addEventListener('click', () => setFontSize(adaState.fontSize - 10));

    // ── Reading Guide ──
    function toggleReadingGuide(enable) {
        if (!readingGuide) return;
        if (enable) {
            readingGuide.style.display = 'block';
            window.addEventListener('mousemove', onReadingGuideMove);
        } else {
            readingGuide.style.display = 'none';
            window.removeEventListener('mousemove', onReadingGuideMove);
        }
    }
    function onReadingGuideMove(e) {
        if (readingGuide) readingGuide.style.top = `${e.clientY - 6}px`;
    }

    // ── Reading Mask ──
    function toggleReadingMask(enable) {
        if (!readingMaskTop || !readingMaskBottom) return;
        if (enable) {
            readingMaskTop.style.display = 'block';
            readingMaskBottom.style.display = 'block';
            window.addEventListener('mousemove', onReadingMaskMove);
        } else {
            readingMaskTop.style.display = 'none';
            readingMaskBottom.style.display = 'none';
            window.removeEventListener('mousemove', onReadingMaskMove);
        }
    }
    function onReadingMaskMove(e) {
        const maskHeight = 110;
        const topEnd = Math.max(0, e.clientY - maskHeight / 2);
        const bottomStart = Math.min(window.innerHeight, e.clientY + maskHeight / 2);
        if (readingMaskTop) readingMaskTop.style.height = `${topEnd}px`;
        if (readingMaskBottom) {
            readingMaskBottom.style.top = `${bottomStart}px`;
            readingMaskBottom.style.height = `${window.innerHeight - bottomStart}px`;
        }
    }

    // ── Text to Speech (Screen Reader simulation) ──
    let ttsActive = false;
    function toggleTextToSpeech(enable) {
        ttsActive = enable;
        if (enable) {
            if ('speechSynthesis' in window) {
                speakText('Screen reader activated. Click on any text or highlight a sentence to read aloud.');
                document.addEventListener('mouseup', onTextToSpeechSelect);
            } else {
                alert('Text-to-Speech is not supported by your browser.');
            }
        } else {
            if ('speechSynthesis' in window) window.speechSynthesis.cancel();
            document.removeEventListener('mouseup', onTextToSpeechSelect);
        }
    }

    function onTextToSpeechSelect() {
        if (!ttsActive || !('speechSynthesis' in window)) return;
        const sel = window.getSelection().toString().trim();
        if (sel.length > 0) {
            speakText(sel);
        }
    }

    function speakText(txt) {
        window.speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(txt);
        u.rate = 1.0;
        u.pitch = 1.0;
        window.speechSynthesis.speak(u);
    }

    // ── Reset Settings ──
    function resetAllSettings() {
        // Reset body & html classes
        for (let key in ACTION_MAP) {
            const cfg = ACTION_MAP[key];
            if (cfg.cls) {
                body.classList.remove(cfg.cls);
                html.classList.remove(cfg.cls);
            }
            if (cfg.custom) cfg.custom(false);
        }
        body.classList.remove('ada-font-110', 'ada-font-120', 'ada-font-130', 'ada-font-140', 'ada-font-150');

        // Reset UI buttons
        document.querySelectorAll('.ada-btn-card').forEach(b => {
            b.classList.remove('active');
            b.setAttribute('aria-pressed', 'false');
        });

        adaState = {
            fontSize: 100,
            activeActions: {},
            activeProfile: null
        };
        if (fontVal) fontVal.textContent = '100%';
        try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    }

    if (resetBtn) resetBtn.addEventListener('click', resetAllSettings);

    // ── Save Preferences ──
    function savePreferences() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(adaState));
        } catch(e) {}
    }

    // ── Bind All Action Buttons ──
    document.querySelectorAll('[data-ada-action]').forEach(btn => {
        const action = btn.getAttribute('data-ada-action');
        btn.addEventListener('click', function() {
            if (action.startsWith('profile-')) {
                applyProfile(action);
            } else {
                applyAction(action);
            }
        });
    });

    // ── Apply Initial State on Load ──
    function init() {
        if (adaState.fontSize > 100) {
            setFontSize(adaState.fontSize);
        }
        for (let act in adaState.activeActions) {
            if (adaState.activeActions[act]) {
                applyAction(act, true);
            }
        }
        if (adaState.activeProfile) {
            const pBtn = document.querySelector(`[data-ada-action="${adaState.activeProfile}"]`);
            if (pBtn) {
                pBtn.classList.add('active');
                pBtn.setAttribute('aria-pressed', 'true');
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
