document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.convert-to-webp-avif').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const attachmentId = button.dataset.id;
            button.textContent = 'Converting...';
            button.disabled = true;

            fetch(imageOptimizerAjax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'convert_image',
                    nonce: imageOptimizerAjax.nonce,
                    attachment_id: attachmentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Conversion', 'Image converted successfully', 'success');
                    button.textContent = 'Converted';
                } else {
                    showToast('Conversion', 'Error converting image', 'danger');
                    button.textContent = 'Error';
                }
                button.disabled = false;
            })
            .catch(error => {
                console.error(error);
                showToast('Conversion', 'Error converting image', 'danger');
                button.textContent = 'Error';
                button.disabled = false;
            });
        });
    });

    document.getElementById('schedule-bulk-conversion').addEventListener('click', function () {
        fetch(imageOptimizerAjax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'schedule_bulk_conversion',
                nonce: imageOptimizerAjax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Scheduler', 'Bulk conversion scheduled successfully', 'success');
            } else {
                showToast('Scheduler', 'Error scheduling bulk conversion', 'danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Scheduler', 'Error scheduling bulk conversion', 'danger');
        });
    });

    document.getElementById('clean-up-optimized-images').addEventListener('click', function () {
        fetch(imageOptimizerAjax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'clean_up_optimized_images',
                nonce: imageOptimizerAjax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Clean Up', 'Optimized images cleaned up successfully', 'success');
            } else {
                showToast('Clean Up', 'Error cleaning up optimized images', 'danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Clean Up', 'Error cleaning up optimized images', 'danger');
        });
    });

    document.getElementById('toggle-scheduler').addEventListener('change', function () {
        fetch(imageOptimizerAjax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'toggle_scheduler',
                nonce: imageOptimizerAjax.nonce,
                enabled: this.checked
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Scheduler', 'Scheduler toggled successfully', 'success');
            } else {
                showToast('Scheduler', 'Error toggling scheduler', 'danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Scheduler', 'Error toggling scheduler', 'danger');
        });
    });

    document.getElementById('toggle-conversion-on-upload').addEventListener('change', function () {
        fetch(imageOptimizerAjax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'toggle_conversion_on_upload',
                nonce: imageOptimizerAjax.nonce,
                enabled: this.checked
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Conversion', 'Conversion on upload toggled successfully', 'success');
            } else {
                showToast('Conversion', 'Error toggling conversion on upload', 'danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Conversion', 'Error toggling conversion on upload', 'danger');
        });
    });

    document.getElementById('toggle-remove-originals').addEventListener('change', function () {
        fetch(imageOptimizerAjax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'toggle_remove_originals',
                nonce: imageOptimizerAjax.nonce,
                enabled: this.checked
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Remove Originals', 'Remove originals setting toggled successfully', 'success');
            } else {
                showToast('Remove Originals', 'Error toggling remove originals setting', 'danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Remove Originals', 'Error toggling remove originals setting', 'danger');
        });
    });

    document.getElementById('set-conversion-format').addEventListener('change', function () {
        fetch(imageOptimizerAjax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'set_conversion_format',
                nonce: imageOptimizerAjax.nonce,
                format: this.value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Conversion Format', 'Conversion format set successfully', 'success');
            } else {
                showToast('Conversion Format', 'Error setting conversion format', 'danger');
            }
        })
        .catch(error => {
            console.error(error);
            showToast('Conversion Format', 'Error setting conversion format', 'danger');
        });
    });
});

function showToast(title, message, type) {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.setAttribute('data-bs-delay', '5000');

    const toastHeader = document.createElement('div');
    toastHeader.className = 'toast-header';
    toastHeader.innerHTML = `<strong class="me-auto">${title}</strong>`;
    toast.appendChild(toastHeader);

    const toastBody = document.createElement('div');
    toastBody.className = 'toast-body';
    toastBody.textContent = message;
    toast.appendChild(toastBody);

    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}
