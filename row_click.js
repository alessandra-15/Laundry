/**
 * row_click.js
 * Clicking anywhere on a table row opens that row's summary, so users don't
 * have to aim for the small view icon. A row opens its data-href, or else
 * clicks its own view control. Buttons and links in the row keep working.
 */
(function () {
    const VIEW_CONTROL = '[data-row-open], [title="View Details"], [title="Open in Queue"]';

    function rowTarget(row) {
        return row.dataset.href ? row : row.querySelector(VIEW_CONTROL);
    }

    document.querySelectorAll('tbody tr').forEach(function (row) {
        if (rowTarget(row)) row.style.cursor = 'pointer';
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('a, button, input, select, textarea, label, [onclick]')) return;
        if (String(window.getSelection())) return;

        const row = e.target.closest('tbody tr');
        const target = row && rowTarget(row);
        if (!target) return;

        if (target === row) {
            window.location.href = row.dataset.href;
        } else {
            target.click();
        }
    });
})();
