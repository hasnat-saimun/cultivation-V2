<style>
    /* Hide the editing caret on static interface copy without disabling selection/copy. */
    .ui-static-caret-scope {
        caret-color: transparent;
        cursor: default;
    }

    .ui-static-caret-scope :where(h1, h2, h3, h4, h5, h6, label, p, th, td, .card-title, .breadcrumb-item, .breadcrumbs-area, .ui-static-text) {
        caret-color: transparent;
        cursor: default;
    }

    .ui-static-caret-scope :where(a, button, [role="button"], summary) { cursor: pointer; }

    .ui-static-caret-scope :where(input, textarea, [contenteditable="true"], code, pre, .CodeMirror, .ace_editor, .select2-search__field, .dataTables_filter input) {
        caret-color: auto;
        cursor: text;
    }

    .ui-static-caret-scope :where(select, .select2-container) {
        caret-color: auto;
        cursor: default;
    }
</style>
