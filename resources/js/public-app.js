/*
 * آفرهای رایگان AI — public UI behaviour
 * Progressive enhancement only: every page works without JS.
 */
(function () {
    'use strict';

    var d = document;

    var csrfToken = (d.querySelector('meta[name="csrf-token"]') || {}).content || '';

    /* ------------------------------------------------------------------ *
     * Theme switcher (persisted in localStorage, applied before paint)
     * ------------------------------------------------------------------ */
    (function theme() {
        var root = d.documentElement;
        var buttons = root.querySelectorAll('[data-theme-toggle]');
        if (!buttons.length) return;

        // Sync the icon/label of every toggle with the current theme
        function paint() {
            var isDark = root.getAttribute('data-theme') !== 'light';
            var isFa = root.getAttribute('lang') === 'fa';
            Array.prototype.forEach.call(buttons, function (btn) {
                var label = btn.querySelector('.theme-toggle__label');
                var svg = btn.querySelector('svg');
                if (label) label.textContent = isFa ? (isDark ? 'روشن' : 'تاریک') : (isDark ? 'Light' : 'Dark');
                if (svg) {
                    svg.innerHTML = isDark
                        ? '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>'
                        : '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
                }
            });
        }

        Array.prototype.forEach.call(buttons, function (btn) {
            btn.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
                root.setAttribute('data-theme', next);
                try { localStorage.setItem('theme', next); } catch (e) { /* noop */ }
                paint();
            });
        });
        paint();
    })();

    /* ------------------------------------------------------------------ *
     * Analytics beacons (offer_view / qr_scan / filter_use)
     * ------------------------------------------------------------------ */
    var beaconTimers = {};

    function beacon(type, offerId, payload) {
        try {
            fetch('/analytics/event', {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    type: type,
                    offer_id: offerId ? Number(offerId) : null,
                    payload: payload || {}
                })
            })['catch'](function () {
                /* analytics must never break the page */
            });
        } catch (error) {
            /* noop */
        }
    }

    function beaconDebounced(key, type, offerId, payload, delay) {
        window.clearTimeout(beaconTimers[key]);
        beaconTimers[key] = window.setTimeout(function () {
            beacon(type, offerId, payload);
        }, delay || 1200);
    }

    /* ------------------------------------------------------------------ *
     * Helpers
     * ------------------------------------------------------------------ */
    function el(tag, className, text) {
        var node = d.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined && text !== null && text !== '') {
            node.textContent = text;
        }
        return node;
    }

    function escapeSelector(value) {
        return (window.CSS && CSS.escape) ? CSS.escape(value) : String(value).replace(/["\\\]]/g, '\\$&');
    }

    function absoluteUrl(value) {
        try {
            return new URL(value, window.location.origin).href;
        } catch (error) {
            return value;
        }
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve) {
            var area = d.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            d.body.appendChild(area);
            area.select();
            try {
                d.execCommand('copy');
            } catch (error) {
                /* noop */
            }
            area.remove();
            resolve();
        });
    }

    function flashCopied(node, fallbackLabel) {
        var original = node.getAttribute('aria-label') || fallbackLabel || '';
        node.classList.add('is-copied');
        node.setAttribute('aria-label', 'کپی شد ✓');
        window.setTimeout(function () {
            node.classList.remove('is-copied');
            if (original) {
                node.setAttribute('aria-label', original);
            }
        }, 1600);
    }

    function openDialog(dialog) {
        if (typeof dialog.showModal === 'function') {
            if (!dialog.open) {
                dialog.showModal();
            }
        } else {
            dialog.setAttribute('open', '');
        }
    }

    function closeDialog(dialog) {
        if (typeof dialog.close === 'function') {
            if (dialog.open) {
                dialog.close();
            }
        } else {
            dialog.removeAttribute('open');
        }
    }

    /* Close on backdrop click / Esc is native; only handle backdrop here. */
    d.querySelectorAll('dialog').forEach(function (dialog) {
        dialog.addEventListener('click', function (event) {
            if (event.target !== dialog) {
                return;
            }
            var rect = dialog.getBoundingClientRect();
            var inside = event.clientX >= rect.left && event.clientX <= rect.right &&
                event.clientY >= rect.top && event.clientY <= rect.bottom;
            if (!inside) {
                closeDialog(dialog);
            }
        });
    });

    d.addEventListener('click', function (event) {
        var closer = event.target.closest('[data-close-dialog]');
        if (closer) {
            var dialog = closer.closest('dialog');
            if (dialog) {
                closeDialog(dialog);
            }
        }
    });

    /* ------------------------------------------------------------------ *
     * Filter bar: auto-submit + filter_use beacons
     * ------------------------------------------------------------------ */
    var filterForm = d.getElementById('filter-form');

    if (filterForm) {
        var searchInput = filterForm.querySelector('input[name="q"]');
        var searchTimer = null;
        var initialValue = searchInput ? searchInput.value : '';
        var lastFilterBeacon = 0;

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(function () {
                    if (searchInput.value !== initialValue) {
                        filterForm.submit();
                    }
                }, 700);
            });
        }

        filterForm.addEventListener('change', function (event) {
            var target = event.target;
            if (!target.matches('input[type="checkbox"], select')) {
                return;
            }
            filterForm.submit();
        });

        filterForm.addEventListener('change', function (event) {
            var target = event.target;
            if (!target.name) {
                return;
            }
            var now = Date.now();
            if (now - lastFilterBeacon > 2000) {
                lastFilterBeacon = now;
                beaconDebounced('filter_use', 'filter_use', null, {
                    param: target.name.replace('[]', ''),
                    value: String(target.value).slice(0, 120)
                }, 400);
            }
        });
    }

    /* Header search keeps the active filter state. */
    var headerSearch = d.querySelector('.header-search');

    if (headerSearch) {
        headerSearch.addEventListener('submit', function () {
            var params = new URLSearchParams(window.location.search);
            params.delete('q');
            params.forEach(function (value, key) {
                if (headerSearch.elements[key]) {
                    return;
                }
                var input = d.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                headerSearch.appendChild(input);
            });
        });
    }

    /* ------------------------------------------------------------------ *
     * Copy link
     * ------------------------------------------------------------------ */
    d.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-copy]');
        if (!trigger) {
            return;
        }
        var value = absoluteUrl(trigger.getAttribute('data-copy'));
        copyText(value).then(function () {
            flashCopied(trigger, 'کپی لینک');
        });
    });

    /* ------------------------------------------------------------------ *
     * Quick view dialog
     * ------------------------------------------------------------------ */
    var quickview = d.getElementById('quickview');
    var viewedOffers = {};
    var currentOffer = null;

    function metaRow(term, value) {
        var row = el('div');
        row.appendChild(el('dt', null, term));
        var dd = el('dd');
        dd.appendChild(el('span', null, value));
        row.appendChild(dd);
        return row;
    }

    function renderQuickview(data) {
        currentOffer = data;

        d.getElementById('quickview-provider').textContent =
            [data.provider, data.category].filter(Boolean).join(' · ');
        d.getElementById('quickview-title').textContent = data.title || '';

        var badges = d.getElementById('quickview-badges');
        badges.textContent = '';
        if (data.badge) {
            badges.appendChild(el('span', 'badge badge--amount', data.badge));
        }
        if (data.tier) {
            badges.appendChild(el('span', 'badge', data.tier));
        }
        if (data.pricing) {
            badges.appendChild(el('span', 'badge', data.pricing));
        }
        if (data.status) {
            badges.appendChild(el('span', 'badge', data.status));
        }

        d.getElementById('quickview-desc').textContent = data.description || 'توضیحی ثبت نشده است.';

        var note = d.getElementById('quickview-note');
        if (data.note) {
            note.hidden = false;
            note.textContent = data.note;
        } else {
            note.hidden = true;
        }

        var meta = d.getElementById('quickview-meta');
        meta.textContent = '';
        if (data.no_verification) {
            meta.appendChild(metaRow('تأیید هویت', 'بدون تأیید هویت'));
        } else if (data.verification && data.verification.length) {
            meta.appendChild(metaRow('تأیید هویت', data.verification.join('، ')));
        }
        if (data.access && data.access.length) {
            meta.appendChild(metaRow('روش دسترسی', data.access.join('، ')));
        }
        if (data.models && data.models.length) {
            meta.appendChild(metaRow('مدل‌های مرتبط', data.models.join('، ')));
        }
        if (data.last_verified) {
            meta.appendChild(metaRow('آخرین بررسی دستی', data.last_verified));
        }

        var freeSection = d.getElementById('quickview-freemodels');
        var freeChips = d.getElementById('quickview-freemodels-chips');
        freeChips.textContent = '';
        if (data.free_models && data.free_models.length) {
            freeSection.hidden = false;
            data.free_models.forEach(function (model) {
                freeChips.appendChild(el('code', 'chip chip--model', model));
            });
            d.getElementById('quickview-freemodels-note').textContent =
                data.last_crawl ? 'آخرین بررسی خودکار: ' + data.last_crawl : '';
        } else {
            freeSection.hidden = true;
        }

        var versionsSection = d.getElementById('quickview-versions');
        var versionsList = d.getElementById('quickview-versions-list');
        versionsList.textContent = '';
        if (data.versions && data.versions.length) {
            versionsSection.hidden = false;
            data.versions.forEach(function (version) {
                var item = el('li', 'versions__item');
                item.appendChild(el('span', 'versions__date', version.date));
                item.appendChild(el('span', 'chip', version.source));
                item.appendChild(el('span', 'versions__summary', version.summary));
                versionsList.appendChild(item);
            });
        } else {
            versionsSection.hidden = true;
        }

        d.getElementById('quickview-go').href = data.go_url || '#';
        d.getElementById('quickview-detail').href = data.detail_url || '#';
    }

    d.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-quickview]');
        if (!trigger || !quickview) {
            return;
        }

        var slug = trigger.getAttribute('data-slug') || '';
        var island = d.querySelector('script[data-quickview-data="' + escapeSelector(slug) + '"]');
        if (!island) {
            return;
        }

        var data;
        try {
            data = JSON.parse(island.textContent);
        } catch (error) {
            return;
        }

        renderQuickview(data);
        openDialog(quickview);

        if (!viewedOffers[data.slug]) {
            viewedOffers[data.slug] = true;
            beacon('offer_view', trigger.getAttribute('data-offer-id'), { slug: data.slug, source: 'quickview' });
        }
    });

    var quickviewCopy = d.getElementById('quickview-copy');
    if (quickviewCopy) {
        quickviewCopy.addEventListener('click', function () {
            if (!currentOffer) {
                return;
            }
            copyText(absoluteUrl(currentOffer.go_url)).then(function () {
                flashCopied(quickviewCopy, 'کپی URL');
            });
        });
    }

    var quickviewQr = d.getElementById('quickview-qr');
    if (quickviewQr) {
        quickviewQr.addEventListener('click', function () {
            if (!currentOffer) {
                return;
            }
            openQr(currentOffer.go_url, currentOffer.slug, null);
        });
    }

    /* ------------------------------------------------------------------ *
     * QR dialog (qrcode-generator, vendored)
     * ------------------------------------------------------------------ */
    var qrDialog = d.getElementById('qr-dialog');
    var qrCanvas = d.getElementById('qr-canvas');
    var scannedOffers = {};
    var currentQr = null;

    function renderQr(text) {
        if (!qrCanvas || !window.qrcode) {
            return false;
        }

        var qr = null;
        var attempts = [0, 4, 6, 8, 10]; // 0 = auto where supported, then explicit types

        for (var i = 0; i < attempts.length && !qr; i += 1) {
            try {
                var candidate = window.qrcode(attempts[i], 'M');
                candidate.addData(text);
                candidate.make();
                qr = candidate;
            } catch (error) {
                qr = null;
            }
        }

        if (!qr) {
            return false;
        }

        var count = qr.getModuleCount();
        var margin = 2;
        var target = 240;
        var cell = Math.max(2, Math.floor(target / (count + margin * 2)));
        var size = cell * (count + margin * 2);

        qrCanvas.width = size;
        qrCanvas.height = size;

        var context = qrCanvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, size, size);
        context.fillStyle = '#0b0f17';

        for (var row = 0; row < count; row += 1) {
            for (var col = 0; col < count; col += 1) {
                if (qr.isDark(row, col)) {
                    context.fillRect((col + margin) * cell, (row + margin) * cell, cell, cell);
                }
            }
        }

        return true;
    }

    function openQr(path, slug, offerId) {
        if (!qrDialog) {
            return;
        }

        var url = absoluteUrl(path);
        currentQr = { url: url, slug: slug || 'offer' };

        d.getElementById('qr-url').textContent = url;
        d.getElementById('qr-open').href = url;

        var ok = renderQr(url);
        d.getElementById('qr-error').hidden = ok;
        qrCanvas.hidden = !ok;

        openDialog(qrDialog);

        var key = slug || url;
        if (!scannedOffers[key]) {
            scannedOffers[key] = true;
            beacon('qr_scan', offerId, { slug: slug });
        }
    }

    d.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-qr]');
        if (!trigger) {
            return;
        }
        openQr(
            trigger.getAttribute('data-qr-url'),
            trigger.getAttribute('data-offer-slug'),
            trigger.getAttribute('data-offer-id')
        );
    });

    var qrDownload = d.getElementById('qr-download');
    if (qrDownload) {
        qrDownload.addEventListener('click', function () {
            if (!currentQr || !qrCanvas) {
                return;
            }
            try {
                var link = d.createElement('a');
                link.download = 'qr-' + currentQr.slug + '.png';
                link.href = qrCanvas.toDataURL('image/png');
                d.body.appendChild(link);
                link.click();
                link.remove();
            } catch (error) {
                /* canvas export unavailable */
            }
        });
    }

    var qrCopy = d.getElementById('qr-copy');
    if (qrCopy) {
        qrCopy.addEventListener('click', function () {
            if (!currentQr) {
                return;
            }
            copyText(currentQr.url).then(function () {
                flashCopied(qrCopy, 'کپی URL');
            });
        });
    }

    /* ------------------------------------------------------------------ *
     * Report dialog (fetch POST)
     * ------------------------------------------------------------------ */
    var reportDialog = d.getElementById('report-dialog');
    var reportForm = d.getElementById('report-form');

    d.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-report]');
        if (!trigger || !reportDialog || !reportForm) {
            return;
        }

        reportForm.action = '/offers/' + encodeURIComponent(trigger.getAttribute('data-offer-slug') || '') + '/report';
        d.getElementById('report-offer-name').textContent =
            trigger.getAttribute('data-offer-name') || trigger.getAttribute('data-offer-slug') || '';

        var status = d.getElementById('report-status');
        status.className = 'dialog__status';
        status.textContent = '';

        var submitButton = d.getElementById('report-submit');
        submitButton.disabled = false;

        openDialog(reportDialog);
    });

    if (reportForm) {
        reportForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var status = d.getElementById('report-status');
            var submitButton = d.getElementById('report-submit');
            submitButton.disabled = true;
            status.className = 'dialog__status';
            status.textContent = 'در حال ارسال…';

            fetch(reportForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: new FormData(reportForm)
            }).then(function (response) {
                return response.json()['catch'](function () {
                    return {};
                }).then(function (json) {
                    return { ok: response.ok, json: json };
                });
            }).then(function (result) {
                if (result.ok && result.json && result.json.ok) {
                    status.className = 'dialog__status dialog__status--ok';
                    status.textContent = result.json.message || 'ممنون! گزارش شما ثبت شد.';
                    window.setTimeout(function () {
                        closeDialog(reportDialog);
                    }, 1800);
                } else {
                    status.className = 'dialog__status dialog__status--error';
                    var message = 'ارسال انجام نشد. دوباره تلاش کن.';
                    if (result.json && result.json.errors) {
                        var firstKey = Object.keys(result.json.errors)[0];
                        if (firstKey && result.json.errors[firstKey][0]) {
                            message = result.json.errors[firstKey][0];
                        }
                    }
                    status.textContent = message;
                    submitButton.disabled = false;
                }
            })['catch'](function () {
                status.className = 'dialog__status dialog__status--error';
                status.textContent = 'ارسال انجام نشد. اتصال اینترنت را بررسی کن.';
                submitButton.disabled = false;
            });
        });
    }

    /* ------------------------------------------------------------------ *
     * Filter FAB + drawer (mobile) — open/close the slide-up filter panel
     * ------------------------------------------------------------------ */
    (function filterDrawer() {
        var fab = d.querySelector('[data-filter-fab-toggle]');
        var drawer = d.getElementById('filter-drawer');
        if (!fab || !drawer) return;

        var closeButtons = drawer.querySelectorAll('[data-filter-close]');

        function open() {
            drawer.classList.add('is-open');
            drawer.removeAttribute('hidden');
            fab.setAttribute('aria-expanded', 'true');
            // Focus the close button for accessibility
            var closeBtn = drawer.querySelector('.filter-drawer__close');
            if (closeBtn) closeBtn.focus();
        }

        function close() {
            drawer.classList.remove('is-open');
            drawer.setAttribute('hidden', '');
            fab.setAttribute('aria-expanded', 'false');
            fab.focus();
        }

        fab.addEventListener('click', function () {
            if (drawer.classList.contains('is-open')) {
                close();
            } else {
                open();
            }
        });

        Array.prototype.forEach.call(closeButtons, function (btn) {
            btn.addEventListener('click', close);
        });

        // Close on Escape
        d.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
                close();
            }
        });

        // Close when clicking the scrim
        var scrim = drawer.querySelector('.filter-drawer__scrim');
        if (scrim) {
            scrim.addEventListener('click', close);
        }
    })();
})();
