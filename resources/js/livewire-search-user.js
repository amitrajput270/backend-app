// Livewire SearchUser page JS moved from inline blade
document.addEventListener('livewire:init', () => {
    const selectAllCheckbox = document.getElementById("selectAll");
    const deleteButton = document.getElementById("deleteSelected");

    if (window.Livewire && typeof Livewire.on === 'function') {
        Livewire.on('checkboxStateChanged', () => {
            toggleDeleteButton();
        });
    }

    document.addEventListener("change", function(event) {
        if (event.target.classList && event.target.classList.contains("userCheckbox")) {
            toggleDeleteButton();
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = document.querySelectorAll(".userCheckbox:checked").length === document.querySelectorAll(".userCheckbox").length;
            }
        }
    });

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener("change", function() {
            document.querySelectorAll(".userCheckbox").forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            toggleDeleteButton();
        });
    }

    function toggleDeleteButton() {
        const deleteBtn = document.getElementById("deleteSelected");
        if (!deleteBtn) return;
        const checkedBoxes = document.querySelectorAll(".userCheckbox:checked");
        deleteBtn.disabled = checkedBoxes.length === 0;

        if (checkedBoxes.length > 0) {
            deleteBtn.textContent = `Delete Selected (${checkedBoxes.length})`;
        } else {
            deleteBtn.textContent = 'Delete Selected';
        }
    }

    const deleteBtn = document.getElementById("deleteSelected");
    if (deleteBtn) {
        deleteBtn.addEventListener("click", function() {
            const selectedUsers = [...document.querySelectorAll(".userCheckbox:checked")].map(checkbox => checkbox.value);
            if (selectedUsers.length > 0 && confirm(`Are you sure you want to delete ${selectedUsers.length} selected user(s)?`)) {
                sendToLivewire('deleteSelected', JSON.stringify(selectedUsers));
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                toggleDeleteButton();
            }
        });
    }

    window.confirmDelete = function(userId) {
        if (confirm("Are you sure you want to delete this user?")) {
            sendToLivewire('deleteUser', userId);
        }
    }

});

function sendToLivewire(eventName, payload) {
    try {
        if (window.Livewire && typeof Livewire.emit === 'function') {
            Livewire.emit(eventName, payload);
            return;
        }

        if (window.Livewire && typeof Livewire.find === 'function') {
            const el = document.querySelector('[wire\\:id]');
            if (!el) return console.warn('Livewire component element not found');
            const id = el.getAttribute('wire:id');
            if (!id) return console.warn('Livewire component id not found');

            Livewire.find(id).call(eventName, payload);
            return;
        }

        console.warn('Livewire is not available on the page');
    } catch (e) {
        console.error('Error sending event to Livewire', e);
    }
}

// Auto-hide success message when it appears (handles dynamic insertion)
(function setupAutoHide() {
    function hideMsg(msg) {
        msg.style.transition = 'opacity 0.4s ease';
        msg.style.opacity = '0';
        setTimeout(() => msg.remove(), 400);
    }

    function startTimer(msg) {
        if (msg.dataset.autoHideStarted) return;
        msg.dataset.autoHideStarted = '1';
        setTimeout(() => hideMsg(msg), 3000);
    }

    const existing = document.getElementById('success-message');
    if (existing) startTimer(existing);

    const observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                if (node.nodeType !== 1) continue;
                if (node.id === 'success-message') {
                    startTimer(node);
                    continue;
                }
                const nested = node.querySelector && node.querySelector('#success-message');
                if (nested) startTimer(nested);
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
