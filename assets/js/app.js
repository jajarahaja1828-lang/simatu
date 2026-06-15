// Sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

// Close sidebar on outside click (mobile)
document.addEventListener('click', (e) => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (window.innerWidth <= 992 && sidebar?.classList.contains('open')) {
        if (!sidebar.contains(e.target) && !toggle?.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    }
});

// Modal helpers
function openModal(id) {
    document.getElementById(id)?.classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id)?.classList.remove('show');
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.querySelectorAll('.modal-custom').forEach(modal => {
    modal.querySelector('.modal-overlay')?.addEventListener('click', () => {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    });
});

// Auto-dismiss alerts after 4s
setTimeout(() => {
    document.querySelectorAll('.alert.show').forEach(el => {
        el.classList.remove('show');
        el.classList.add('fade');
        setTimeout(() => el.remove(), 200);
    });
}, 4000);

// Confirm delete
function confirmDelete(url, name) {
    if (confirm('Hapus "' + name + '"? Tindakan ini tidak dapat dibatalkan.')) {
        window.location.href = url;
    }
}

// Search filter for tables
function tableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('input', () => {
        const val = input.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    });
}

// Format rupiah input
function formatRupiahInput(el) {
    el.addEventListener('input', () => {
        let val = el.value.replace(/\D/g, '');
        el.value = val ? parseInt(val).toLocaleString('id-ID') : '';
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    tableSearch('searchInput', 'mainTable');
});
