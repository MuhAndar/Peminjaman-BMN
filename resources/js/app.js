import './bootstrap';
import Alpine from 'alpinejs';
import htmx from 'htmx.org';
import Chart from 'chart.js/auto';

window.htmx = htmx;
window.Alpine = Alpine;
window.Chart = Chart;

// Konfigurasi HTMX
htmx.config.historyEnabled = true;

// Cegah HTMX boost mencegat tautan berkas unduhan biner (Excel, CSV, PDF, dsb.)
document.addEventListener('click', (event) => {
    const link = event.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href') || '';
    const hasDownloadAttr = link.hasAttribute('download');
    const isTargetBlank = link.getAttribute('target') === '_blank';
    const isFileExtension = /\.(xlsx|xls|csv|pdf|zip|docx|doc)($|\?)/i.test(href);
    const isDownloadRoute = href.includes('/template') || href.includes('/export') || href.includes('/download') || href.includes('/pdf');

    if (hasDownloadAttr || isTargetBlank || isFileExtension || isDownloadRoute) {
        link.setAttribute('hx-boost', 'false');
    }
}, true);

// 1. Kirim CSRF Token Laravel pada setiap request HTMX
document.addEventListener('htmx:configRequest', (event) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        event.detail.headers['X-CSRF-TOKEN'] = token;
    }
});

// 2. Inisialisasi ulang Alpine.js dan Lucide Icons saat konten halaman berganti via HTMX
document.addEventListener('htmx:beforeSwap', (event) => {
    if (window.Alpine && (event.detail.target === document.body || event.detail.target.tagName === 'BODY')) {
        Alpine.destroyTree(document.body);
    }
});

document.addEventListener('htmx:afterSwap', (event) => {
    document.documentElement.classList.remove('preload-no-transition');
    if (window.Alpine) {
        Alpine.initTree(event.detail.target);
    }
    if (window.lucide) {
        window.lucide.createIcons();
    }
});

// 3. Indikator Progress Bar di bagian paling atas (seperti GitHub / YouTube)
let progressTimer;
document.addEventListener('htmx:beforeRequest', () => {
    const bar = document.getElementById('bmn-top-progress');
    if (bar) {
        clearTimeout(progressTimer);
        bar.style.transition = 'width 0.35s cubic-bezier(0.1, 0.7, 0.1, 1), opacity 0.2s ease';
        bar.style.opacity = '1';
        bar.style.width = '70%';
    }
});

document.addEventListener('htmx:afterSettle', () => {
    const bar = document.getElementById('bmn-top-progress');
    if (bar) {
        bar.style.transition = 'width 0.15s ease, opacity 0.25s ease 0.15s';
        bar.style.width = '100%';
        bar.style.opacity = '0';
        progressTimer = setTimeout(() => {
            bar.style.transition = 'none';
            bar.style.width = '0%';
        }, 400);
    }
});

// 4. Tangani galat jaringan dengan visualisasi merah
document.addEventListener('htmx:responseError', () => {
    const bar = document.getElementById('bmn-top-progress');
    if (bar) {
        bar.style.background = '#EF4444';
        bar.style.width = '100%';
        setTimeout(() => {
            bar.style.opacity = '0';
            setTimeout(() => {
                bar.style.background = 'linear-gradient(90deg, #0B63B5, #38BDF8)';
                bar.style.width = '0%';
            }, 300);
        }, 800);
    }
});

Alpine.start();

