export function showNotification(message, type = 'info') {
    const notificationArea = document.getElementById('notification-area');
    if (!notificationArea) return;

    const alert = document.createElement('div');
    alert.className = 'p-3 rounded-md mb-3 text-sm border';

    const styles = {
        success: 'bg-green-100 text-green-800 border-green-200',
        error: 'bg-red-100 text-red-800 border-red-200',
        info: 'bg-blue-100 text-blue-800 border-blue-200',
        warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    };

    alert.classList.add(...styles[type].split(' '));
    alert.innerText = message;

    notificationArea.appendChild(alert);
    setTimeout(() => alert.remove(), 5000);
}

export function showProcessing(message = 'Memproses...') {
    const notificationArea = document.getElementById('notification-area');
    if (!notificationArea) return;

    const alert = document.createElement('div');
    alert.id = 'processing-alert';
    alert.className = 'p-3 rounded-md mb-3 text-sm bg-blue-100 text-blue-800 border border-blue-200';
    alert.innerText = message;

    notificationArea.appendChild(alert);
}

export function hideProcessing() {
    document.getElementById('processing-alert')?.remove();
}
