/* ============================================================
   BlePOS Unified Input Controller
   Handles floating labels, autofill, validation, custom select,
   password toggle, search debounce, money/phone formatting,
   textarea auto-grow/counter, and file dropzones.
   ============================================================ */

(function () {
    'use strict';

    const initialized = new WeakSet();

    function getFieldLabel(fieldEl) {
        return fieldEl.getAttribute('data-label') || 'This field';
    }

    function setError(fieldEl, controlEl, message) {
        fieldEl.classList.add('has-error');
        if (controlEl) controlEl.setAttribute('aria-invalid', 'true');
        const slot = fieldEl.querySelector('.ui-helper-slot');
        if (slot) {
            slot.innerHTML = `<span class="ui-error-text"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>${escapeHtml(message)}</span></span>`;
        }
    }

    function clearError(fieldEl, controlEl) {
        fieldEl.classList.remove('has-error');
        if (controlEl) controlEl.removeAttribute('aria-invalid');
        const slot = fieldEl.querySelector('.ui-helper-slot');
        if (slot && slot.querySelector('.ui-error-text')) {
            const origHelper = fieldEl.getAttribute('data-orig-helper');
            slot.innerHTML = origHelper ? `<span class="ui-helper-text">${escapeHtml(origHelper)}</span>` : '';
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function validateField(fieldEl) {
        const control = fieldEl.querySelector('input:not(.ui-select-search), select, textarea');
        if (!control || control.disabled) return true;

        const label = getFieldLabel(fieldEl);
        let rawVal = control.value;
        const isMoney = control.hasAttribute('data-ui-money');
        if (isMoney) {
            rawVal = rawVal.replace(/,/g, '').trim();
        } else if (control.type !== 'password' && control.type !== 'file') {
            rawVal = rawVal.trim();
        }

        // Required check
        if (control.required) {
            if (control.type === 'checkbox' || control.type === 'radio') {
                if (control.type === 'checkbox' && !control.checked) {
                    setError(fieldEl, control, `${label} is required`);
                    return false;
                }
                if (control.type === 'radio' && control.name) {
                    const form = control.closest('form') || document;
                    const checked = form.querySelector(`input[type="radio"][name="${CSS.escape(control.name)}"]:checked`);
                    if (!checked) {
                        setError(fieldEl, control, `${label} is required`);
                        return false;
                    }
                }
            } else if (control.type === 'file') {
                if (!control.files || control.files.length === 0) {
                    setError(fieldEl, control, `${label} is required`);
                    return false;
                }
            } else if (rawVal === '') {
                setError(fieldEl, control, `${label} is required`);
                return false;
            }
        }

        if (rawVal !== '') {
            // Email check
            if (control.type === 'email') {
                const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRe.test(rawVal)) {
                    setError(fieldEl, control, 'Enter a valid email address');
                    return false;
                }
            }

            // Phone check (Kenyan & international)
            if (control.hasAttribute('data-ui-tel')) {
                const digits = rawVal.replace(/[\s\-()]/g, '');
                if (!/^\+?\d{7,15}$/.test(digits)) {
                    setError(fieldEl, control, 'Enter a valid phone number');
                    return false;
                }
            }

            // Number / Money check
            if (control.type === 'number' || isMoney || control.hasAttribute('data-ui-number')) {
                const num = Number(rawVal);
                if (Number.isNaN(num)) {
                    setError(fieldEl, control, `Enter a valid number for ${label.toLowerCase()}`);
                    return false;
                }
                if (control.min !== '' && num < Number(control.min)) {
                    setError(fieldEl, control, `${label} must be at least ${control.min}`);
                    return false;
                }
                if (control.max !== '' && num > Number(control.max)) {
                    setError(fieldEl, control, `${label} must be at most ${control.max}`);
                    return false;
                }
            }

            // Pattern check
            if (control.pattern) {
                const re = new RegExp(`^(?:${control.pattern})$`);
                if (!re.test(rawVal)) {
                    setError(fieldEl, control, `${label} format is invalid`);
                    return false;
                }
            }
        }

        clearError(fieldEl, control);
        return true;
    }

    function syncHasValue(fieldEl, inputEl) {
        if (!inputEl) return;
        const hasVal = inputEl.value !== '' || inputEl.matches(':-webkit-autofill');
        fieldEl.classList.toggle('has-value', hasVal);
    }

    function initField(fieldEl) {
        if (initialized.has(fieldEl)) return;
        initialized.add(fieldEl);

        // Save original non-error helper text
        const existingHelper = fieldEl.querySelector('.ui-helper-text');
        if (existingHelper) {
            fieldEl.setAttribute('data-orig-helper', existingHelper.textContent);
        }

        const type = fieldEl.getAttribute('data-ui-input');
        const inputEl = fieldEl.querySelector('input:not(.ui-select-search), textarea');

        if (inputEl) {
            syncHasValue(fieldEl, inputEl);

            // Detect browser autofill animation
            inputEl.addEventListener('animationstart', function (e) {
                if (e.animationName === 'uiAutofillStart') {
                    fieldEl.classList.add('has-value');
                }
            });

            inputEl.addEventListener('input', function () {
                syncHasValue(fieldEl, inputEl);
                // Re-validate immediately only if already in error state
                if (fieldEl.classList.contains('has-error')) {
                    validateField(fieldEl);
                }
            });

            inputEl.addEventListener('blur', function () {
                syncHasValue(fieldEl, inputEl);
                validateField(fieldEl);
            });
        }

        // ── Password toggle ────────────────────────────────────
        if (type === 'password') {
            const toggleBtn = fieldEl.querySelector('.ui-pw-toggle');
            if (toggleBtn && inputEl) {
                toggleBtn.addEventListener('click', function () {
                    const isHidden = inputEl.type === 'password';
                    inputEl.type = isHidden ? 'text' : 'password';
                    toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
                    }
                });
            }
        }

        // ── Search input ───────────────────────────────────────
        if (type === 'search' && inputEl) {
            const clearBtn = fieldEl.querySelector('.ui-search-clear');
            let debounceTimer = null;

            function updateClearBtn() {
                if (clearBtn) {
                    clearBtn.hidden = inputEl.value.length === 0;
                }
            }

            updateClearBtn();

            inputEl.addEventListener('input', function () {
                updateClearBtn();
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    inputEl.dispatchEvent(new CustomEvent('ui:search', {
                        bubbles: true,
                        detail: { value: inputEl.value }
                    }));
                }, 300);
            });

            function clearSearch() {
                inputEl.value = '';
                updateClearBtn();
                syncHasValue(fieldEl, inputEl);
                inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                inputEl.focus();
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', clearSearch);
            }

            inputEl.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && inputEl.value !== '') {
                    e.preventDefault();
                    clearSearch();
                }
            });
        }

        // ── Number & Money ─────────────────────────────────────
        if ((type === 'number' || type === 'money') && inputEl) {
            // Prevent wheel changing value
            inputEl.addEventListener('wheel', function (e) {
                if (document.activeElement === inputEl) {
                    e.preventDefault();
                }
            }, { passive: false });

            // Block letters on keydown
            inputEl.addEventListener('keydown', function (e) {
                if (e.ctrlKey || e.metaKey || e.altKey) return;
                const allowedKeys = [
                    'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                    'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'
                ];
                if (allowedKeys.includes(e.key)) return;
                if (e.key === '.' && !inputEl.value.includes('.')) return;
                if (e.key === '-' && inputEl.selectionStart === 0 && !inputEl.value.includes('-')) return;
                if (!/^[0-9]$/.test(e.key)) {
                    e.preventDefault();
                }
            });

            if (inputEl.hasAttribute('data-ui-money')) {
                function formatMoneyDisplay() {
                    const clean = inputEl.value.replace(/,/g, '').trim();
                    if (clean === '' || Number.isNaN(Number(clean))) return;
                    const parts = clean.split('.');
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    inputEl.value = parts.join('.');
                    syncHasValue(fieldEl, inputEl);
                }

                formatMoneyDisplay();

                inputEl.addEventListener('focus', function () {
                    inputEl.value = inputEl.value.replace(/,/g, '');
                });

                inputEl.addEventListener('blur', formatMoneyDisplay);
            }
        }

        // ── Phone formatting ───────────────────────────────────
        if (type === 'tel' && inputEl) {
            inputEl.addEventListener('blur', function () {
                const digits = inputEl.value.replace(/[\s\-()]/g, '');
                if (/^0[17]\d{8}$/.test(digits)) {
                    inputEl.value = digits.replace(/^(\d{4})(\d{3})(\d{3})$/, '$1 $2 $3');
                } else if (/^\+254[17]\d{8}$/.test(digits)) {
                    inputEl.value = digits.replace(/^(\+254)(\d{3})(\d{3})(\d{3})$/, '$1 $2 $3 $4');
                }
                syncHasValue(fieldEl, inputEl);
            });
        }

        // ── Textarea auto-grow & counter ───────────────────────
        if (type === 'textarea' && inputEl) {
            const counter = fieldEl.querySelector('.ui-char-count');
            const maxLen = counter ? parseInt(counter.getAttribute('data-max'), 10) : 0;

            function updateTextarea() {
                inputEl.style.height = 'auto';
                const nextH = Math.min(Math.max(inputEl.scrollHeight, 80), 220);
                inputEl.style.height = nextH + 'px';
                inputEl.style.overflowY = inputEl.scrollHeight > 220 ? 'auto' : 'hidden';

                if (counter && maxLen > 0) {
                    const len = inputEl.value.length;
                    counter.textContent = `${len} / ${maxLen}`;
                    counter.classList.toggle('is-limit', len >= maxLen);
                }
            }

            updateTextarea();
            inputEl.addEventListener('input', updateTextarea);
        }

        // ── Custom Select Dropdown ─────────────────────────────
        if (type === 'select') {
            initCustomSelect(fieldEl);
        }

        // ── File Dropzone ──────────────────────────────────────
        if (type === 'file') {
            initFileDropzone(fieldEl);
        }

        // ── Checkbox / Radio / Toggle ──────────────────────────
        if (type === 'checkbox' || type === 'radio' || type === 'toggle') {
            const choiceNative = fieldEl.querySelector('.ui-choice-native');
            if (choiceNative) {
                choiceNative.addEventListener('change', function () {
                    if (fieldEl.classList.contains('has-error')) {
                        validateField(fieldEl);
                    }
                });
            }
        }
    }

    function initCustomSelect(fieldEl) {
        const nativeSelect = fieldEl.querySelector('.ui-select-native');
        const trigger = fieldEl.querySelector('.ui-select-trigger');
        const valueSpan = fieldEl.querySelector('.ui-select-value');
        const menu = fieldEl.querySelector('.ui-select-menu');
        const searchWrap = fieldEl.querySelector('.ui-select-search-wrap');
        const searchInput = fieldEl.querySelector('.ui-select-search');
        const optionsContainer = fieldEl.querySelector('.ui-select-options');

        if (!nativeSelect || !trigger || !menu || !optionsContainer) return;

        let activeIndex = -1;
        let typeAheadBuffer = '';
        let typeAheadTimer = null;

        function renderOptions(filter = '') {
            const opts = Array.from(nativeSelect.options);
            if (searchWrap) {
                searchWrap.hidden = opts.length <= 8;
            }

            const q = filter.trim().toLowerCase();
            optionsContainer.innerHTML = '';

            let visibleIndex = 0;
            opts.forEach(function (opt) {
                const text = opt.textContent.trim();
                if (q && !text.toLowerCase().includes(q)) return;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ui-select-option';
                btn.setAttribute('role', 'option');
                btn.setAttribute('data-value', opt.value);
                btn.setAttribute('data-index', String(visibleIndex));
                if (opt.disabled) btn.disabled = true;

                const isSelected = opt.selected;
                if (isSelected) {
                    btn.classList.add('is-selected');
                    btn.setAttribute('aria-selected', 'true');
                }

                btn.innerHTML = `<span>${escapeHtml(text)}</span>${isSelected ? '<i class="bi bi-check" aria-hidden="true"></i>' : ''}`;

                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    selectValue(opt.value);
                    closeMenu();
                    trigger.focus();
                });

                optionsContainer.appendChild(btn);
                visibleIndex++;
            });

            if (visibleIndex === 0) {
                const empty = document.createElement('div');
                empty.className = 'ui-select-empty';
                empty.textContent = 'No matching options';
                optionsContainer.appendChild(empty);
            }
        }

        function syncTriggerText() {
            const selected = nativeSelect.options[nativeSelect.selectedIndex];
            if (selected) {
                valueSpan.textContent = selected.textContent.trim();
                valueSpan.classList.toggle('is-placeholder', selected.value === '');
            } else {
                valueSpan.textContent = '';
            }
        }

        function selectValue(val) {
            nativeSelect.value = val;
            syncTriggerText();
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            if (fieldEl.classList.contains('has-error')) {
                validateField(fieldEl);
            }
        }

        function openMenu() {
            if (nativeSelect.disabled) return;
            // Close any other open select menus
            document.querySelectorAll('.ui-field.is-select.is-open').forEach(function (other) {
                if (other !== fieldEl) closeOtherMenu(other);
            });

            renderOptions('');
            menu.hidden = false;
            fieldEl.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            activeIndex = -1;

            if (searchWrap && !searchWrap.hidden && searchInput) {
                searchInput.value = '';
                setTimeout(() => searchInput.focus(), 20);
            }
        }

        function closeMenu() {
            fieldEl.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            setTimeout(function () {
                if (!fieldEl.classList.contains('is-open')) {
                    menu.hidden = true;
                }
            }, 150);
        }

        function closeOtherMenu(otherField) {
            otherField.classList.remove('is-open');
            const t = otherField.querySelector('.ui-select-trigger');
            const m = otherField.querySelector('.ui-select-menu');
            if (t) t.setAttribute('aria-expanded', 'false');
            if (m) m.hidden = true;
        }

        syncTriggerText();

        // Sync if nativeSelect changes programmatically
        nativeSelect.addEventListener('change', syncTriggerText);

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            if (fieldEl.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                renderOptions(searchInput.value);
                activeIndex = -1;
            });
            searchInput.addEventListener('keydown', handleMenuKeydown);
        }

        function handleMenuKeydown(e) {
            const items = Array.from(optionsContainer.querySelectorAll('.ui-select-option:not(:disabled)'));
            if (e.key === 'Escape') {
                e.preventDefault();
                closeMenu();
                trigger.focus();
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!fieldEl.classList.contains('is-open')) {
                    openMenu();
                    return;
                }
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                highlightItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                highlightItem(items);
            } else if (e.key === 'Enter' && fieldEl.classList.contains('is-open')) {
                if (activeIndex >= 0 && items[activeIndex]) {
                    e.preventDefault();
                    items[activeIndex].click();
                }
            } else if (e.key.length === 1 && document.activeElement !== searchInput) {
                typeAheadBuffer += e.key.toLowerCase();
                clearTimeout(typeAheadTimer);
                typeAheadTimer = setTimeout(() => { typeAheadBuffer = ''; }, 500);
                const matchIdx = items.findIndex(item => item.textContent.trim().toLowerCase().startsWith(typeAheadBuffer));
                if (matchIdx >= 0) {
                    activeIndex = matchIdx;
                    highlightItem(items);
                }
            }
        }

        function highlightItem(items) {
            items.forEach((el, idx) => el.classList.toggle('is-active', idx === activeIndex));
            if (items[activeIndex]) {
                items[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                if (!fieldEl.classList.contains('is-open')) {
                    e.preventDefault();
                    openMenu();
                    return;
                }
            }
            handleMenuKeydown(e);
        });

        document.addEventListener('click', function (e) {
            if (!fieldEl.contains(e.target) && fieldEl.classList.contains('is-open')) {
                closeMenu();
            }
        });
    }

    function initFileDropzone(fieldEl) {
        const dropzone = fieldEl.querySelector('.ui-file-dropzone');
        const nativeFile = fieldEl.querySelector('.ui-file-native');
        const emptyView = fieldEl.querySelector('.ui-file-empty');
        const selectedView = fieldEl.querySelector('.ui-file-selected');
        const fileNameSpan = fieldEl.querySelector('.ui-file-name');
        const clearBtn = fieldEl.querySelector('.ui-file-clear');

        if (!dropzone || !nativeFile) return;

        function updateFileDisplay() {
            if (nativeFile.files && nativeFile.files.length > 0) {
                fileNameSpan.textContent = nativeFile.files[0].name;
                emptyView.hidden = true;
                selectedView.hidden = false;
            } else {
                fileNameSpan.textContent = '';
                emptyView.hidden = false;
                selectedView.hidden = true;
            }
        }

        dropzone.addEventListener('click', function (e) {
            if (e.target.closest('.ui-file-clear') || nativeFile.disabled) return;
            nativeFile.click();
        });

        dropzone.addEventListener('keydown', function (e) {
            if ((e.key === 'Enter' || e.key === ' ') && !nativeFile.disabled) {
                e.preventDefault();
                nativeFile.click();
            }
        });

        nativeFile.addEventListener('change', function () {
            updateFileDisplay();
            if (fieldEl.classList.contains('has-error')) {
                validateField(fieldEl);
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                nativeFile.value = '';
                updateFileDisplay();
            });
        }

        ['dragenter', 'dragover'].forEach(evt => {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                if (!nativeFile.disabled) dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', function (e) {
            if (nativeFile.disabled) return;
            if (e.dataTransfer && e.dataTransfer.files.length > 0) {
                nativeFile.files = e.dataTransfer.files;
                nativeFile.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    function initForms() {
        document.querySelectorAll('form').forEach(function (form) {
            if (form.hasAttribute('data-ui-form-init')) return;
            if (!form.querySelector('[data-ui-input]')) return;

            form.setAttribute('data-ui-form-init', 'true');
            form.setAttribute('novalidate', 'novalidate');

            form.addEventListener('submit', function (e) {
                // Strip commas from money inputs before validation & submission
                form.querySelectorAll('input[data-ui-money="true"]').forEach(function (moneyEl) {
                    moneyEl.value = moneyEl.value.replace(/,/g, '').trim();
                });

                const fields = Array.from(form.querySelectorAll('[data-ui-input]'));
                let firstInvalid = null;

                fields.forEach(function (fieldEl) {
                    if (!validateField(fieldEl) && !firstInvalid) {
                        firstInvalid = fieldEl;
                    }
                });

                if (firstInvalid) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const focusTarget = firstInvalid.querySelector('input:not(.ui-select-search), textarea, .ui-select-trigger, .ui-file-dropzone');
                    if (focusTarget) focusTarget.focus({ preventScroll: true });

                    firstInvalid.classList.remove('ui-shake');
                    void firstInvalid.offsetWidth;
                    firstInvalid.classList.add('ui-shake');
                    setTimeout(() => firstInvalid.classList.remove('ui-shake'), 260);
                }
            });
        });
    }

    function initAll() {
        document.querySelectorAll('[data-ui-input]').forEach(initField);
        initForms();
    }

    window.BleInputInit = initAll;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Re-check autofill shortly after load
    setTimeout(function () {
        document.querySelectorAll('[data-ui-input] input, [data-ui-input] textarea').forEach(function (el) {
            const field = el.closest('[data-ui-input]');
            if (field) syncHasValue(field, el);
        });
    }, 150);

    // Observe dynamic DOM additions (e.g., journal lines, POS cart rows)
    const observer = new MutationObserver(function (mutations) {
        let shouldInit = false;
        for (const m of mutations) {
            if (m.addedNodes.length > 0) {
                shouldInit = true;
                break;
            }
        }
        if (shouldInit) initAll();
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
})();

/* ============================================================
   BlePOS Light / Dark Mode Theme Controller
   ============================================================ */
(function () {
    'use strict';

    const STORAGE_KEY = 'blepos_theme';

    function getPreferredTheme() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'light' || saved === 'dark') {
                return saved;
            }
        } catch (e) {}

        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        const btn = document.getElementById('theme-toggle');
        const icon = document.getElementById('theme-toggle-icon');
        if (btn) {
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            btn.setAttribute('data-tooltip', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        }
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-moon-stars' : 'bi bi-sun';
        }
    }

    function initThemeToggle() {
        applyTheme(getPreferredTheme());
        const btn = document.getElementById('theme-toggle');
        if (!btn || btn.dataset.themeBound === '1') return;
        btn.dataset.themeBound = '1';
        btn.addEventListener('click', function () {
            const current = document.documentElement.getAttribute('data-theme') || getPreferredTheme();
            const next = current === 'dark' ? 'light' : 'dark';
            try {
                localStorage.setItem(STORAGE_KEY, next);
            } catch (e) {}
            applyTheme(next);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeToggle);
    } else {
        initThemeToggle();
    }
})();

/* ============================================================
   BlePOS Motion System
   ============================================================ */
(function () {
    'use strict';

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const motionSelector = '.page-title, h1, .page-subtitle, .page-header > *, .stat-card, .chart-card, .form-card, .table-container, .table-wrap, .table-scroll, .card, .panel';
    let modalTrigger = null;
    let openModalNode = null;
    let countFrame = null;

    function animate(el, index = 0) {
        if (!(el instanceof Element) || el.dataset.motionDone === '1') return;
        el.dataset.motionDone = '1';
        el.style.setProperty('--motion-index', String(Math.min(index, 12)));
        el.classList.add('motion-enter');
    }

    function animatePage(root = document) {
        const main = root.querySelector?.('.page-body') || (root.matches?.('.page-body') ? root : null);
        if (!main) {
            const auth = document.querySelector('.auth-page');
            if (auth) { auth.querySelectorAll('.ath-auth-card, .ath-heading, .ath-subhead').forEach((el, i) => animate(el, i)); return; }
            document.querySelectorAll('.hero-nav, .hero-body, .feature-grid, .hero-footer').forEach((el, i) => animate(el, i));
            return;
        }
        let i = 0;
        main.querySelectorAll(motionSelector).forEach(el => {
            // Animate only outer blocks to avoid nested transforms fighting hover effects.
            if (el.closest('.page-body') !== main || el.parentElement.closest(motionSelector)) return;
            animate(el, i++);
        });
        main.querySelectorAll('tbody tr').forEach((row, index) => {
            if (index < 12) animate(row, Math.min(i + index, 12));
        });
    }

    function tokenMs(name) {
        const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        if (!value) return 0;
        return value.endsWith('ms') ? parseFloat(value) : parseFloat(value) * 1000;
    }

    function countUp(el) {
        if (el.children.length && !el.hasAttribute('data-count-up')) return;
        const original = el.textContent.trim();
        const match = original.match(/^([^\d-]*)(-?[\d,.]+)(.*)$/);
        if (original.includes('/') || (!el.hasAttribute('data-count-up') && !match)) return;
        const target = el.hasAttribute('data-count-up') ? Number(el.getAttribute('data-count-up')) : Number(match[2].replace(/,/g, ''));
        if (!Number.isFinite(target)) return;
        const prefix = match?.[1] || '';
        const suffix = match?.[3] || '';
        const decimals = (match?.[2].match(/\.(\d+)/)?.[1] || '').length;
        const startValue = 0;
        if (reducedMotion.matches) {
            const formatted = decimals ? target.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) : Math.round(target).toLocaleString();
            el.textContent = prefix + formatted + suffix;
            return;
        }
        window.setTimeout(() => {
            const start = performance.now();
            const step = now => {
                const t = Math.min(1, (now - start) / tokenMs('--dur-count'));
                const value = startValue + (target - startValue) * (1 - Math.pow(1 - t, 3));
                const formatted = decimals ? value.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) : Math.round(value).toLocaleString();
                el.textContent = prefix + formatted + suffix;
                if (t < 1) countFrame = requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        }, reducedMotion.matches ? 0 : tokenMs('--dur-base'));
    }

    function modalDuration(el) {
        const raw = getComputedStyle(el).transitionDuration.split(',')[0].trim();
        return raw.endsWith('ms') ? parseFloat(raw) : parseFloat(raw) * 1000;
    }

    function focusables(modal) {
        return [...modal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
            .filter(el => el.offsetParent !== null);
    }

    function openModal(modal) {
        if (!(modal instanceof HTMLElement)) return;
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        if (modal._motionCloseTimer) clearTimeout(modal._motionCloseTimer);
        modalTrigger = document.activeElement;
        openModalNode = modal;
        modal.style.display = 'flex';
        void modal.offsetWidth;
        modal.classList.remove('motion-closing');
        modal.classList.add('motion-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('motion-modal-open');
        const panel = modal.querySelector('[role="dialog"]') || modal.firstElementChild;
        if (panel && !panel.hasAttribute('role')) panel.setAttribute('role', 'dialog');
        if (panel && !panel.hasAttribute('aria-modal')) panel.setAttribute('aria-modal', 'true');
        requestAnimationFrame(() => (focusables(panel)[0] || panel)?.focus?.({ preventScroll: true }));
    }

    function closeModal(modal = openModalNode) {
        if (!(modal instanceof HTMLElement) || modal.style.display === 'none') return;
        if (modal._motionResolve) { const resolve = modal._motionResolve; modal._motionResolve = null; resolve(false); }
        modal.classList.remove('motion-open');
        modal.classList.add('motion-closing');
        modal.setAttribute('aria-hidden', 'true');
        modal._motionCloseTimer = setTimeout(() => {
            if (!modal.classList.contains('motion-open')) {
                modal.style.display = 'none';
                modal.classList.remove('motion-closing');
                if (openModalNode === modal) {
                    openModalNode = null;
                    document.body.classList.remove('motion-modal-open');
                    if (modalTrigger?.isConnected) modalTrigger.focus({ preventScroll: true });
                }
            }
        }, reducedMotion.matches ? 0 : modalDuration(modal) || tokenMs('--dur-base'));
    }

    function confirm(message) {
        let modal = document.getElementById('motion-confirm-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'motion-confirm-modal';
            modal.className = 'modal-overlay';
            modal.setAttribute('aria-hidden', 'true');
            const panel = document.createElement('section');
            panel.className = 'card modal-box';
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-modal', 'true');
            panel.setAttribute('aria-label', 'Confirm action');
            panel.tabIndex = -1;
            const text = document.createElement('p');
            text.dataset.confirmMessage = '';
            text.style.whiteSpace = 'pre-line';
            const actions = document.createElement('div');
            actions.className = 'motion-confirm-actions';
            const cancel = document.createElement('button');
            cancel.type = 'button'; cancel.className = 'btn btn-secondary'; cancel.textContent = 'Cancel';
            cancel.dataset.confirmCancel = '';
            const accept = document.createElement('button');
            accept.type = 'button'; accept.className = 'btn'; accept.textContent = 'Confirm';
            accept.dataset.confirmAccept = '';
            actions.append(cancel, accept); panel.append(text, actions); modal.append(panel); document.body.append(modal);
            cancel.addEventListener('click', () => closeModal(modal));
            accept.addEventListener('click', () => {
                const resolve = modal._motionResolve;
                modal._motionResolve = null;
                closeModal(modal);
                resolve?.(true);
            });
        }
        modal.querySelector('[data-confirm-message]').textContent = String(message);
        return new Promise(resolve => { modal._motionResolve = resolve; openModal(modal); });
    }

    window.BleMotion = { openModal, closeModal, animatePage, confirm, duration: tokenMs };

    function initOtpForm(form) {
        if (form.dataset.otpInitialized === '1') return;
        form.dataset.otpInitialized = '1';
        const digits = [...form.querySelectorAll('[data-otp-digit]')];
        const value = form.querySelector('[data-otp-value]');
        const digitGroup = form.querySelector('[data-otp-digits]');
        const recoveryGroup = form.querySelector('[data-otp-recovery]');
        const recoveryInput = form.querySelector('[data-otp-recovery-input]');
        const toggle = form.querySelector('[data-otp-toggle]');
        if (!digits.length || !value || !digitGroup || !recoveryGroup || !recoveryInput || !toggle) return;

        let recoveryMode = false;
        const syncDigits = () => { value.value = digits.map(input => input.value).join(''); };
        const setMode = useRecovery => {
            recoveryMode = useRecovery;
            digitGroup.hidden = useRecovery;
            recoveryGroup.hidden = !useRecovery;
            digits.forEach(input => { input.required = !useRecovery; });
            recoveryInput.required = useRecovery;
            toggle.textContent = useRecovery ? 'Use an authenticator code' : 'Use a recovery code';
            value.value = '';
            (useRecovery ? recoveryInput : digits[0]).focus();
        };

        digits.forEach((input, index) => {
            input.addEventListener('input', () => {
                const entered = input.value.replace(/\D/g, '');
                if (entered.length > 1) {
                    [...entered].slice(0, digits.length - index).forEach((digit, offset) => {
                        digits[index + offset].value = digit;
                    });
                    const next = Math.min(index + entered.length, digits.length - 1);
                    digits[next].focus();
                } else {
                    input.value = entered.slice(-1);
                    if (input.value && index < digits.length - 1) digits[index + 1].focus();
                }
                syncDigits();
            });
            input.addEventListener('keydown', event => {
                if (event.key === 'Backspace' && !input.value && index > 0) {
                    digits[index - 1].value = '';
                    digits[index - 1].focus();
                    syncDigits();
                } else if (event.key === 'ArrowLeft' && index > 0) {
                    event.preventDefault(); digits[index - 1].focus();
                } else if (event.key === 'ArrowRight' && index < digits.length - 1) {
                    event.preventDefault(); digits[index + 1].focus();
                }
            });
            input.addEventListener('paste', event => {
                const pasted = event.clipboardData?.getData('text').replace(/\D/g, '') || '';
                if (!pasted) return;
                event.preventDefault();
                [...pasted].slice(0, digits.length).forEach((digit, offset) => { digits[offset].value = digit; });
                digits[Math.min(pasted.length, digits.length) - 1]?.focus();
                syncDigits();
            });
        });
        toggle.addEventListener('click', () => setMode(!recoveryMode));
        form.addEventListener('submit', event => {
            if (recoveryMode) {
                value.value = recoveryInput.value.trim();
                if (!value.value) { event.preventDefault(); recoveryInput.focus(); }
            } else {
                syncDigits();
                if (value.value.length !== digits.length) {
                    event.preventDefault();
                    digits.find(input => !input.value)?.focus();
                }
            }
        });
    }

    function initActivityPeriodFilter(root = document) {
        root.querySelectorAll?.('[data-period-filter]').forEach(filter => {
            const select = filter.querySelector('select');
            const form = filter.closest('form');
            if (!select || !form || select.dataset.periodFilterReady === '1') return;
            select.dataset.periodFilterReady = '1';
            const sync = () => form.querySelectorAll('[data-period-field]').forEach(field => {
                field.hidden = field.dataset.periodField !== select.value;
            });
            select.addEventListener('change', sync);
            sync();
        });
    }

    function init() {
        animatePage();
        initActivityPeriodFilter();
        document.querySelectorAll('[data-otp-form]').forEach(initOtpForm);
        document.querySelectorAll('[data-count-up], .stat-value').forEach(countUp);
        document.querySelectorAll('[data-progress]').forEach(el => {
            const progress = Math.max(0, Math.min(100, Number(el.dataset.progress) || 0)) / 100;
            el.style.setProperty('--motion-progress', String(progress));
            requestAnimationFrame(() => el.classList.add('is-ready'));
        });
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            const panel = modal.querySelector('[role="dialog"]') || modal.firstElementChild;
            if (panel && !panel.hasAttribute('tabindex')) panel.setAttribute('tabindex', '-1');
        });

        document.addEventListener('keydown', event => {
            if (!openModalNode) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal(openModalNode);
            } else if (event.key === 'Tab') {
                const panel = openModalNode.querySelector('[role="dialog"]') || openModalNode;
                const list = focusables(panel);
                if (!list.length) { event.preventDefault(); panel.focus(); return; }
                if (event.shiftKey && document.activeElement === list[0]) { event.preventDefault(); list.at(-1).focus(); }
                else if (!event.shiftKey && document.activeElement === list.at(-1)) { event.preventDefault(); list[0].focus(); }
            }
        });
        document.addEventListener('submit', event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-motion-confirm')) return;
            if (form.dataset.motionConfirmed === '1') { delete form.dataset.motionConfirmed; return; }
            event.preventDefault();
            confirm(form.dataset.motionConfirm).then(confirmed => { if (confirmed) { form.dataset.motionConfirmed = '1'; form.requestSubmit(event.submitter || undefined); } });
        }, true);
        document.addEventListener('click', event => {
            const confirmedControl = event.target.closest?.('[data-motion-confirm]');
            if (confirmedControl && confirmedControl.dataset.motionProceed !== '1') {
                event.preventDefault();
                event.stopImmediatePropagation();
                confirm(confirmedControl.dataset.motionConfirm).then(confirmed => {
                    if (!confirmed) return;
                    if (confirmedControl instanceof HTMLButtonElement && confirmedControl.type === 'submit' && confirmedControl.form) {
                        confirmedControl.form.requestSubmit(confirmedControl);
                    } else if (confirmedControl instanceof HTMLFormElement) {
                        confirmedControl.dataset.motionConfirmed = '1';
                        confirmedControl.requestSubmit();
                    } else {
                        confirmedControl.dataset.motionProceed = '1';
                        confirmedControl.click();
                        delete confirmedControl.dataset.motionProceed;
                    }
                });
                return;
            }
            if (openModalNode && event.target === openModalNode) closeModal(openModalNode);
            const link = event.target.closest?.('a[href]');
            if (link && !event.defaultPrevented && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey && link.target !== '_blank') {
                const url = new URL(link.href, location.href);
                const main = document.querySelector('.page-body');
                if (main && url.origin === location.origin && url.pathname !== location.pathname) {
                    main.classList.add('motion-exit');
                    event.preventDefault();
                    setTimeout(() => { location.href = link.href; }, tokenMs('--dur-exit'));
                }
            }
        });

        // Dynamic content gets a short entrance when it arrives; cap row stagger at twelve.
        let newRows = 0;
        const observer = new MutationObserver(mutations => {
            for (const mutation of mutations) {
                if (mutation.type === 'attributes' && mutation.target.matches?.('[data-count-up]')) {
                    countUp(mutation.target);
                }
                mutation.addedNodes.forEach(node => {
                    if (!(node instanceof Element) || node.matches('.modal-overlay, [data-motion-overlay]')) return;
                    initActivityPeriodFilter(node.parentElement || document);
                    if (node.matches('[data-otp-form]')) initOtpForm(node);
                    node.querySelectorAll?.('[data-otp-form]').forEach(initOtpForm);
                    if (node.matches('[data-count-up]')) countUp(node);
                    if (node.matches(motionSelector)) animate(node, newRows++);
                    node.querySelectorAll?.(motionSelector).forEach(el => animate(el, newRows++));
                    if (node.matches('tbody tr')) { if (newRows < 12) animate(node, newRows++); }
                    node.querySelectorAll?.('tbody tr').forEach(row => { if (newRows < 12) animate(row, newRows++); });
                });
            }
        });
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-count-up'] });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
