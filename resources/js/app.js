import './bootstrap';
import { attachPhilippineAddressLookups } from './philippine-address-lookup';
import { attachLandingChat } from './landing-chat';
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

let pdfWorkerReady = null;

const configurePdfWorker = () => {
    if (pdfWorkerReady) {
        return pdfWorkerReady;
    }

    pdfWorkerReady = fetch(pdfWorker, { credentials: 'same-origin' })
        .then((response) => {
            if (!response.ok) {
                return;
            }

            return response.text().then((source) => {
                const blob = new Blob([source], { type: 'text/javascript' });
                pdfjsLib.GlobalWorkerOptions.workerSrc = URL.createObjectURL(blob);
            });
        })
        .catch(() => {
            pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;
        });

    return pdfWorkerReady;
};

const bufferLooksLikePdf = (data) => {
    const prefix = new TextDecoder('latin1').decode(data.slice(0, 1024));

    return prefix.includes('%PDF');
};

const loadPdfDocument = async (url) => {
    await configurePdfWorker();

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/pdf,*/*' },
    });

    if (!response.ok) {
        const error = new Error(`pdf-http-${response.status}`);
        error.status = response.status;
        throw error;
    }

    const data = await response.arrayBuffer();
    if (data.byteLength <= 4 || !bufferLooksLikePdf(data)) {
        const error = new Error('pdf-invalid-body');
        error.contentType = response.headers.get('content-type') || 'unknown';
        error.byteLength = data.byteLength;
        throw error;
    }

    try {
        return await pdfjsLib.getDocument({ data }).promise;
    } catch (parseError) {
        parseError.stage = 'pdfjs-parse';
        throw parseError;
    }
};

const dashboardThemeStorageKey = 'mcare-dashboard-theme';
const adminSidebarCollapsedStorageKey = 'mcare-admin-sidebar-collapsed';
const trainerSidebarCollapsedStorageKey = 'mcare-trainer-sidebar-collapsed';
const traineeSidebarCollapsedStorageKey = 'mcare-trainee-sidebar-collapsed';
const officialSidebarStorageKeys = {
    admin: adminSidebarCollapsedStorageKey,
    trainer: trainerSidebarCollapsedStorageKey,
    trainee: traineeSidebarCollapsedStorageKey,
};

const readDashboardTheme = () => {
    try {
        return window.localStorage.getItem(dashboardThemeStorageKey) === 'dark' ? 'dark' : 'light';
    } catch (error) {
        // Light is the safe default when storage is unavailable or blocked.
        return 'light';
    }
};

const applyDashboardTheme = (theme) => {
    const resolvedTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.dashboardTheme = resolvedTheme;
    document.documentElement.style.colorScheme = resolvedTheme;
};

applyDashboardTheme(readDashboardTheme());

// Sidebar collapse must survive back/forward cache restores and any errors
// thrown by unrelated DOMContentLoaded logic (e.g. the PDF viewer on lesson
// pages). Registering the click handler on `document` with delegation means
// the button keeps working even if it is re-rendered by a navigation swap,
// and re-applying the state on `pageshow` keeps bfcache-restored pages in
// sync so users no longer need Ctrl+Shift+R for the toggle to respond.
const resolveOfficialSidebarKey = () => {
    const shell = document.querySelector(
        '.universal-dashboard[data-dashboard-role="admin"], '
        + '.universal-dashboard[data-dashboard-role="trainer"], '
        + '.universal-dashboard[data-dashboard-role="trainee"]',
    );
    const role = shell?.dataset.dashboardRole;

    return {
        shell,
        storageKey: (role && officialSidebarStorageKeys[role]) || adminSidebarCollapsedStorageKey,
    };
};

const applyOfficialSidebarCollapsed = (collapsed) => {
    document.documentElement.classList.toggle('is-admin-sidebar-collapsed', collapsed);
    document.querySelectorAll('[data-dashboard-sidebar-collapse]').forEach((button) => {
        button.setAttribute('aria-expanded', String(!collapsed));
        const label = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        button.setAttribute('aria-label', label);
        button.title = label;
    });
};

const syncOfficialSidebarFromStorage = () => {
    const { shell, storageKey } = resolveOfficialSidebarKey();
    if (!shell) return;

    let collapsed = document.documentElement.classList.contains('is-admin-sidebar-collapsed');
    try {
        collapsed = window.localStorage.getItem(storageKey) === '1';
    } catch (error) {
        // Fall back to whatever the inline layout script decided.
    }

    applyOfficialSidebarCollapsed(collapsed);
};

// Delegated click handler survives bfcache restores and dynamic DOM swaps.
document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    const trigger = target.closest('[data-dashboard-sidebar-collapse]');
    if (!trigger) return;

    const { shell, storageKey } = resolveOfficialSidebarKey();
    if (!shell) return;

    const collapsed = !document.documentElement.classList.contains('is-admin-sidebar-collapsed');
    applyOfficialSidebarCollapsed(collapsed);

    try {
        window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
    } catch (error) {
        // Preference persistence is optional when storage is blocked.
    }
});

// Initial sync fires as soon as the DOM is parsed enough for the shell to
// exist. `DOMContentLoaded` guarantees the button is present, but we still
// re-sync on `pageshow` so a page restored from bfcache never shows a stale
// collapsed/expanded state.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncOfficialSidebarFromStorage, { once: true });
} else {
    syncOfficialSidebarFromStorage();
}
window.addEventListener('pageshow', syncOfficialSidebarFromStorage);

document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.remove('dashboard-navigating');
    attachPhilippineAddressLookups();
    attachLandingChat();

    // Account forms use one accessible toggle so temporary passwords are easy to verify without exposing them by default.
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const icon = button.querySelector('svg');
        if (!input) return;

        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            button.title = visible ? 'Hide password' : 'Show password';
            icon?.replaceChildren();
            if (icon) {
                icon.innerHTML = visible
                    ? '<path d="m3 3 18 18M10.6 6.1A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a18.5 18.5 0 0 1-3.2 3.8M6.2 6.3C3.5 8.1 2 12 2 12s3.5 6 10 6c1.4 0 2.6-.3 3.7-.8"/>'
                    : '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/>';
            }
        });
    });

    const sidebar = document.querySelector('[data-dashboard-sidebar]');
    const accountMenus = document.querySelectorAll('[data-dashboard-account]');
    const dashboardLinks = document.querySelectorAll('.dashboard-nav-link, .dashboard-mobile-link');
    const hashLinks = document.querySelectorAll('.dashboard-nav-link[href*="#"], .dashboard-mobile-link[href*="#"]');
    const themeToggleButtons = document.querySelectorAll('[data-dashboard-theme-toggle]');
    const prefetchLinks = document.querySelectorAll('a[data-dashboard-prefetch]');
    const trainingCalendars = document.querySelectorAll('[data-training-calendar]');
    const dashboardMain = document.querySelector('.dashboard-main');
    const protectedViewer = document.querySelector('[data-protected-module-viewer]');
    const securityEventUrl = document.querySelector('meta[name="dashboard-security-event-url"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const desktopDashboardMedia = window.matchMedia('(min-width: 1024px)');
    let navigationLocked = false;
    let navigationUnlockTimer = null;
    let navigationSpamReported = false;

    const publicMenu = document.querySelector('[data-public-menu]');
    const publicMenuOpen = document.querySelector('[data-public-menu-open]');
    const publicMenuDesktop = window.matchMedia('(min-width: 1024px)');
    const scrollHeaders = document.querySelectorAll('[data-header-scroll-border]');

    if (scrollHeaders.length) {
        let headerScrollTick = false;
        const syncHeaderScrollBorder = () => {
            const scrolled = window.scrollY > 8;
            scrollHeaders.forEach((header) => header.classList.toggle('is-scrolled', scrolled));
            headerScrollTick = false;
        };
        const onHeaderScroll = () => {
            if (headerScrollTick) return;
            headerScrollTick = true;
            window.requestAnimationFrame(syncHeaderScrollBorder);
        };

        syncHeaderScrollBorder();
        window.addEventListener('scroll', onHeaderScroll, { passive: true });
    }

    const setPublicMenuOpen = (open) => {
        if (!publicMenu || !publicMenuOpen) return;

        publicMenu.classList.toggle('is-open', open);
        publicMenu.setAttribute('aria-hidden', open ? 'false' : 'true');
        publicMenuOpen.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('public-menu-open', open);
    };

    publicMenuOpen?.addEventListener('click', () => setPublicMenuOpen(true));
    publicMenu?.querySelector('[data-public-menu-close]')?.addEventListener('click', () => setPublicMenuOpen(false));
    publicMenu?.querySelector('[data-public-menu-overlay]')?.addEventListener('click', () => setPublicMenuOpen(false));
    publicMenu?.querySelectorAll('[data-public-menu-link]').forEach((link) => {
        link.addEventListener('click', () => setPublicMenuOpen(false));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setPublicMenuOpen(false);
    });
    const syncPublicMenu = () => {
        if (publicMenuDesktop.matches) setPublicMenuOpen(false);
    };
    publicMenuDesktop.addEventListener('change', syncPublicMenu);

    document.querySelectorAll('[data-application-form]').forEach((form) => {
        const submitButton = form.querySelector('[data-application-submit]');
        if (!(submitButton instanceof HTMLButtonElement)) return;

        const requiredFields = () => Array.from(form.querySelectorAll('[required]'));

        const formIsReady = () => requiredFields().every((field) => {
            if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
                return true;
            }
            if (field.disabled) return true;
            if (field.type === 'checkbox' || field.type === 'radio') return field.checked;
            if (field.value.trim() === '') return false;
            if (field.type === 'email' && !field.value.trim().toLowerCase().endsWith('@gmail.com')) return false;
            return field.checkValidity();
        });

        const syncSubmit = () => {
            const ready = formIsReady();
            submitButton.disabled = !ready;
            submitButton.setAttribute('aria-disabled', ready ? 'false' : 'true');
        };

        form.addEventListener('input', syncSubmit);
        form.addEventListener('change', syncSubmit);
        form.addEventListener('submit', (event) => {
            if (formIsReady()) return;
            event.preventDefault();
            syncSubmit();
        });
        syncSubmit();
    });

    // Mobile dashboards keep the bottom bar compact while the More sheet
    // exposes every destination that is available in the desktop sidebar.
    document.querySelectorAll('[data-dashboard-mobile-menu-open]').forEach((button) => {
        const dialog = document.getElementById(button.dataset.dashboardMobileMenuOpen);
        if (!('HTMLDialogElement' in window) || !(dialog instanceof window.HTMLDialogElement)) return;

        button.addEventListener('click', () => {
            if (!dialog.open) dialog.showModal();
        });
    });

    document.querySelectorAll('[data-dashboard-mobile-menu]').forEach((dialog) => {
        dialog.querySelector('[data-dashboard-mobile-menu-close]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    // Successful action feedback stays long enough to read, pauses during
    // interaction, then leaves the interface without requiring another click.
    document.querySelectorAll('[data-auto-dismiss]').forEach((notice) => {
        const configuredDelay = Number(notice.dataset.autoDismiss || 5000);
        const delay = Number.isFinite(configuredDelay)
            ? Math.min(Math.max(configuredDelay, 2500), 15000)
            : 5000;
        let dismissTimer = null;
        let removeTimer = null;

        const dismiss = () => {
            if (!notice.isConnected || notice.classList.contains('is-dismissing')) return;

            notice.classList.add('is-dismissing');
            removeTimer = window.setTimeout(() => {
                notice.remove();
                const toastRegion = document.querySelector('[data-dashboard-toast-region]');
                if (
                    toastRegion
                    && !toastRegion.querySelector('[data-dashboard-toast]')
                    && typeof toastRegion.hidePopover === 'function'
                    && toastRegion.matches(':popover-open')
                ) {
                    toastRegion.hidePopover();
                }
            }, 300);
        };

        const pauseDismissal = () => window.clearTimeout(dismissTimer);
        const scheduleDismissal = () => {
            window.clearTimeout(dismissTimer);
            dismissTimer = window.setTimeout(dismiss, delay);
        };

        notice.querySelector('[data-dashboard-toast-dismiss]')?.addEventListener('click', dismiss);

        notice.addEventListener('mouseenter', pauseDismissal);
        notice.addEventListener('mouseleave', scheduleDismissal);
        notice.addEventListener('focusin', pauseDismissal);
        notice.addEventListener('focusout', scheduleDismissal);
        notice.addEventListener('transitionend', () => {
            if (!notice.classList.contains('is-dismissing')) return;

            window.clearTimeout(removeTimer);
            notice.remove();
        }, { once: true });

        scheduleDismissal();
    });

    // Shared native dialogs keep large creation forms outside the normal page flow.
    const dashboardDialogs = document.querySelectorAll('dialog[data-dashboard-dialog]');
    const dashboardDialogTriggers = new WeakMap();
    const openDashboardDialog = (dialog, trigger = document.activeElement) => {
        if (!dialog?.showModal || dialog.open) return;

        if (trigger instanceof HTMLElement) {
            dashboardDialogTriggers.set(dialog, trigger);
        }
        dialog.showModal();
        window.requestAnimationFrame(() => {
            dialog.querySelector('[autofocus], input:not([type="hidden"]), select, textarea, button')?.focus();
        });
    };

    document.querySelectorAll('[data-dashboard-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => {
            openDashboardDialog(document.getElementById(button.dataset.dashboardDialogOpen), button);
        });
    });

    dashboardDialogs.forEach((dialog) => {
        dialog.querySelectorAll('[data-dashboard-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (event) => {
            // Only a click on the native dialog backdrop may dismiss it.
            // Descendant controls (notably programmatic file-input clicks) can
            // report 0,0 coordinates and must not be mistaken for backdrop clicks.
            if (event.target !== dialog) return;

            const bounds = dialog.getBoundingClientRect();
            const outside = event.clientX < bounds.left || event.clientX > bounds.right
                || event.clientY < bounds.top || event.clientY > bounds.bottom;

            if (outside) dialog.close();
        });

        if (dialog.dataset.autoOpen === 'true') {
            openDashboardDialog(dialog);
        }

        dialog.addEventListener('close', () => {
            const trigger = dashboardDialogTriggers.get(dialog);
            if (trigger?.isConnected) {
                window.requestAnimationFrame(() => trigger.focus());
            }
            dashboardDialogTriggers.delete(dialog);
        });
    });

    document.querySelectorAll('[data-dashboard-dialog-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            window.setTimeout(() => {
                form.querySelectorAll('[data-action-button]').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('cursor-not-allowed', 'opacity-70');
                    button.textContent = form.dataset.submitLabel || 'Saving...';
                });
            }, 0);
        });
    });

    const formatCareerSmsDate = (value) => {
        const parts = String(value || '').split('-');
        if (parts.length !== 3) return 'TBA';

        const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        if (Number.isNaN(date.getTime())) return 'TBA';

        return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    };

    const selectedCareerSmsLabel = (field) => {
        if (! (field instanceof HTMLSelectElement) || ! field.value) return '';

        return field.options[field.selectedIndex]?.textContent?.trim() || '';
    };

    const careerSmsMessageFromForm = (form) => {
        const title = form.querySelector('[data-career-sms-field="title"]')?.value?.trim() || 'Career opportunity';
        const salary = form.querySelector('[data-career-sms-field="salary"]')?.value?.trim() || 'see Career Hub';
        const startField = form.querySelector('[data-career-sms-field="start"]');
        const age = form.querySelector('[data-career-sms-field="age"]')?.value?.trim();
        const care = [
            selectedCareerSmsLabel(form.querySelector('[data-career-sms-field="gender"]')),
            selectedCareerSmsLabel(form.querySelector('[data-career-sms-field="mobility"]')),
            age ? `age ${age}` : '',
        ].filter(Boolean);
        const parts = [
            `MCARE Career Hub: ${title}`,
            `Salary ${salary}`,
            `Start ${startField?.value ? formatCareerSmsDate(startField.value) : 'TBA'}`,
        ];

        if (care.length) parts.push(care.join(', '));
        parts.push('Open Career Hub for details.');

        return parts.join('. ');
    };

    document.querySelectorAll('[data-career-sms-form]').forEach((form) => {
        const preview = form.querySelector('[data-career-sms-preview-text]');
        const count = form.querySelector('[data-career-sms-preview-count]');
        if (! preview) return;

        const refreshCareerSmsPreview = () => {
            const message = careerSmsMessageFromForm(form);
            preview.textContent = message;
            if (count) count.textContent = String(message.length);
        };

        form.querySelectorAll('[data-career-sms-field]').forEach((field) => {
            field.addEventListener('input', refreshCareerSmsPreview);
            field.addEventListener('change', refreshCareerSmsPreview);
        });

        refreshCareerSmsPreview();
    });

    const reportClientSecurityEvent = (eventName) => {
        if (!securityEventUrl || !csrfToken) return;

        window.fetch(securityEventUrl, {
            method: 'POST',
            keepalive: true,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ event: eventName }),
        }).catch(() => {});
    };

    const updateThemeControls = () => {
        const isDark = document.documentElement.dataset.dashboardTheme === 'dark';

        themeToggleButtons.forEach((button) => {
            button.setAttribute('aria-pressed', String(isDark));
            const label = button.querySelector('[data-dashboard-theme-label]');
            const moonIcon = button.querySelector('[data-dashboard-theme-icon="moon"]');
            const sunIcon = button.querySelector('[data-dashboard-theme-icon="sun"]');

            if (label) label.textContent = isDark ? 'Light mode' : 'Night mode';
            moonIcon?.classList.toggle('hidden', isDark);
            sunIcon?.classList.toggle('hidden', !isDark);
        });
    };

    themeToggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.dashboardTheme === 'dark' ? 'light' : 'dark';
            applyDashboardTheme(nextTheme);

            try {
                // One shared key carries the user's choice across every role portal.
                window.localStorage.setItem(dashboardThemeStorageKey, nextTheme);
            } catch (error) {
                // The current page can still change theme when storage is disabled.
            }

            updateThemeControls();
        });
    });
    updateThemeControls();
    // Keep the account menu label/icon in sync when another tab changes the
    // shared preference. The page-level storage handler below updates the
    // document attribute; this listener updates controls without a reload.
    window.addEventListener('storage', (event) => {
        if (event.key === dashboardThemeStorageKey) updateThemeControls();
    });

    if (protectedViewer) {
        const notice = protectedViewer.querySelector('[data-protected-viewer-notice]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        let noticeTimer = null;

        const reportRestrictedAction = (eventName) => {
            const url = protectedViewer.dataset.securityEventUrl;
            if (!url || !csrfToken) return;

            window.fetch(url, {
                method: 'POST',
                keepalive: true,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ event: eventName }),
            }).catch(() => {});
        };

        const showRestrictedNotice = (message) => {
            if (!notice) return;
            notice.textContent = message;
            notice.classList.remove('hidden');
            window.clearTimeout(noticeTimer);
            noticeTimer = window.setTimeout(() => notice.classList.add('hidden'), 3500);
        };

        protectedViewer.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            showRestrictedNotice('Right-click is disabled inside the protected module viewer.');
            reportRestrictedAction('context_menu');
        });

        protectedViewer.addEventListener('dragstart', (event) => event.preventDefault());

        document.addEventListener('keydown', (event) => {
            if (!(event.ctrlKey || event.metaKey)) return;
            const key = event.key.toLowerCase();

            if (key === 'p' || key === 's') {
                event.preventDefault();
                showRestrictedNotice(key === 'p'
                    ? 'Printing is disabled for protected learning materials.'
                    : 'Saving protected learning materials is disabled.');
                reportRestrictedAction(key === 'p' ? 'print_shortcut' : 'save_shortcut');
            }
        });

        window.addEventListener('beforeprint', () => {
            document.documentElement.classList.add('protected-module-printing');
            reportRestrictedAction('before_print');
        });
        window.addEventListener('afterprint', () => document.documentElement.classList.remove('protected-module-printing'));
    }

    const initPdfCanvasViewer = (container) => {
        const url = container.dataset.pdfUrl;
        const canvas = container.querySelector('[data-pdf-canvas]');
        if (!url || !canvas) return;

        const ctx = canvas.getContext('2d');
        const prevBtn = container.querySelector('[data-pdf-prev]');
        const nextBtn = container.querySelector('[data-pdf-next]');
        const currentPageEl = container.querySelector('[data-pdf-current-page]');
        const totalPagesEl = container.querySelector('[data-pdf-total-pages]');
        const zoomLevelEl = container.querySelector('[data-pdf-zoom-level]');
        const zoomInBtn = container.querySelector('[data-pdf-zoom-in]');
        const zoomOutBtn = container.querySelector('[data-pdf-zoom-out]');
        const fitWidthBtn = container.querySelector('[data-pdf-fit-width]');
        const loadingEl = container.querySelector('[data-pdf-loading]');
        const containerWrapper = container.querySelector('[data-pdf-canvas-container]');
        const fitMode = container.dataset.pdfFitMode === 'page' ? 'page' : 'width';

        let pdfDoc = null;
        let pageNum = 1;
        let pageRendering = false;
        let pageNumPending = null;
        let scale = 1.25;
        let userAdjustedZoom = false;
        const minScale = 0.25;
        const maxScale = 3.0;

        const syncHorizontalPan = () => {
            if (!containerWrapper) return;

            window.requestAnimationFrame(() => {
                const extraX = containerWrapper.scrollWidth - containerWrapper.clientWidth;
                containerWrapper.scrollLeft = extraX > 1 ? Math.round(extraX / 2) : 0;
            });
        };

        const applyCanvasSize = (cssWidth, cssHeight) => {
            const outputScale = window.devicePixelRatio || 1;
            const width = Math.max(1, Math.floor(cssWidth));
            const height = Math.max(1, Math.floor(cssHeight));

            canvas.width = Math.floor(width * outputScale);
            canvas.height = Math.floor(height * outputScale);
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height}px`;
            canvas.style.maxWidth = 'none';
            canvas.style.maxHeight = 'none';
            canvas.style.aspectRatio = `${width} / ${height}`;

            const pageWrapper = canvas.closest('[data-pdf-page-wrapper]');
            if (pageWrapper) {
                pageWrapper.style.width = `${width}px`;
                pageWrapper.style.height = `${height}px`;
                pageWrapper.style.aspectRatio = `${width} / ${height}`;
            }

            return outputScale;
        };

        const renderPage = (num) => {
            pageRendering = true;
            if (loadingEl) loadingEl.style.display = 'flex';

            pdfDoc.getPage(num).then((page) => {
                const viewport = page.getViewport({ scale });
                const outputScale = applyCanvasSize(viewport.width, viewport.height);

                const transform = outputScale !== 1
                    ? [outputScale, 0, 0, outputScale, 0, 0]
                    : null;

                const renderContext = {
                    canvasContext: ctx,
                    transform: transform,
                    viewport: viewport,
                };

                const renderTask = page.render(renderContext);

                renderTask.promise.then(() => {
                    pageRendering = false;
                    if (loadingEl) loadingEl.style.display = 'none';
                    syncHorizontalPan();

                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                }).catch(() => {
                    pageRendering = false;
                    if (loadingEl) loadingEl.style.display = 'none';
                });
            }).catch(() => {
                pageRendering = false;
                if (loadingEl) loadingEl.style.display = 'none';
            });

            if (currentPageEl) currentPageEl.textContent = String(num);
            if (prevBtn) prevBtn.disabled = num <= 1;
            if (nextBtn) nextBtn.disabled = num >= (pdfDoc ? pdfDoc.numPages : 1);
            if (zoomLevelEl) zoomLevelEl.textContent = Math.round(scale * 100) + '%';
        };

        const queueRenderPage = (num) => {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        };

        const fitCurrentPage = () => {
            if (!pdfDoc || !containerWrapper) return;

            pdfDoc.getPage(pageNum).then((page) => {
                const unscaledViewport = page.getViewport({ scale: 1.0 });
                const styles = window.getComputedStyle(containerWrapper);
                const paddingX = (Number.parseFloat(styles.paddingLeft) || 0)
                    + (Number.parseFloat(styles.paddingRight) || 0);
                const paddingY = (Number.parseFloat(styles.paddingTop) || 0)
                    + (Number.parseFloat(styles.paddingBottom) || 0);
                const availableWidth = Math.max(0, containerWrapper.clientWidth - paddingX);
                const availableHeight = Math.max(0, containerWrapper.clientHeight - paddingY);
                if (!availableWidth || !unscaledViewport.width) {
                    scale = 1;
                    queueRenderPage(pageNum);
                    return;
                }

                const widthScale = availableWidth / unscaledViewport.width;
                const heightScale = availableHeight && unscaledViewport.height
                    ? availableHeight / unscaledViewport.height
                    : widthScale;
                const fittedScale = fitMode === 'page'
                    ? Math.min(widthScale, heightScale)
                    : widthScale;

                scale = Math.max(minScale, Math.min(maxScale, fittedScale));
                queueRenderPage(pageNum);
            });
        };

        const showPage = (nextPage) => {
            pageNum = nextPage;
            if (userAdjustedZoom) {
                queueRenderPage(pageNum);
                return;
            }
            fitCurrentPage();
        };

        prevBtn?.addEventListener('click', () => {
            if (pageNum <= 1) return;
            showPage(pageNum - 1);
        });

        nextBtn?.addEventListener('click', () => {
            if (!pdfDoc || pageNum >= pdfDoc.numPages) return;
            showPage(pageNum + 1);
        });

        zoomInBtn?.addEventListener('click', () => {
            if (scale >= maxScale) return;
            userAdjustedZoom = true;
            scale = Math.min(maxScale, scale + 0.25);
            queueRenderPage(pageNum);
        });

        zoomOutBtn?.addEventListener('click', () => {
            if (scale <= minScale) return;
            userAdjustedZoom = true;
            scale = Math.max(minScale, scale - 0.25);
            queueRenderPage(pageNum);
        });

        fitWidthBtn?.addEventListener('click', () => {
            userAdjustedZoom = false;
            fitCurrentPage();
        });

        container.addEventListener('contextmenu', (event) => event.preventDefault());
        container.addEventListener('dragstart', (event) => event.preventDefault());

        const swapToNativePdfViewer = (note) => {
            const pageWrapper = container.querySelector('[data-pdf-page-wrapper]');
            const toolbar = container.querySelector('.lms-module-pdf-toolbar');
            if (toolbar) toolbar.style.display = 'none';
            if (loadingEl) loadingEl.style.display = 'none';

            if (pageWrapper) {
                pageWrapper.innerHTML = `
                    <iframe
                        src="${url}#toolbar=0&navpanes=0"
                        title="Lesson document"
                        class="block h-[75vh] w-full min-w-[320px] border-0 bg-white"
                        loading="lazy"
                    ></iframe>
                    ${note ? `<p class="mt-2 text-center text-xs text-slate-300">${note}</p>` : ''}
                `;
            }
        };

        loadPdfDocument(url).then((pdf) => {
            pdfDoc = pdf;
            if (totalPagesEl) totalPagesEl.textContent = String(pdf.numPages);
            fitCurrentPage();
        }).catch((error) => {
            const status = error?.status;
            const contentType = error?.contentType;
            const detail = status
                ? `HTTP ${status}`
                : contentType
                    ? `content-type ${contentType}`
                    : (error?.message || 'canvas viewer error');

            if (status === 404 || status === 403 || status === 429) {
                if (loadingEl) {
                    const label = status === 404
                        ? 'The lesson file is not on this server yet. Re-upload the module on Hostinger.'
                        : status === 403
                            ? 'This lesson is no longer available on your account.'
                            : 'You opened this lesson too many times in a short window. Wait a minute and reload.';

                    loadingEl.innerHTML = `<div class="p-6 text-center text-sm text-red-300">${label}<br><span class="text-xs text-slate-400">${detail}</span></div>`;
                }
                return;
            }

            // Any other failure (pdf.js parse error, worker blocked, corrupted
            // bytes) falls back to the browser's built-in PDF renderer so the
            // trainee can still read the lesson.
            swapToNativePdfViewer(`Canvas viewer fallback active (${detail}).`);
        });

        if (containerWrapper && 'ResizeObserver' in window) {
            let resizeTimer = null;
            const resizeObserver = new ResizeObserver(() => {
                if (!pdfDoc || userAdjustedZoom) return;
                window.clearTimeout(resizeTimer);
                resizeTimer = window.setTimeout(fitCurrentPage, 120);
            });
            resizeObserver.observe(containerWrapper);
        }
    };

    document.querySelectorAll('[data-pdf-canvas-viewer]').forEach(initPdfCanvasViewer);

    document.querySelectorAll('[data-lesson-document]').forEach((root) => {
        const toggle = root.querySelector('[data-lesson-document-toggle]');
        const label = toggle?.querySelector('[data-lesson-document-toggle-label]');
        const panel = root.querySelector('[data-lesson-document-panel]');
        if (!toggle || !panel) {
            return;
        }

        const showLabel = 'Show lesson document';
        const hideLabel = 'Hide lesson document';

        const sync = (open) => {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (label) {
                label.textContent = open ? hideLabel : showLabel;
            }
            if (open) {
                window.requestAnimationFrame(() => {
                    window.dispatchEvent(new Event('resize'));
                });
            }
        };

        sync(root.dataset.lessonDocumentOpen !== 'false');
        toggle.addEventListener('click', () => sync(panel.hidden));
    });

    const syncSidebarAccessibility = () => {
        if (!sidebar) return;

        // Mobile uses the bottom navigation. The desktop sidebar stays available
        // and can be collapsed from the top bar without hiding it.
        const isInaccessible = ! desktopDashboardMedia.matches;
        sidebar.inert = isInaccessible;
        sidebar.toggleAttribute('aria-hidden', isInaccessible);
    };

    // The sidebar collapse toggle is now wired at module top-level via
    // event delegation (see the `syncOfficialSidebarFromStorage` block above)
    // so it survives bfcache restores and unrelated init errors.

    syncSidebarAccessibility();
    desktopDashboardMedia.addEventListener?.('change', syncSidebarAccessibility);

    // Shared admin/trainer/trainee calendar behavior. Date selection swaps the
    // complete day agenda in place, so sessions never need modal popups.
    trainingCalendars.forEach((calendar) => {
        const dayButtons = Array.from(calendar.querySelectorAll('[data-calendar-day]'));
        const agendaPanels = Array.from(calendar.querySelectorAll('[data-calendar-agenda]'));
        const agenda = calendar.querySelector('.training-calendar-agenda');

        const activateDate = (date, updateHistory = true) => {
            let selectedPanel = null;

            dayButtons.forEach((button) => {
                const isSelected = button.dataset.calendarDate === date;
                button.classList.toggle('is-selected', isSelected);
                button.setAttribute('aria-pressed', String(isSelected));
            });

            agendaPanels.forEach((panel) => {
                const isSelected = panel.dataset.calendarAgenda === date;
                panel.hidden = ! isSelected;
                panel.classList.remove('is-active');
                if (isSelected) selectedPanel = panel;
            });

            // Restart one short panel animation while keeping every event in
            // the selected day visible at the same time.
            if (selectedPanel) {
                window.requestAnimationFrame(() => selectedPanel.classList.add('is-active'));
            }

            if (updateHistory) {
                try {
                    const nextUrl = new URL(window.location.href);
                    nextUrl.searchParams.set('date', date);
                    window.history.replaceState({}, '', nextUrl);
                } catch (error) {
                    // Calendar selection remains fully usable without History API access.
                }
            }
        };

        dayButtons.forEach((button, index) => {
            button.addEventListener('click', () => {
                if (button.dataset.calendarMonthUrl) {
                    window.location.assign(button.dataset.calendarMonthUrl);
                    return;
                }

                activateDate(button.dataset.calendarDate);

                if (window.matchMedia('(max-width: 720px)').matches && agenda) {
                    agenda.scrollIntoView({
                        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                        block: 'start',
                    });
                }
            });

            button.addEventListener('keydown', (event) => {
                const offset = {
                    ArrowLeft: -1,
                    ArrowRight: 1,
                    ArrowUp: -7,
                    ArrowDown: 7,
                }[event.key];

                if (offset === undefined) return;
                const target = dayButtons[index + offset];
                if (! target) return;

                event.preventDefault();
                target.focus();
                if (! target.dataset.calendarMonthUrl) {
                    activateDate(target.dataset.calendarDate);
                }
            });
        });

        activateDate(calendar.dataset.initialDate, false);
    });

    document.querySelectorAll('[data-training-chart]').forEach((plot) => {
        const tooltip = plot.querySelector('[data-training-chart-tooltip]');
        if (!tooltip) {
            return;
        }

        const svg = plot.querySelector('svg');

        const hideTooltip = () => {
            tooltip.classList.remove('is-visible');
            tooltip.hidden = true;
        };

        const showTooltip = (dot) => {
            const label = document.createElement('p');
            label.className = 'admin-training-chart-tooltip-label';
            label.textContent = dot.getAttribute('data-chart-label') || '';

            const enrolledRow = document.createElement('p');
            enrolledRow.className = 'admin-training-chart-tooltip-row';
            const enrolledName = document.createElement('span');
            enrolledName.textContent = 'Active';
            const enrolledValue = document.createElement('strong');
            enrolledValue.textContent = dot.getAttribute('data-chart-enrolled') || '0';
            enrolledRow.append(enrolledName, enrolledValue);

            const passingRow = document.createElement('p');
            passingRow.className = 'admin-training-chart-tooltip-row';
            const passingName = document.createElement('span');
            passingName.textContent = 'Graduate';
            const passingValue = document.createElement('strong');
            passingValue.textContent = dot.getAttribute('data-chart-passing') || '0';
            passingRow.append(passingName, passingValue);

            tooltip.replaceChildren(label, enrolledRow, passingRow);

            const plotRect = plot.getBoundingClientRect();
            const svgRect = svg ? svg.getBoundingClientRect() : plotRect;
            const vb = svg?.viewBox?.baseVal;
            const cx = parseFloat(dot.getAttribute('cx'));
            const cy = parseFloat(dot.getAttribute('cy'));

            if (vb && vb.width > 0 && vb.height > 0) {
                tooltip.style.left = `${svgRect.left - plotRect.left + (cx / vb.width) * svgRect.width}px`;
                tooltip.style.top = `${svgRect.top - plotRect.top + (cy / vb.height) * svgRect.height}px`;
            } else {
                const dotRect = dot.getBoundingClientRect();
                tooltip.style.left = `${dotRect.left - plotRect.left + dotRect.width / 2}px`;
                tooltip.style.top = `${dotRect.top - plotRect.top}px`;
            }

            tooltip.hidden = false;
            tooltip.classList.add('is-visible');
        };

        plot.querySelectorAll('.admin-training-chart-dot').forEach((dot) => {
            dot.addEventListener('pointerenter', () => showTooltip(dot));
            dot.addEventListener('pointerleave', hideTooltip);
        });
    });

    const setActiveNavigationKey = (activeKey) => {
        if (!activeKey) {
            return;
        }

        dashboardLinks.forEach((link) => {
            const isActive = link.dataset.dashboardNavKey === activeKey;

            link.classList.toggle('is-active', isActive);
            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    const storedKeyForHash = (hash) => `mcare-dashboard-nav:${window.location.pathname}:${hash}`;

    // Use only one logical item for a hash, even when future placeholder tabs share a section.
    const setActiveHash = () => {
        const activeHash = window.location.hash;

        if (!activeHash) {
            return;
        }

        const matchingLinks = Array.from(hashLinks).filter((link) => {
            const url = new URL(link.href, window.location.href);

            return url.pathname === window.location.pathname && url.hash === activeHash;
        });

        if (matchingLinks.length === 0) {
            return;
        }

        const rememberedKey = window.sessionStorage.getItem(storedKeyForHash(activeHash));
        const rememberedLink = matchingLinks.find((link) => link.dataset.dashboardNavKey === rememberedKey);
        const activeKey = rememberedLink?.dataset.dashboardNavKey ?? matchingLinks[0].dataset.dashboardNavKey;

        setActiveNavigationKey(activeKey);
    };

    let dashboardScrollFrame = null;
    const scrollDashboardTo = (target) => {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const startPosition = window.scrollY;
        const targetPosition = Math.max(0, target.getBoundingClientRect().top + startPosition - 96);
        const distance = targetPosition - startPosition;

        if (reduceMotion || Math.abs(distance) < 8) {
            window.scrollTo({ top: targetPosition, left: 0 });
            return;
        }

        if (dashboardScrollFrame) {
            window.cancelAnimationFrame(dashboardScrollFrame);
        }

        document.documentElement.classList.add('dashboard-scroll-in-progress');
        const duration = 240;
        let startedAt = null;
        const animate = (timestamp) => {
            startedAt ??= timestamp;
            const progress = Math.min((timestamp - startedAt) / duration, 1);
            const easedProgress = 1 - Math.pow(1 - progress, 3);

            window.scrollTo({ top: startPosition + distance * easedProgress, left: 0 });

            if (progress < 1) {
                dashboardScrollFrame = window.requestAnimationFrame(animate);
            } else {
                dashboardScrollFrame = null;
                document.documentElement.classList.remove('dashboard-scroll-in-progress');
            }
        };

        dashboardScrollFrame = window.requestAnimationFrame(animate);
    };

    hashLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const url = new URL(link.href, window.location.href);
            const activeKey = link.dataset.dashboardNavKey;

            if (activeKey && url.hash) {
                window.sessionStorage.setItem(storedKeyForHash(url.hash), activeKey);
                setActiveNavigationKey(activeKey);
            }

            const target = url.hash ? document.getElementById(url.hash.slice(1)) : null;
            const isCurrentDocument = url.pathname === window.location.pathname && url.search === window.location.search;

            // Same-page dashboard tabs scroll smoothly without a full refresh or white blink.
            if (target && isCurrentDocument) {
                event.preventDefault();
                window.history.pushState({}, '', url.hash);
                scrollDashboardTo(target);
            }
        });
    });

    window.addEventListener('hashchange', setActiveHash);
    window.addEventListener('popstate', setActiveHash);
    setActiveHash();

    const prefetchedUrls = new Set();
    const prefetchDashboardPage = (link) => {
        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin || url.href === window.location.href || prefetchedUrls.has(url.href)) {
            return;
        }

        prefetchedUrls.add(url.href);
        const hint = document.createElement('link');
        hint.rel = 'prefetch';
        hint.href = url.href;
        hint.as = 'document';
        document.head.append(hint);
    };

    prefetchLinks.forEach((link) => {
        link.addEventListener('pointerenter', () => prefetchDashboardPage(link), { once: true });
        link.addEventListener('focus', () => prefetchDashboardPage(link), { once: true });
        link.addEventListener('touchstart', () => prefetchDashboardPage(link), { once: true, passive: true });

        link.addEventListener('click', (event) => {
            const url = new URL(link.href, window.location.href);
            const isModifiedClick = event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
            const isSameDocumentHash = url.pathname === window.location.pathname
                && url.search === window.location.search
                && url.hash;

            if (isModifiedClick || url.origin !== window.location.origin || isSameDocumentHash) {
                return;
            }

            if (link.dataset.dashboardNavKey) {
                setActiveNavigationKey(link.dataset.dashboardNavKey);
            }

            // A quick double-click (or a mobile tap burst) can otherwise queue
            // multiple full page requests. Allow the first navigation to win,
            // then release the lock automatically if a connection stalls.
            if (navigationLocked) {
                event.preventDefault();
                if (!navigationSpamReported) {
                    navigationSpamReported = true;
                    reportClientSecurityEvent('navigation_spam');
                }
                return;
            }

            navigationLocked = true;
            document.querySelectorAll('a[data-dashboard-prefetch]').forEach((navLink) => {
                navLink.classList.add('is-loading');
                navLink.setAttribute('aria-disabled', 'true');
            });
            window.clearTimeout(navigationUnlockTimer);
            navigationUnlockTimer = window.setTimeout(() => {
                navigationLocked = false;
                document.querySelectorAll('a[data-dashboard-prefetch]').forEach((navLink) => {
                    navLink.classList.remove('is-loading');
                    navLink.removeAttribute('aria-disabled');
                });
            }, 5000);

            document.documentElement.classList.add('dashboard-navigating');
            dashboardMain?.setAttribute('aria-busy', 'true');
        });
    });

    // Use one accessible confirmation surface for destructive actions and
    // timed quiz starts/submissions instead of browser-specific confirm boxes.
    const confirmDialog = document.querySelector('[data-lms-confirm-dialog]');
    const confirmTitle = confirmDialog?.querySelector('#lms-confirm-title');
    const confirmMessage = confirmDialog?.querySelector('[data-lms-confirm-message]');
    const confirmDetail = confirmDialog?.querySelector('[data-lms-confirm-detail]');
    const confirmAction = confirmDialog?.querySelector('[data-lms-confirm-action]');
    let pendingConfirmedForm = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;
                return;
            }

            if (!confirmDialog || typeof confirmDialog.showModal !== 'function') {
                const message = [form.dataset.confirm, form.dataset.confirmDetail]
                    .filter(Boolean)
                    .join(' ');
                if (!window.confirm(message || 'Continue with this action?')) {
                    event.preventDefault();
                    return;
                }
                return;
            }

            event.preventDefault();
            pendingConfirmedForm = form;
            if (confirmTitle) {
                confirmTitle.textContent = form.dataset.confirmTitle || 'Confirm action';
            }
            if (confirmMessage) {
                confirmMessage.textContent = form.dataset.confirm || 'Continue with this action?';
            }
            if (confirmDetail) {
                const detail = form.dataset.confirmDetail || '';
                confirmDetail.textContent = detail;
                confirmDetail.hidden = detail === '';
            }
            if (confirmAction) {
                confirmAction.textContent = form.dataset.confirmAction || 'Continue';
            }
            confirmDialog.showModal();
        });
    });

    confirmDialog?.addEventListener('close', () => {
        const form = pendingConfirmedForm;
        pendingConfirmedForm = null;

        if (confirmDialog.returnValue !== 'confirm' || !form) return;
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });

    // Keep the initials fallback visible when a remote Google image can no
    // longer be loaded or was removed by its provider.
    document.querySelectorAll('[data-user-avatar-image]').forEach((image) => {
        image.addEventListener('error', () => image.remove(), { once: true });
    });

    document.querySelectorAll('[data-profile-photo-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const form = input.closest('[data-profile-photo-form]');
            const avatar = form?.querySelector('.user-avatar');
            if (!file || !file.type.startsWith('image/') || !avatar) {
                return;
            }

            const previewUrl = URL.createObjectURL(file);
            let image = avatar.querySelector('[data-user-avatar-image]');
            if (!image) {
                image = document.createElement('img');
                image.alt = '';
                image.className = 'user-avatar-image';
                image.decoding = 'async';
                image.dataset.userAvatarImage = '';
                image.addEventListener('error', () => image.remove(), { once: true });
                avatar.appendChild(image);
            }
            image.src = previewUrl;
        });
    });

    // Batch and single-trainee assignment share the same form component.
    // Disable the inactive selector so only the chosen audience reaches Laravel.
    document.querySelectorAll('[data-audience-scope]').forEach((scope) => {
        const controls = Array.from(scope.querySelectorAll('[data-audience-control]'));
        const batchSelect = scope.querySelector('[data-audience-batch]');
        const traineeSelect = scope.querySelector('[data-audience-trainee]');

        const syncAudience = () => {
            const selectedType = controls.find((control) => control.checked)?.value || 'batch';
            if (batchSelect) batchSelect.disabled = selectedType !== 'batch';
            if (traineeSelect) traineeSelect.disabled = selectedType !== 'trainee';
        };

        controls.forEach((control) => control.addEventListener('change', syncAudience));
        syncAudience();
    });

    // Show the selected file immediately. This is deliberately lightweight:
    // it previews file identity and size without reading large videos/PDFs.
    document.querySelectorAll('[data-lms-file-input]').forEach((input) => {
        const picker = input.closest('.lms-file-picker') || input.parentElement;
        const preview = picker?.querySelector('[data-lms-file-preview]');
        const activatePicker = () => input.click();

        preview?.addEventListener('click', activatePicker);
        preview?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            activatePicker();
        });
        if (preview) {
            preview.tabIndex = 0;
            preview.setAttribute('role', 'button');
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file || !preview) return;

            const sizeInMb = file.size / (1024 * 1024);
            const strong = preview.querySelector('strong');
            const small = preview.querySelector('small');
            if (strong) strong.textContent = file.name;
            if (small) small.textContent = `${sizeInMb.toFixed(sizeInMb >= 10 ? 0 : 1)} MB - ready to upload`;
        });
    });

    document.querySelectorAll('[data-close-inline-editor]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('.lms-inline-editor')?.removeAttribute('open');
        });
    });

    document.querySelectorAll('.lms-inline-editor').forEach((editor) => {
        editor.addEventListener('toggle', () => {
            if (!editor.open) return;
            window.requestAnimationFrame(() => {
                editor.querySelector('form input:not([type="hidden"]), form textarea, form select')?.focus();
            });
        });
    });

    // Dynamic quiz builder. Every reindex keeps nested input names aligned
    // with Laravel's questions[n][options][n] validation contract.
    document.querySelectorAll('[data-quiz-builder]').forEach((builder) => {
        const questionList = builder.querySelector('[data-quiz-question-list]');
        const questionTemplate = builder.querySelector('[data-quiz-question-template]');
        const addQuestionButton = builder.querySelector('[data-add-question]');

        if (!questionList || !questionTemplate) return;

        const updateOptionControls = (question) => {
            const type = question.querySelector('[data-question-type]')?.value || 'multiple_choice';
            const optionFieldset = question.querySelector('.lms-option-fieldset');
            const correctField = question.querySelector('.lms-correct-answer');
            const optionList = question.querySelector('[data-quiz-option-list]');
            const addOptionButton = question.querySelector('[data-add-option]');

            if (type === 'file_upload' || type === 'enumeration') {
                if (optionFieldset) optionFieldset.hidden = true;
                if (correctField) correctField.hidden = true;
                optionList?.querySelectorAll('input').forEach((input) => {
                    input.disabled = true;
                    input.required = false;
                });
                if (correctField?.querySelector('select')) {
                    correctField.querySelector('select').disabled = true;
                }
                return;
            }

            if (optionFieldset) optionFieldset.hidden = false;
            if (correctField) correctField.hidden = false;
            optionList?.querySelectorAll('input').forEach((input) => {
                input.disabled = false;
                input.required = true;
            });
            if (correctField?.querySelector('select')) {
                correctField.querySelector('select').disabled = false;
            }

            if (!optionList) return;

            if (type === 'true_false') {
                optionList.innerHTML = ['True', 'False'].map((label) => `
                    <div class="lms-option-row" data-quiz-option>
                        <span class="lms-option-letter" aria-hidden="true"></span>
                        <label class="sr-only"></label>
                        <input value="${label}" readonly required>
                        <button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option" hidden>x</button>
                    </div>
                `).join('');
                if (addOptionButton) addOptionButton.hidden = true;
            } else {
                const options = Array.from(optionList.querySelectorAll('[data-quiz-option]'));
                if (options.length < 2) {
                    while (optionList.querySelectorAll('[data-quiz-option]').length < 4) {
                        optionList.insertAdjacentHTML('beforeend', `
                            <div class="lms-option-row" data-quiz-option>
                                <span class="lms-option-letter" aria-hidden="true"></span>
                                <label class="sr-only"></label>
                                <input required>
                                <button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option">x</button>
                            </div>
                        `);
                    }
                }
                optionList.querySelectorAll('input').forEach((option) => option.readOnly = false);
                optionList.querySelectorAll('[data-remove-option]').forEach((button) => button.hidden = false);
                if (addOptionButton) addOptionButton.hidden = false;
            }
        };

        const reindexBuilder = () => {
            const questions = Array.from(questionList.querySelectorAll('[data-quiz-question]'));

            questions.forEach((question, questionIndex) => {
                question.dataset.questionIndex = String(questionIndex);
                const number = question.querySelector('.lms-question-number');
                if (number) number.textContent = `Question ${questionIndex + 1}`;

                question.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/questions\[(?:\d+|__INDEX__)\]/, `questions[${questionIndex}]`);
                });

                question.querySelectorAll('[id]').forEach((field) => {
                    field.id = field.id.replace(/question-(?:\d+|__INDEX__)-/, `question-${questionIndex}-`);
                });

                const options = Array.from(question.querySelectorAll('[data-quiz-option]'));
                const correctSelect = question.querySelector('[data-correct-option]');
                const previousCorrect = Number(correctSelect?.value || 0);

                options.forEach((option, optionIndex) => {
                    const optionInput = option.querySelector('input');
                    const optionLabel = option.querySelector('label');
                    const optionLetter = option.querySelector('.lms-option-letter');
                    const removeButton = option.querySelector('[data-remove-option]');
                    const letter = String.fromCharCode(65 + optionIndex);

                    if (optionInput) {
                        optionInput.name = `questions[${questionIndex}][options][${optionIndex}]`;
                        optionInput.id = `question-${questionIndex}-option-${optionIndex}`;
                    }
                    if (optionLabel) {
                        optionLabel.htmlFor = optionInput?.id || '';
                        optionLabel.textContent = `Option ${optionIndex + 1}`;
                    }
                    if (optionLetter) optionLetter.textContent = letter;
                    if (removeButton) removeButton.setAttribute('aria-label', `Remove option ${optionIndex + 1}`);
                });

                if (correctSelect) {
                    correctSelect.name = `questions[${questionIndex}][correct_option]`;
                    correctSelect.id = `question-${questionIndex}-correct`;
                    correctSelect.innerHTML = options.map((_, optionIndex) => {
                        const selected = optionIndex === Math.min(previousCorrect, options.length - 1) ? ' selected' : '';
                        return `<option value="${optionIndex}"${selected}>Option ${String.fromCharCode(65 + optionIndex)}</option>`;
                    }).join('');
                }
            });
        };

        const bindQuestion = (question) => {
            const typeSelect = question.querySelector('[data-question-type]');
            const addOptionButton = question.querySelector('[data-add-option]');

            typeSelect?.addEventListener('change', () => {
                updateOptionControls(question);
                reindexBuilder();
            });

            addOptionButton?.addEventListener('click', () => {
                const optionList = question.querySelector('[data-quiz-option-list]');
                const optionCount = optionList?.querySelectorAll('[data-quiz-option]').length || 0;
                if (!optionList || optionCount >= 6) return;

                optionList.insertAdjacentHTML('beforeend', `
                    <div class="lms-option-row" data-quiz-option>
                        <span class="lms-option-letter" aria-hidden="true"></span>
                        <label class="sr-only"></label>
                        <input required>
                        <button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option">x</button>
                    </div>
                `);
                reindexBuilder();
            });

            question.addEventListener('click', (event) => {
                const removeOptionButton = event.target.closest('[data-remove-option]');
                if (removeOptionButton) {
                    const options = question.querySelectorAll('[data-quiz-option]');
                    if (options.length <= 2) return;
                    removeOptionButton.closest('[data-quiz-option]')?.remove();
                    reindexBuilder();
                    return;
                }

                if (event.target.closest('[data-remove-question]')) {
                    const questions = questionList.querySelectorAll('[data-quiz-question]');
                    if (questions.length <= 1) {
                        question.querySelector('textarea')?.focus();
                        return;
                    }
                    question.remove();
                    reindexBuilder();
                }
            });

            updateOptionControls(question);
        };

        questionList.querySelectorAll('[data-quiz-question]').forEach(bindQuestion);

        addQuestionButton?.addEventListener('click', () => {
            const questionIndex = questionList.querySelectorAll('[data-quiz-question]').length;
            const wrapper = document.createElement('div');
            wrapper.innerHTML = questionTemplate.innerHTML
                .replaceAll('__INDEX__', String(questionIndex))
                .replaceAll('__NUMBER__', String(questionIndex + 1))
                .trim();
            const question = wrapper.firstElementChild;
            if (!question) return;

            questionList.append(question);
            bindQuestion(question);
            reindexBuilder();
            question.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'center',
            });
            question.querySelector('textarea')?.focus({ preventScroll: true });
        });

        reindexBuilder();
    });

    // Quiz attempts retain an authoritative server deadline. The client timer
    // is a readable countdown only; the backend still enforces expiration.
    document.querySelectorAll('[data-quiz-attempt]').forEach((attemptPage) => {
        const form = attemptPage.querySelector('[data-quiz-attempt-form]');
        const timer = attemptPage.querySelector('[data-quiz-timer]');
        const timerValue = attemptPage.querySelector('[data-quiz-timer-value]');
        const progress = attemptPage.querySelector('[data-answer-progress]');
        const submitButton = attemptPage.querySelector('[data-submit-quiz]');
        const questions = Array.from(attemptPage.querySelectorAll('[data-answer-question]'));
        const jumps = Array.from(attemptPage.querySelectorAll('[data-question-jump]'));
        const remainingValue = attemptPage.dataset.remainingSeconds;
        let remainingSeconds = remainingValue === 'unlimited' ? null : Number(remainingValue || 0);
        let autoSubmitted = false;

        const updateAnswerProgress = () => {
            const answered = questions.filter((question) => question.querySelector('input[type="radio"]:checked')).length;
            if (progress) progress.textContent = `${answered} of ${questions.length} answered`;
            questions.forEach((question, index) => {
                jumps[index]?.classList.toggle('is-answered', Boolean(question.querySelector('input[type="radio"]:checked')));
            });
        };

        const formatTime = (seconds) => {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainder = seconds % 60;
            return hours > 0
                ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`
                : `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
        };

        const tick = () => {
            if (remainingSeconds === null) {
                timer?.classList.remove('is-warning', 'is-critical');
                return;
            }

            if (timerValue) timerValue.textContent = formatTime(Math.max(0, remainingSeconds));
            timer?.classList.toggle('is-warning', remainingSeconds > 60 && remainingSeconds <= 300);
            timer?.classList.toggle('is-critical', remainingSeconds <= 60);

            if (remainingSeconds <= 0) {
                if (!autoSubmitted && form) {
                    autoSubmitted = true;
                    form.dataset.confirmed = 'true';
                    form.requestSubmit();
                }
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Finalizing...';
                }
                return;
            }

            remainingSeconds -= 1;
            window.setTimeout(tick, 1000);
        };

        form?.addEventListener('change', updateAnswerProgress);
        updateAnswerProgress();
        tick();
    });

    // Native details menus remain keyboard-friendly; close only when focus/click moves away.
    document.addEventListener('click', (event) => {
        accountMenus.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        accountMenus.forEach((menu) => menu.removeAttribute('open'));
        document.querySelectorAll('.lms-inline-editor[open]').forEach((editor) => editor.removeAttribute('open'));
    });
});

window.addEventListener('pageshow', () => {
    applyDashboardTheme(readDashboardTheme());
    document.documentElement.classList.remove('dashboard-navigating');
    document.querySelector('.dashboard-main')?.removeAttribute('aria-busy');
    document.querySelectorAll('a[data-dashboard-prefetch].is-loading').forEach((navLink) => {
        navLink.classList.remove('is-loading');
        navLink.removeAttribute('aria-disabled');
    });
});

window.addEventListener('storage', (event) => {
    if (event.key === dashboardThemeStorageKey) {
        applyDashboardTheme(readDashboardTheme());
    }

});

document.addEventListener('DOMContentLoaded', () => {
    const board = document.querySelector('[data-competency-board]');
    if (!board) return;

    const scroller = board.querySelector('[data-competency-scroller]');
    board.querySelectorAll('[data-competency-scroll]').forEach((button) => {
        button.addEventListener('click', () => {
            const direction = button.dataset.competencyScroll === 'left' ? -1 : 1;
            scroller?.scrollBy({ left: direction * Math.max(scroller.clientWidth * 0.7, 420), behavior: 'smooth' });
        });
    });

    const selectors = [...board.querySelectorAll('[data-trainee-selector]')];
    const selectAll = board.querySelector('[data-select-all-trainees]');
    const bulkButton = board.querySelector('[data-bulk-update-open]');
    const selectedCount = board.querySelector('[data-selected-trainee-count]');
    const dialogCount = board.querySelector('[data-bulk-dialog-count]');

    const updateSelectionState = () => {
        const checked = selectors.filter((selector) => selector.checked).length;
        if (selectedCount) selectedCount.textContent = `${checked} selected`;
        if (dialogCount) dialogCount.textContent = `${checked} trainee${checked === 1 ? '' : 's'} selected`;
        if (bulkButton) bulkButton.disabled = checked === 0;
        if (selectAll) {
            selectAll.checked = selectors.length > 0 && checked === selectors.length;
            selectAll.indeterminate = checked > 0 && checked < selectors.length;
        }
    };

    selectors.forEach((selector) => selector.addEventListener('change', updateSelectionState));
    selectAll?.addEventListener('change', () => {
        selectors.forEach((selector) => {
            selector.checked = selectAll.checked;
        });
        updateSelectionState();
    });
    updateSelectionState();

    const bulkStatus = board.querySelector('[data-bulk-status]');
    const bulkScoreWrap = board.querySelector('[data-bulk-score-wrap]');
    const bulkScore = board.querySelector('[data-bulk-score]');
    const updateBulkScore = () => {
        const needsScore = bulkStatus?.value === 'competent';
        bulkScoreWrap?.classList.toggle('hidden', !needsScore);
        if (bulkScore) bulkScore.required = needsScore;
    };
    bulkStatus?.addEventListener('change', updateBulkScore);
    updateBulkScore();

    const drawer = board.querySelector('[data-competency-drawer]');
    const drawerBackdrop = board.querySelector('[data-competency-drawer-backdrop]');
    const drawerForm = board.querySelector('[data-competency-drawer-form]');
    const drawerUnitCode = board.querySelector('[data-drawer-unit-code]');
    const drawerTrainee = board.querySelector('[data-drawer-trainee]');
    const drawerUnitTitle = board.querySelector('[data-drawer-unit-title]');
    const drawerUnitId = board.querySelector('[data-drawer-unit-id]');
    const drawerStatus = board.querySelector('[data-drawer-status]');
    const drawerScore = board.querySelector('[data-drawer-score]');
    const drawerNotes = board.querySelector('[data-drawer-notes]');
    const drawerOutcomes = board.querySelector('[data-drawer-outcomes]');
    const drawerLockNotice = board.querySelector('[data-drawer-lock-notice]');
    const drawerSave = board.querySelector('[data-drawer-save]');
    const drawerFullRecord = board.querySelector('[data-drawer-full-record]');

    const decodeRecordPayload = (encoded) => {
        const bytes = Uint8Array.from(window.atob(encoded), (character) => character.charCodeAt(0));
        return JSON.parse(new TextDecoder().decode(bytes));
    };

    const closeDrawer = () => {
        drawer?.classList.remove('is-open');
        drawer?.setAttribute('aria-hidden', 'true');
        drawerBackdrop?.classList.remove('is-open');
        document.body.classList.remove('competency-drawer-open');
        window.setTimeout(() => {
            if (drawerBackdrop && !drawerBackdrop.classList.contains('is-open')) drawerBackdrop.hidden = true;
        }, 220);
    };

    const buildOutcomeRow = (outcome, unitId, locked) => {
        const row = document.createElement('div');
        row.className = 'grid gap-3 py-3 sm:grid-cols-[1fr_11rem] sm:items-center';
        const label = document.createElement('label');
        label.className = 'text-sm font-medium text-slate-800';
        label.htmlFor = `drawer-outcome-${outcome.id}`;
        label.textContent = outcome.title;
        const select = document.createElement('select');
        select.id = `drawer-outcome-${outcome.id}`;
        select.name = `records[${unitId}][outcomes][${outcome.id}]`;
        select.className = 'form-field';
        select.disabled = locked;
        [...drawerStatus.options].forEach((sourceOption) => {
            const option = sourceOption.cloneNode(true);
            option.selected = option.value === outcome.status;
            select.append(option);
        });
        row.append(label, select);
        return row;
    };

    const openDrawer = (payload) => {
        if (!drawer || !drawerForm || !drawerOutcomes) return;
        drawerForm.action = payload.update_url;
        drawerUnitCode.textContent = payload.unit_code;
        drawerTrainee.textContent = payload.trainee_name;
        drawerUnitTitle.textContent = payload.unit_title;
        drawerUnitId.name = `records[${payload.unit_id}][unit_id]`;
        drawerUnitId.value = payload.unit_id;
        drawerStatus.name = `records[${payload.unit_id}][status]`;
        drawerStatus.value = payload.status;
        drawerScore.name = `records[${payload.unit_id}][percentage_score]`;
        drawerScore.value = payload.score ?? '';
        drawerNotes.name = `records[${payload.unit_id}][notes]`;
        drawerNotes.value = payload.notes ?? '';
        drawerFullRecord.href = payload.full_url;
        drawerOutcomes.replaceChildren(...payload.outcomes.map(
            (outcome) => buildOutcomeRow(outcome, payload.unit_id, payload.locked)
        ));
        [drawerUnitId, drawerStatus, drawerScore, drawerNotes].forEach((field) => {
            field.disabled = payload.locked;
        });
        drawerLockNotice?.classList.toggle('hidden', !payload.locked);
        drawerSave?.classList.toggle('hidden', payload.locked);
        drawerSave.disabled = payload.locked;
        drawerBackdrop.hidden = false;
        window.requestAnimationFrame(() => {
            drawerBackdrop.classList.add('is-open');
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.classList.add('competency-drawer-open');
            drawerStatus.focus();
        });
    };

    board.querySelectorAll('[data-competency-cell]').forEach((cell) => {
        cell.addEventListener('click', () => {
            try {
                openDrawer(decodeRecordPayload(cell.dataset.recordPayload));
            } catch (error) {
                closeDrawer();
            }
        });
    });

    drawerStatus?.addEventListener('change', () => {
        drawerOutcomes?.querySelectorAll('select').forEach((select) => {
            select.value = drawerStatus.value;
        });
        if (drawerStatus.value === 'competent' && !drawerScore.value) drawerScore.value = '75';
        if (drawerStatus.value === 'not_assessed') drawerScore.value = '';
    });
    drawerForm?.addEventListener('submit', () => {
        drawerSave.disabled = true;
        drawerSave.textContent = 'Saving evaluation...';
    });
    board.querySelectorAll('[data-competency-drawer-close]').forEach((button) => button.addEventListener('click', closeDrawer));
    drawerBackdrop?.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer?.classList.contains('is-open')) closeDrawer();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-trainer-global-search]');
    const input = form?.querySelector('input[type="search"]');
    const suggestBox = form?.querySelector('[id="trainer-search-suggest"]');
    const suggestUrl = form?.dataset.suggestUrl;
    if (!form || !input || !suggestBox || !suggestUrl) {
        return;
    }

    let timer = null;
    let activeRequest = null;

    const hideSuggest = () => {
        suggestBox.hidden = true;
        suggestBox.replaceChildren();
        input.setAttribute('aria-expanded', 'false');
    };

    const renderSuggest = (groups) => {
        suggestBox.replaceChildren();
        if (!Array.isArray(groups) || groups.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'trainer-search-suggest-empty';
            empty.textContent = 'No matching pages or class records.';
            suggestBox.append(empty);
            suggestBox.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            return;
        }

        groups.forEach((group) => {
            const section = document.createElement('div');
            section.className = 'trainer-search-suggest-group';
            const label = document.createElement('p');
            label.className = 'trainer-search-suggest-label';
            label.textContent = group.label || '';
            section.append(label);
            (group.results || []).forEach((item) => {
                const link = document.createElement('a');
                link.href = item.href || '#';
                link.setAttribute('role', 'option');
                const title = document.createElement('strong');
                title.textContent = item.title || '';
                link.append(title);
                if (item.subtitle) {
                    const subtitle = document.createElement('small');
                    subtitle.textContent = item.subtitle;
                    link.append(subtitle);
                }
                section.append(link);
            });
            suggestBox.append(section);
        });
        suggestBox.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const requestSuggest = (query) => {
        if (query.trim().length < 2) {
            hideSuggest();
            return;
        }

        activeRequest?.abort();
        activeRequest = new AbortController();
        const url = new URL(suggestUrl, window.location.origin);
        url.searchParams.set('q', query);

        fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: activeRequest.signal,
        })
            .then((response) => (response.ok ? response.json() : Promise.reject(response)))
            .then((payload) => {
                if (input.value.trim() !== query.trim()) {
                    return;
                }
                renderSuggest(payload.groups || []);
            })
            .catch((error) => {
                if (error?.name !== 'AbortError') {
                    hideSuggest();
                }
            });
    };

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => requestSuggest(input.value), 220);
    });

    input.addEventListener('focus', () => {
        if (input.value.trim().length >= 2 && suggestBox.childElementCount > 0) {
            suggestBox.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }
    });

    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) {
            hideSuggest();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && form.contains(document.activeElement)) {
            hideSuggest();
        }
    });
});
