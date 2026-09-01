import {
    Activity,
    BadgeCheck,
    Calculator,
    Check,
    ChevronRight,
    CircleAlert,
    CircleCheck,
    createIcons,
    Download,
    FileJson,
    Gauge,
    ImagePlus,
    KeyRound,
    ListVideo,
    LoaderCircle,
    LockKeyhole,
    OctagonAlert,
    Play,
    Pencil,
    Plus,
    RefreshCw,
    ScanLine,
    ShieldCheck,
    Star,
    Trash2,
    Video,
    X,
} from 'lucide';

createIcons({
    icons: {
        Activity,
        BadgeCheck,
        Calculator,
        Check,
        ChevronRight,
        CircleAlert,
        CircleCheck,
        Download,
        FileJson,
        Gauge,
        ImagePlus,
        KeyRound,
        ListVideo,
        LoaderCircle,
        LockKeyhole,
        OctagonAlert,
        Play,
        Pencil,
        Plus,
        RefreshCw,
        ScanLine,
        ShieldCheck,
        Star,
        Trash2,
        Video,
        X,
    },
});

document.querySelectorAll('[data-file-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const label = input.closest('.file-picker')?.querySelector('[data-file-label]');
        const file = input.files?.[0];

        if (!label || !file) return;

        const megabytes = (file.size / 1024 / 1024).toFixed(file.size > 10 * 1024 * 1024 ? 1 : 2);
        label.textContent = `${file.name} · ${megabytes} MB`;
        label.classList.add('text-stone-800');
    });
});

document.querySelectorAll('[data-submit-lock]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        const label = form.querySelector('[data-submit-label]');

        if (button) button.disabled = true;
        if (label) label.textContent = 'Đang xử lý...';
    });
});

const poller = document.querySelector('[data-status-poller]');

if (poller) {
    const poll = async () => {
        try {
            const response = await fetch(poller.dataset.statusUrl, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;

            const data = await response.json();
            const attempts = poller.querySelector('[data-poll-attempts]');
            if (attempts) attempts.textContent = data.poll_attempts;

            if (data.terminal || data.status !== poller.dataset.currentStatus) {
                window.location.reload();
            }
        } catch {
            // The next interval retries status reads without resubmitting the paid task.
        }
    };

    window.setInterval(poll, 4000);
}
