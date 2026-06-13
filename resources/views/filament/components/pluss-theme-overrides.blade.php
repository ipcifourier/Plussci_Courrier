@once
    <meta charset="utf-8">
    <style>
        :root {
            --pluss-auth-ink: #8fb4dc;
            --pluss-auth-muted: rgba(232, 242, 255, 0.82);
            --pluss-auth-panel: rgba(8, 19, 35, 0.88);
            --pluss-auth-panel-border: rgba(255, 255, 255, 0.09);
            --pluss-auth-field: rgba(238, 245, 255, 0.94);
            --pluss-auth-field-border: rgba(104, 157, 221, 0.38);
            --pluss-auth-field-focus: #ff9b26;
            --pluss-auth-button-start: #ff9b26;
            --pluss-auth-button-end: #ea7a00;
            --pluss-auth-input-text: #0d2c50;
            --pluss-auth-input-placeholder: rgba(13, 44, 80, 0.58);
            --pluss-auth-remember-text: #8fb4dc;
        }

        .fi-simple-layout {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 12% 18%, rgba(53, 119, 197, 0.2), transparent 20%),
                radial-gradient(circle at 85% 12%, rgba(255, 155, 38, 0.16), transparent 18%),
                radial-gradient(circle at 78% 82%, rgba(88, 185, 71, 0.14), transparent 20%),
                linear-gradient(180deg, #edf4fb 0%, #dce9f7 100%);
        }

        .fi-simple-layout::before,
        .fi-simple-layout::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(10px);
        }

        .fi-simple-layout::before {
            top: -6rem;
            left: -4rem;
            width: 18rem;
            height: 18rem;
            background: rgba(31, 92, 168, 0.16);
        }

        .fi-simple-layout::after {
            right: -5rem;
            bottom: -7rem;
            width: 22rem;
            height: 22rem;
            background: rgba(240, 138, 43, 0.12);
        }

        .fi-simple-layout-header {
            inset-inline-end: 1.5rem;
            top: 1.25rem;
            z-index: 2;
        }

        .fi-simple-layout .fi-simple-main-ctn {
            position: relative;
            z-index: 1;
            padding: 2rem 1rem;
        }

        .fi-simple-layout .fi-simple-main {
            width: min(100%, 34rem);
            margin-block: 2rem;
            padding: 1.45rem;
            border-radius: 30px;
            border: 1px solid var(--pluss-auth-panel-border);
            background:
                linear-gradient(180deg, rgba(10, 20, 35, 0.92), rgba(18, 22, 31, 0.96));
            box-shadow:
                0 34px 80px rgba(9, 20, 37, 0.28),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(14px);
        }

        @media (min-width: 640px) {
            .fi-simple-layout .fi-simple-main {
                padding: 1.75rem;
            }
        }

        .fi-simple-layout .fi-simple-header {
            row-gap: 0.75rem;
            margin-bottom: 1.1rem;
        }

        .fi-simple-layout .fi-logo {
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.96);
            padding: 0.72rem;
            box-shadow: 0 12px 30px rgba(9, 20, 37, 0.25);
        }

        .fi-simple-layout .fi-simple-header-heading {
            color: #ffffff !important;
            font-size: clamp(1.8rem, 2vw, 2.25rem);
            line-height: 1.05;
            letter-spacing: -0.03em;
        }

        .fi-simple-layout .fi-simple-header-subheading {
            color: var(--pluss-auth-muted) !important;
            font-size: 0.95rem;
            line-height: 1.6;
            max-width: 28rem;
        }

        .fi-simple-layout .fi-fo-field-wrp-label,
        .fi-simple-layout .fi-input-wrp-label,
        .fi-simple-layout .fi-fo-checkbox-list-option-label,
        .fi-simple-layout .fi-fo-field-label,
        .fi-simple-layout .fi-fo-field-label-content {
            color: var(--pluss-auth-ink) !important;
            font-weight: 700 !important;
            letter-spacing: 0.01em;
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.08);
        }

        .fi-simple-layout .fi-input-wrp,
        .fi-simple-layout .fi-fo-text-input,
        .fi-simple-layout .fi-input {
            border-radius: 16px !important;
        }

        .fi-simple-layout .fi-input-wrp {
            background: var(--pluss-auth-field) !important;
            border: 1px solid var(--pluss-auth-field-border) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .fi-simple-layout .fi-input-wrp:focus-within {
            transform: translateY(-1px);
            border-color: var(--pluss-auth-field-focus) !important;
            box-shadow:
                0 0 0 4px rgba(255, 155, 38, 0.14),
                0 14px 28px rgba(7, 15, 27, 0.18);
        }

        .fi-simple-layout .fi-input,
        .fi-simple-layout .fi-fo-text-input {
            color: var(--pluss-auth-input-text) !important;
            font-weight: 600;
        }

        .fi-simple-layout input[type='email'],
        .fi-simple-layout input[type='password'],
        .fi-simple-layout input[type='text'] {
            color: var(--pluss-auth-input-text) !important;
            -webkit-text-fill-color: var(--pluss-auth-input-text) !important;
        }

        .fi-simple-layout .fi-input::placeholder,
        .fi-simple-layout .fi-fo-text-input::placeholder {
            color: var(--pluss-auth-input-placeholder) !important;
        }

        .fi-simple-layout .fi-input-wrp-suffix,
        .fi-simple-layout .fi-input-wrp-prefix,
        .fi-simple-layout .fi-input-wrp .fi-icon {
            color: #2b5fa8 !important;
        }

        .fi-simple-layout .fi-fo-checkbox,
        .fi-simple-layout .fi-checkbox-input {
            border-color: rgba(164, 197, 234, 0.7) !important;
            background-color: rgba(255, 255, 255, 0.92) !important;
        }

        .fi-simple-layout .fi-fo-checkbox-list-option-label,
        .fi-simple-layout .fi-fo-checkbox-list-option-description,
        .fi-simple-layout .fi-fo-checkbox-wrp-label,
        .fi-simple-layout label[for*='remember'],
        .fi-simple-layout [wire\:key*='remember'] label,
        .fi-simple-layout .fi-fo-checkbox label,
        .fi-simple-layout .fi-fo-checkbox label span {
            color: var(--pluss-auth-remember-text) !important;
            font-weight: 600 !important;
        }

        .fi-simple-layout .fi-fo-checkbox .fi-fo-field-label,
        .fi-simple-layout .fi-fo-checkbox .fi-fo-field-label-content,
        .fi-simple-layout .fi-fo-checkbox .fi-fo-field-label-content span {
            color: var(--pluss-auth-remember-text) !important;
            text-shadow: none !important;
        }

        .fi-simple-layout label.fi-fo-field-label:has(input[type='checkbox'][id*='remember']) .fi-fo-field-label-content,
        .fi-simple-layout label.fi-fo-field-label:has(input[type='checkbox'][id*='remember']) .fi-fo-field-label-content span,
        .fi-simple-layout label.fi-fo-field-label:has(input[type='checkbox'][name$='remember']) .fi-fo-field-label-content,
        .fi-simple-layout label.fi-fo-field-label:has(input[type='checkbox'][name$='remember']) .fi-fo-field-label-content span,
        .fi-simple-layout label.fi-fo-field-label:has(input[type='checkbox']) .fi-fo-field-label-content {
            color: var(--pluss-auth-remember-text) !important;
            text-shadow: none !important;
        }

        .fi-simple-layout .fi-fo-checkbox-list-option-description {
            color: var(--pluss-auth-muted) !important;
        }

        .fi-simple-layout .fi-btn.fi-color-custom,
        .fi-simple-layout .fi-btn.fi-color-primary,
        .fi-simple-layout button[type='submit'].fi-btn {
            width: 100%;
            min-height: 3.35rem;
            border-radius: 16px;
            border: none;
            background: linear-gradient(135deg, var(--pluss-auth-button-start), var(--pluss-auth-button-end)) !important;
            color: #1d1204 !important;
            font-size: 0.96rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            box-shadow: 0 18px 30px rgba(234, 122, 0, 0.24);
        }

        .fi-simple-layout .fi-btn.fi-color-custom:hover,
        .fi-simple-layout .fi-btn.fi-color-primary:hover,
        .fi-simple-layout button[type='submit'].fi-btn:hover {
            filter: brightness(1.03);
            transform: translateY(-1px);
        }

        .fi-simple-layout .fi-form {
            display: grid;
            gap: 0.9rem;
        }

        .fi-simple-layout a,
        .fi-simple-layout .fi-link {
            color: #9cc9ff !important;
        }

        .fi-simple-layout .fi-fo-field-wrp-error-message,
        .fi-simple-layout .fi-input-wrp-error-message,
        .fi-simple-layout .text-danger-600 {
            color: #ffd3bf !important;
        }

        .fi-simple-layout .fi-fo-field-wrp-helper-text {
            color: rgba(234, 244, 255, 0.58) !important;
        }

        /* Style amélioré pour le formulaire de recherche dans SearchPage */
        .fi-main .fi-fo-field,
        .fi-main .fi-fo-select-wrp,
        .fi-main .fi-fo-textarea-wrp,
        .fi-main .fi-fo-tags-input-wrp,
        .fi-main .fi-input-wrp,
        .fi-main .fi-fo-text-input,
        .fi-main .fi-fo-date-picker-wrp {
            background: #fff !important;
            border: 1px solid #bcd7f5 !important;
            border-radius: 12px !important;
            box-shadow: 0 2px 8px rgba(23,61,120,0.04);
        }
        .fi-main .fi-fo-field-label,
        .fi-main .fi-fo-field-label-content,
        .fi-main .fi-input-wrp-label {
            color: #12345f !important;
            font-weight: 700 !important;
        }
        .fi-main .fi-input,
        .fi-main .fi-select-input-btn,
        .fi-main .fi-fo-textarea,
        .fi-main .fi-fo-text-input,
        .fi-main .fi-fo-select {
            background-color: #fff !important;
            color: #0f2f5f !important;
            border-color: #bcd7f5 !important;
        }
        .fi-main .fi-input::placeholder,
        .fi-main .fi-fo-textarea::placeholder,
        .fi-main .fi-fo-text-input::placeholder {
            color: #bcd7f5 !important;
            opacity: 1;
        }
        :root {
            --pluss-blue-deep: #173d78;
            --pluss-blue: #2d9cdb;
            --pluss-green: #58b947;
            --pluss-orange: #f08a2b;
            --pluss-paper: #f7fbff;
            --pluss-sidebar-text: #102a4d;
            --pluss-sidebar-muted: #244f82;
            --pluss-sidebar-hover-bg: #dcecff;
            --pluss-sidebar-active-bg: #0f2f5f;
            --pluss-sidebar-active-text: #ffffff;
            --pluss-focus-ring: #f08a2b;
            --pluss-form-bg: #e9f3ff;
            --pluss-form-bg-strong: #d8eaff;
            --pluss-form-border: #bcd7f5;
            --pluss-form-label: #12345f;
            --pluss-form-input-text: #0f2f5f;
        }

        /* Correction très spécifique search-page : texte, icônes, pagination, badges */
        .fi-page .text-gray-400,
        .fi-page .text-gray-500,
        .fi-page .dark\:text-gray-400,
        .fi-page .dark\:text-gray-500,
        .fi-page .dark\:text-gray-200,
        .fi-page .dark\:text-gray-100,
        .fi-page .fi-icon,
        .fi-page .pagination .page-link,
        .fi-page .pagination .page-item.active .page-link,
        .fi-page .badge,
        .fi-page .rounded-full,
        .fi-page .bg-gray-100,
        .fi-page .bg-gray-200,
        .fi-page .bg-gray-800,
        .fi-page .text-blue-600,
        .fi-page .text-emerald-600,
        .fi-page .text-yellow-700 {
            color: #12345f !important;
            border-color: #12345f !important;
        }

        .fi-page .fi-icon {
            color: #1f5ca8 !important;
        }

        .fi-sidebar {
            background: linear-gradient(180deg, #eaf4ff 0%, #dbeafe 100%) !important;
            border-inline-end: 1px solid #bcd7f5 !important;
        }
        .fi-sidebar .fi-sidebar-item-label,
        .fi-sidebar .fi-sidebar-group-label,
        .fi-sidebar .fi-sidebar-item-btn > .fi-icon {
            color: #173d78 !important;
        }

        .fi-topbar {
            background: linear-gradient(90deg, rgba(219, 237, 255, 0.98), rgba(197, 224, 250, 0.97)) !important;
            border-bottom: 1px solid rgba(23, 61, 120, 0.2);
            box-shadow: 0 6px 20px rgba(23, 61, 120, 0.14);
        }

        /* Organisation sur deux paliers : 1er palier (Créer à Recherche), 2e palier (Export à Export PDF registre) */
        .fi-header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: flex-start;
            flex-direction: column;
        }

        .fi-header-actions-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .fi-header-actions-row .fi-action-btn {
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(23,61,120,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }

        .fi-header-actions-row .fi-action-btn:first-child {
            background: var(--pluss-orange);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            box-shadow: 0 4px 16px rgba(240,138,43,0.12);
        }

        .fi-header-actions-row .fi-action-btn:hover {
            box-shadow: 0 4px 16px rgba(23,61,120,0.16);
        }

        /* Correction visibilité bouton Documents GED et icône dans search-page */
        .fi-page button[wire\:click="switchTab('documents')"],
        .fi-page button[wire\:click="switchTab('documents')"] .fi-icon,
        .fi-page button[wire\:click="switchTab('documents')"] .w-4,
        .fi-page button[wire\:click="switchTab('documents')"] .h-4 {
            color: #12345f !important;
        }

        /* Correction globale visibilité : textes gris trop pâles */
        .text-gray-400,
        .text-gray-500,
        .dark\:text-gray-400,
        .dark\:text-gray-500,
        .dark\:text-gray-200,
        .dark\:text-gray-100 {
            color: #12345f !important;
        }

        .fi-icon {
            color: #1f5ca8 !important;
        }

        .fi-topbar .fi-icon-btn > .fi-icon,
        .dark .fi-topbar .fi-topbar-item-btn > .fi-icon,
        .dark .fi-topbar .fi-topbar-database-notifications-btn > .fi-icon,
        .dark .fi-topbar .fi-icon-btn > .fi-icon {
            color: #1f5ca8 !important;
        }

        .fi-sidebar-group-label,
        .fi-sidebar-database-notifications-btn-label,
        .fi-topbar-item-label {
            color: var(--pluss-sidebar-muted) !important;
            font-weight: 800;
        }

        .fi-sidebar-item-label,
        .fi-sidebar-sub-group-items .fi-sidebar-item-label,
        .fi-sidebar-item-btn > .fi-badge,
        .fi-sidebar-item-btn > .fi-icon,
        .fi-sidebar-group-btn > .fi-icon,
        .fi-sidebar-group-dropdown-trigger-btn > .fi-icon {
            color: var(--pluss-sidebar-text) !important;
            transition: color 0.2s;
        }

        /* Palette cyclique sur les icônes du menu latéral */
        .fi-sidebar .fi-sidebar-item-btn:nth-child(1) > .fi-icon { color: var(--pluss-blue) !important; }
        .fi-sidebar .fi-sidebar-item-btn:nth-child(2) > .fi-icon { color: var(--pluss-green) !important; }
        .fi-sidebar .fi-sidebar-item-btn:nth-child(3) > .fi-icon { color: var(--pluss-orange) !important; }
        .fi-sidebar .fi-sidebar-item-btn:nth-child(4) > .fi-icon { color: #a23dbb !important; }
        .fi-sidebar .fi-sidebar-item-btn:nth-child(5) > .fi-icon { color: var(--pluss-blue-deep) !important; }
        .fi-sidebar .fi-sidebar-item-btn:nth-child(6) > .fi-icon { color: var(--pluss-green) !important; }
        .fi-sidebar .fi-sidebar-item-btn:nth-child(7) > .fi-icon { color: var(--pluss-orange) !important; }
        .fi-sidebar .fi-sidebar-item-btn:nth-child(8) > .fi-icon { color: #a23dbb !important; }
        .fi-sidebar .fi-sidebar-item-btn:nth-child(9) > .fi-icon { color: var(--pluss-blue) !important; }
        .fi-sidebar .fi-sidebar-item-btn:nth-child(10) > .fi-icon { color: var(--pluss-green) !important; }

        /* Couleurs institutionnelles par type d'icône (exemples) */
        .fi-sidebar-item-btn > .fi-icon[data-icon*="document"] {
            color: var(--pluss-blue) !important;
        }

        .fi-sidebar-item-btn > .fi-icon[data-icon*="check"] {
            color: var(--pluss-green) !important;
        }

        .fi-sidebar-item-btn > .fi-icon[data-icon*="calendar"] {
            color: var(--pluss-orange) !important;
        }

        .fi-sidebar-item-btn > .fi-icon[data-icon*="users"] {
            color: #a23dbb !important;
        }

        /* Icône active : plus foncé */
        .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon {
            color: var(--pluss-blue-deep) !important;
        }

        .fi-sidebar-item-btn > .fi-badge {
            background: #e5efff !important;
            border: 1px solid rgba(16, 42, 77, 0.2);
            font-weight: 800;
        }

        .fi-sidebar-item.fi-sidebar-item-has-url > .fi-sidebar-item-btn:hover,
        .fi-sidebar-item.fi-sidebar-item-has-url > .fi-sidebar-item-btn:focus-visible,
        .fi-sidebar-group-dropdown-trigger-btn:hover,
        .fi-sidebar-group-dropdown-trigger-btn:focus-visible {
            background: var(--pluss-sidebar-hover-bg) !important;
        }

        .fi-sidebar-item-btn:focus-visible,
        .fi-sidebar-group-dropdown-trigger-btn:focus-visible,
        .fi-sidebar-group-collapse-btn:focus-visible {
            outline: 2px solid var(--pluss-focus-ring) !important;
            outline-offset: 2px;
        }

        .fi-sidebar-item.fi-active > .fi-sidebar-item-btn,
        .fi-sidebar-item.fi-sidebar-item-has-active-child-items > .fi-sidebar-item-btn {
            background: var(--pluss-sidebar-active-bg) !important;
            box-shadow: 0 8px 18px rgba(23, 61, 120, 0.28);
        }

        .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label,
        .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon,
        .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-badge,
        .fi-sidebar-item.fi-sidebar-item-has-active-child-items > .fi-sidebar-item-btn > .fi-sidebar-item-label,
        .fi-sidebar-item.fi-sidebar-item-has-active-child-items > .fi-sidebar-item-btn > .fi-icon,
        .fi-sidebar-item.fi-sidebar-item-has-active-child-items > .fi-sidebar-item-btn > .fi-badge {
            color: var(--pluss-sidebar-active-text) !important;
        }

        .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-badge,
        .fi-sidebar-item.fi-sidebar-item-has-active-child-items > .fi-sidebar-item-btn > .fi-badge {
            background: rgba(255, 255, 255, 0.2) !important;
            border-color: rgba(255, 255, 255, 0.42);
        }

        .fi-fo-field,
        .fi-fo-builder-item,
        .fi-fo-checkbox-list,
        .fi-fo-rich-editor,
        .fi-fo-tags-input-wrp,
        .fi-fo-select-wrp,
        .fi-fo-textarea-wrp {
            background: linear-gradient(180deg, var(--pluss-form-bg), var(--pluss-form-bg-strong));
        }

        .fi-select-input-value-label,
        .fi-fo-rich-editor [contenteditable='true'],
        .fi-fo-rich-editor .tiptap,
        .fi-fo-rich-editor .ProseMirror {
            color: var(--pluss-form-input-text) !important;
            -webkit-text-fill-color: var(--pluss-form-input-text) !important;
            caret-color: var(--pluss-form-input-text) !important;
            background-color: #ffffff;
        }

        /* Correction pour la lisibilité des listes de sélection en mode sombre */
        .dark .fi-input-wrp select,
        .dark .fi-fo-select,
        .dark .fi-select-input-value-ctn,
        .dark .fi-select-input-value-label {
            background-color: #e5e7eb !important;
            color: #2d9cdb !important;
            font-size: 1.08em !important;
            font-weight: 700 !important;
            text-shadow: none !important;
        }

        .fi-input::placeholder,
        .fi-fo-textarea::placeholder,
        .fi-fo-text-input::placeholder,
        .fi-select-input-placeholder {
            color: #5a7da6 !important;
            opacity: 1;
        }

        .fi-fo-field-wrp-error-message {
            font-weight: 700;
        }

        /* Form readability: helper/hint text and bottom action-area captions. */
        .fi-main .fi-fo-field-wrp-helper-text,
        .fi-main .fi-fo-field-wrp-hint,
        .fi-main .fi-fo-field-wrp .text-gray-500,
        .fi-main .fi-fo-field-wrp .text-gray-400,
        .fi-main .fi-fo-field-wrp .text-gray-300,
        .fi-main .fi-fo-actions,
        .fi-main .fi-fo-actions p,
        .fi-main .fi-fo-actions span,
        .fi-main .fi-fo-actions .fi-link,
        .fi-main .fi-fo-actions .text-gray-500,
        .fi-main .fi-fo-actions .text-gray-400,
        .fi-main .fi-fo-actions .text-gray-300 {
            color: #12345f !important;
        }

        .dark .fi-main .fi-fo-field-wrp-helper-text,
        .dark .fi-main .fi-fo-field-wrp-hint,
        .dark .fi-main .fi-fo-field-wrp .text-gray-500,
        .dark .fi-main .fi-fo-field-wrp .text-gray-400,
        .dark .fi-main .fi-fo-field-wrp .text-gray-300,
        .dark .fi-main .fi-fo-actions,
        .dark .fi-main .fi-fo-actions p,
        .dark .fi-main .fi-fo-actions span,
        .dark .fi-main .fi-fo-actions .fi-link,
        .dark .fi-main .fi-fo-actions .text-gray-500,
        .dark .fi-main .fi-fo-actions .text-gray-400,
        .dark .fi-main .fi-fo-actions .text-gray-300 {
            color: #12345f !important;
        }

        /* Correction visibilité search-page : entête et bas de page */
        .fi-page.search-page .text-gray-400,
        .fi-page.search-page .text-gray-500,
        .fi-page.search-page .dark\:text-gray-400,
        .fi-page.search-page .dark\:text-gray-500,
        .fi-page.search-page .dark\:text-gray-200,
        .fi-page.search-page .dark\:text-gray-100 {
            color: #12345f !important;
        }

        .fi-page.search-page .fi-icon {
            color: #1f5ca8 !important;
        }

        /* Filament v5 component-specific readability fixes. */
        .fi-main .fi-fo-radio > .fi-fo-radio-label > .fi-fo-radio-label-text,
        .fi-main .fi-fo-radio .fi-fo-radio-label-description,
        .fi-main .fi-sc-actions .fi-sc-actions-label,
        .fi-main .fi-sc-actions .fi-sc-actions-label-ctn,
        .fi-main .fi-sc-actions .fi-sc-actions-label-ctn span,
        .fi-main .fi-fo-key-value-table > thead > tr > th,
        .fi-main .fi-fo-key-value-table > tbody > tr > td,
        .fi-main .fi-fo-key-value .fi-input,
        .fi-main .fi-in-key-value th,
        .fi-main .fi-in-key-value td,
        .fi-main .fi-in-key-value td.fi-in-placeholder {
            color: #12345f !important;
        }

        .fi-main .fi-fo-key-value .fi-input::placeholder {
            color: #5a7da6 !important;
            opacity: 1;
        }

        .dark .fi-main .fi-fo-radio > .fi-fo-radio-label > .fi-fo-radio-label-text,
        .dark .fi-main .fi-fo-radio .fi-fo-radio-label-description,
        .dark .fi-main .fi-sc-actions .fi-sc-actions-label,
        .dark .fi-main .fi-sc-actions .fi-sc-actions-label-ctn,
        .dark .fi-main .fi-sc-actions .fi-sc-actions-label-ctn span,
        .dark .fi-main .fi-fo-key-value-table > thead > tr > th,
        .dark .fi-main .fi-fo-key-value-table > tbody > tr > td,
        .dark .fi-main .fi-fo-key-value .fi-input,
        .dark .fi-main .fi-in-key-value th,
        .dark .fi-main .fi-in-key-value td,
        .dark .fi-main .fi-in-key-value td.fi-in-placeholder {
            color: #12345f !important;
        }

        .dark .fi-main .fi-fo-key-value .fi-input::placeholder {
            color: #5a7da6 !important;
            opacity: 1;
        }

        /* Keep action button labels readable in the two remaining problematic zones. */
        .fi-main .fi-sc-actions .fi-ac .fi-btn,
        .fi-main .fi-sc-actions .fi-ac .fi-btn span,
        .fi-main .fi-sc-actions .fi-ac .fi-btn > .fi-icon,
        .fi-main .fi-fo-key-value-add-action-ctn .fi-btn,
        .fi-main .fi-fo-key-value-add-action-ctn .fi-btn span,
        .fi-main .fi-fo-key-value-add-action-ctn .fi-btn > .fi-icon {
            color: #12345f !important;
        }

        .dark .fi-main .fi-sc-actions .fi-ac .fi-btn,
        .dark .fi-main .fi-sc-actions .fi-ac .fi-btn span,
        .dark .fi-main .fi-sc-actions .fi-ac .fi-btn > .fi-icon,
        .dark .fi-main .fi-fo-key-value-add-action-ctn .fi-btn,
        .dark .fi-main .fi-fo-key-value-add-action-ctn .fi-btn span,
        .dark .fi-main .fi-fo-key-value-add-action-ctn .fi-btn > .fi-icon {
            color: #12345f !important;
        }

        /* Dedicated style for EditDocument release lock action. */
        .fi-main .fi-header-actions-ctn .pluss-release-lock-action,
        .fi-main .fi-header-actions-ctn .pluss-release-lock-action > .fi-icon,
        .dark .fi-main .fi-header-actions-ctn .pluss-release-lock-action,
        .dark .fi-main .fi-header-actions-ctn .pluss-release-lock-action > .fi-icon {
            color: #12345f !important;
        }

        .fi-main .fi-header-actions-ctn .pluss-release-lock-action.fi-outlined,
        .dark .fi-main .fi-header-actions-ctn .pluss-release-lock-action.fi-outlined {
            background: #ffffff !important;
            border-color: #6ea2db !important;
        }

        .fi-main .fi-header-actions-ctn .pluss-release-lock-action.fi-disabled,
        .fi-main .fi-header-actions-ctn .pluss-release-lock-action[disabled],
        .dark .fi-main .fi-header-actions-ctn .pluss-release-lock-action.fi-disabled,
        .dark .fi-main .fi-header-actions-ctn .pluss-release-lock-action[disabled] {
            opacity: 0.9 !important;
        }

        .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action,
        .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action .fi-btn-label,
        .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action .fi-btn-icon,
        .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action .fi-icon,
        .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action span,
        .dark .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action,
        .dark .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action .fi-btn-label,
        .dark .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action .fi-btn-icon,
        .dark .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action .fi-icon,
        .dark .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action span {
            color: #ffffff !important;
        }

        .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action.fi-btn,
        .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action,
        .dark .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action.fi-btn,
        .dark .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action {
            background: linear-gradient(135deg, #2f8f3a 0%, #1f6a2a 100%) !important;
            border-color: #1f6a2a !important;
            box-shadow: 0 12px 24px rgba(31, 106, 42, 0.18) !important;
        }

        .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action:hover,
        .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action:focus-visible,
        .dark .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action:hover,
        .dark .fi-main .fi-header-actions-ctn .pluss-scan-folder-preview-action:focus-visible {
            filter: brightness(1.04);
        }

        .fi-section .fi-section-header-heading,
        .fi-section .fi-section-header-description {
            color: #12345f !important;
        }

        .fi-section .fi-section-header-heading {
            font-weight: 800 !important;
        }

        .fi-fo-checkbox-list .fi-fo-checkbox-list-option-ctn {
            background: #ffffff;
            border: 1px solid #c8dcf3;
            border-radius: 12px;
            padding: 0.5rem 0.65rem;
        }

        .fi-fo-checkbox-list .fi-fo-checkbox-list-option-ctn:hover {
            background: #eef6ff;
            border-color: #9fc0e4;
        }

        .fi-fo-checkbox-list .fi-fo-checkbox-list-option-label {
            color: #0f2f5f !important;
            font-weight: 700 !important;
            line-height: 1.35 !important;
        }

        .fi-fo-checkbox-list .fi-fo-checkbox-list-option-description,
        .fi-fo-checkbox-list .fi-fo-checkbox-list-no-search-results-message {
            color: #3a5f8a !important;
        }

        .fi-fo-checkbox-list .fi-fo-checkbox-list-search-input-wrp .fi-input {
            color: #0f2f5f !important;
            background: #ffffff !important;
            border: 1px solid #9fc0e4 !important;
        }

        input[type='checkbox'].fi-checkbox-input {
            border: 1px solid #6c95c7 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 1px rgba(108, 149, 199, 0.25) !important;
        }

        input[type='checkbox'].fi-checkbox-input:checked,
        input[type='checkbox'].fi-checkbox-input:indeterminate {
            background-color: #1f5ca8 !important;
            border-color: #1f5ca8 !important;
        }

        .fi-ta-search-field,
        .fi-ta-filters,
        .fi-ta-col-manager,
        .fi-global-search-field,
        .fi-global-search-results-ctn {
            background: linear-gradient(180deg, #eff7ff, #e3f0ff);
            border: 1px solid #bdd6f3;
            border-radius: 14px;
        }

        .fi-ta-search-field,
        .fi-global-search-field {
            padding: 0.45rem;
        }

        .fi-ta-search-field .fi-input-wrp,
        .fi-global-search-field .fi-input-wrp,
        .fi-ta-filters .fi-input-wrp,
        .fi-ta-col-manager .fi-input-wrp {
            background: #ffffff !important;
            border: 1px solid rgba(18, 52, 95, 0.28) !important;
        }

        .fi-ta-filters,
        .fi-ta-col-manager {
            padding: 0.9rem;
        }

        .fi-ta-filters .fi-fo-field,
        .fi-ta-filters .fi-fo-select-wrp,
        .fi-ta-filters .fi-fo-textarea-wrp,
        .fi-ta-filters .fi-fo-tags-input-wrp {
            background: linear-gradient(180deg, #eaf4ff, #dcecff);
            border-color: #b7d2f0;
        }

        .fi-ta-filters-heading,
        .fi-ta-col-manager-heading,
        .fi-ta-filter-indicators-label,
        .fi-global-search-result-group-header,
        .fi-global-search-result-heading {
            color: #12345f !important;
            font-weight: 800;
        }

        .fi-global-search-result-detail-label,
        .fi-global-search-result-detail-value,
        .fi-global-search-no-results-message {
            color: #274d79 !important;
        }

        .fi-ta-filter-indicators,
        .fi-ta-filter-indicators-badges-ctn {
            background: #eef6ff;
            border: 1px solid #c4daf4;
            border-radius: 12px;
            padding: 0.4rem 0.55rem;
        }

        .fi-ta,
        .fi-ta * {
            text-shadow: none;
        }

        .fi-ta-ctn,
        .fi-ta-content,
        .fi-ta-content-ctn,
        .fi-ta-content .fi-ta-record,
        .fi-ta-table,
        .fi-ta-table thead,
        .fi-ta-table tbody,
        .fi-ta-table tr,
        .fi-ta-table td {
            background: #fbfdff !important;
        }

        .fi-ta-table tbody tr:nth-child(odd),
        .fi-ta-table tbody tr:nth-child(odd) td {
            background: #ffffff !important;
        }

        .fi-ta-table tbody tr:nth-child(even),
        .fi-ta-table tbody tr:nth-child(even) td {
            background: #f4f8fc !important;
        }

        .fi-ta-table tbody tr:hover,
        .fi-ta-table tbody tr:hover td {
            background: #eaf2fb !important;
        }

        .fi-ta-table thead tr,
        .fi-ta-table .fi-ta-table-stacked-header-row {
            background: #dbeafe !important;
            border-bottom: 2px solid #9fc4eb !important;
        }

        .fi-ta-header-cell,
        .fi-ta-table-stacked-header-cell,
        .fi-ta-header-cell-sort-btn,
        .fi-ta-header-heading {
            color: #0c2f5d !important;
            font-weight: 800 !important;
        }

        .fi-ta-header-cell .fi-icon,
        .fi-ta-header-cell-sort-btn .fi-icon,
        .fi-ta-header-cell.fi-ta-header-cell-sorted .fi-icon {
            color: #0c2f5d !important;
            opacity: 1 !important;
        }

        .fi-ta,
        .fi-ta .fi-ta-cell,
        .fi-ta .fi-ta-cell-content,
        .fi-ta .fi-ta-col,
        .fi-ta .fi-link,
        .fi-ta .fi-ta-group-heading,
        .fi-ta .fi-ta-group-description,
        .fi-ta .fi-ta-header-cell,
        .fi-ta .fi-ta-header-heading,
        .fi-ta .fi-ta-text,
        .fi-ta .fi-ta-text-item,
        .fi-ta .fi-ta-text-item-label,
        .fi-ta .fi-ta-text-item-description,
        .fi-ta .fi-dropdown-list-item,
        .fi-ta .fi-dropdown-list-item-label {
            color: #111111 !important;
        }

        .fi-ta .fi-link {
            text-decoration-color: rgba(17, 17, 17, 0.35);
        }

        .pluss-audit-export-action,
        .pluss-audit-export-action .fi-btn,
        .pluss-audit-export-action .fi-btn-label,
        .pluss-audit-export-action .fi-btn-icon,
        .pluss-audit-export-action span,
        .pluss-audit-export-action svg {
            color: #ffffff !important;
        }

        .pluss-audit-export-action--csv,
        .pluss-audit-export-action--csv .fi-btn {
            background: linear-gradient(135deg, #1f6fb2 0%, #173d78 100%) !important;
            border-color: #173d78 !important;
            box-shadow: 0 12px 24px rgba(23, 61, 120, 0.18) !important;
        }

        .pluss-audit-export-action--xlsx,
        .pluss-audit-export-action--xlsx .fi-btn {
            background: linear-gradient(135deg, #2f8f3a 0%, #1f6a2a 100%) !important;
            border-color: #1f6a2a !important;
            box-shadow: 0 12px 24px rgba(31, 106, 42, 0.18) !important;
        }

        .pluss-audit-export-action:hover,
        .pluss-audit-export-action:focus-visible,
        .pluss-audit-export-action .fi-btn:hover,
        .pluss-audit-export-action .fi-btn:focus-visible {
            filter: brightness(1.03);
        }

        /* Keep table action buttons readable in relation manager tabs. */
        .fi-ta-header-toolbar .fi-btn,
        .fi-ta-header-toolbar .fi-ac-btn,
        .fi-ta-header-toolbar .fi-btn-label,
        .fi-ta-header-toolbar .fi-btn-icon,
        .fi-ta-header-toolbar-actions .fi-btn,
        .fi-ta-header-toolbar-actions .fi-ac-btn,
        .fi-ta-header-toolbar-actions .fi-btn-label,
        .fi-ta-header-toolbar-actions .fi-btn-icon,
        .fi-ta-actions .fi-btn,
        .fi-ta-actions .fi-ac-btn,
        .fi-ta-actions .fi-btn-label,
        .fi-ta-actions .fi-btn-icon {
            color: #12345f !important;
        }

        .fi-ta,
        .fi-in {
            backdrop-filter: none !important;
        }

        .fi-ta .fi-badge,
        .fi-ta .fi-badge .fi-badge-label,
        .fi-ta .fi-badge .fi-icon,
        .fi-ta .fi-badge.fi-color,
        .fi-ta .fi-icon.fi-color {
            opacity: 1 !important;
            filter: none !important;
            font-weight: 800;
        }

        .fi-ta .fi-color,
        .fi-ta .fi-badge.fi-color,
        .fi-ta .fi-link.fi-color,
        .fi-ta .fi-icon.fi-color {
            --text: var(--color-800);
            --hover-text: var(--color-900);
            --dark-text: var(--color-900);
            --dark-hover-text: var(--color-950);
        }

        .fi-ta .fi-badge.fi-color {
            background-color: var(--color-100) !important;
            color: var(--color-900) !important;
            border: 1px solid var(--color-300) !important;
            --tw-ring-color: var(--color-700) !important;
        }

        .fi-ta .fi-badge.fi-color .fi-icon,
        .fi-ta .fi-badge.fi-color .fi-badge-label {
            color: var(--color-900) !important;
        }

        .fi-ta .fi-link.fi-color,
        .fi-ta .fi-icon.fi-color,
        .fi-ta .fi-dropdown-header.fi-color span,
        .fi-ta .fi-dropdown-list-item.fi-color .fi-dropdown-list-item-label {
            color: var(--color-800) !important;
            font-weight: 700;
        }

        .fi-ta .fi-filter-indicators-badges-ctn .fi-badge,
        .fi-ta .fi-ta-filter-indicators-badges-ctn .fi-badge {
            letter-spacing: 0.01em;
            border-width: 1px;
        }

        .fi-ta .fi-ta-row,
        .fi-ta .fi-ta-cell {
            border-color: rgba(18, 52, 95, 0.16);
        }

        .fi-fo-builder-item .fi-select-input-value-badges-ctn .fi-select-input-badge,
        .fi-fo-builder-item .fi-fo-tags-input-tags-ctn .fi-badge,
        .fi-fo-builder-item .fi-badge.fi-color,
        .fi-fo-builder-item .fi-select-input-value-badges-ctn .fi-select-input-badge .fi-badge-label,
        .fi-fo-builder-item .fi-fo-tags-input-tags-ctn .fi-badge .fi-badge-label,
        .fi-fo-builder-item .fi-badge.fi-color .fi-badge-label {
            background: #e6f1ff !important;
            border: 1px solid #8eb7e6 !important;
            color: #0d325f !important;
            font-weight: 700;
            --text: #0d325f;
            --dark-text: #0d325f;
        }

        .fi-fo-builder-item .fi-select-input-value-badges-ctn .fi-select-input-badge .fi-icon,
        .fi-fo-builder-item .fi-fo-tags-input-tags-ctn .fi-badge .fi-icon,
        .fi-fo-builder-item .fi-badge.fi-color .fi-icon {
            color: #0d325f !important;
        }

        .fi-body {
            position: relative;
            isolation: isolate;
            overflow-x: hidden;
            overflow-y: auto;
            background:
                radial-gradient(circle at 12% 18%, rgba(53, 119, 197, 0.2), transparent 20%),
                radial-gradient(circle at 85% 12%, rgba(255, 155, 38, 0.16), transparent 18%),
                radial-gradient(circle at 78% 82%, rgba(88, 185, 71, 0.14), transparent 20%),
                linear-gradient(180deg, #edf4fb 0%, #dce9f7 100%);
        }

        .fi-body::before,
        .fi-body::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(10px);
            z-index: -1;
        }

        .fi-body::before {
            top: -6rem;
            left: -4rem;
            width: 18rem;
            height: 18rem;
            background: rgba(31, 92, 168, 0.16);
        }

        .fi-body::after {
            right: -5rem;
            bottom: -7rem;
            width: 22rem;
            height: 22rem;
            background: rgba(240, 138, 43, 0.12);
        }

        .fi-main,
        .fi-page,
        .fi-page-content {
            background: transparent !important;
        }

        .fi-section,
        .fi-wi,
        .fi-ta,
        .fi-tabs,
        .fi-in,
        .fi-fo-field-wrp,
        .fi-wi-stats-overview-stat {
            border-radius: 18px;
            border: 1px solid rgba(23, 61, 120, 0.14);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 252, 255, 0.94));
            box-shadow: 0 12px 34px rgba(23, 61, 120, 0.09);
            backdrop-filter: blur(4px);
        }

        .fi-header-heading {
            color: var(--pluss-blue-deep);
            letter-spacing: 0.01em;
            font-weight: 800;
        }

        /* Targeted readability fix for "Afficher / Detail et partage" values only.
           Keep it strict to avoid affecting actions/buttons. */
        .fi-main .fi-in-entry-label,
        .fi-main .fi-in-entry-content,
        .fi-main .fi-in-entry-content-ctn,
        .fi-main .fi-in-text,
        .fi-main .fi-in-text-item,
        .fi-main .fi-in-text-item-label,
        .fi-main .fi-in-text-item-content,
        .fi-main .fi-in-entry,
        .fi-main .fi-in .text-gray-500,
        .fi-main .fi-in .text-gray-400,
        .fi-main .fi-in .text-gray-300,
        .fi-main .fi-in .text-gray-200,
        .fi-main .fi-in .dark\:text-gray-500,
        .fi-main .fi-in .dark\:text-gray-400,
        .fi-main .fi-in .dark\:text-gray-300,
        .fi-main .fi-in .dark\:text-gray-200,
        .fi-main .fi-in .dark\:text-gray-100 {
            color: var(--pluss-form-input-text) !important;
        }

        .fi-main .fi-tabs-item-label,
        .fi-main .fi-section-header-heading,
        .fi-main .fi-section-header-description {
            color: #12345f !important;
        }

        .dark .fi-main .fi-in-entry-label,
        .dark .fi-main .fi-in-entry-content,
        .dark .fi-main .fi-in-entry-content-ctn,
        .dark .fi-main .fi-in-text,
        .dark .fi-main .fi-in-text-item,
        .dark .fi-main .fi-in-text-item-label,
        .dark .fi-main .fi-in-text-item-content,
        .dark .fi-main .fi-in-entry,
        .dark .fi-main .fi-in .text-gray-500,
        .dark .fi-main .fi-in .text-gray-400,
        .dark .fi-main .fi-in .text-gray-300,
        .dark .fi-main .fi-in .text-gray-200,
        .dark .fi-main .fi-in .dark\:text-gray-500,
        .dark .fi-main .fi-in .dark\:text-gray-400,
        .dark .fi-main .fi-in .dark\:text-gray-300,
        .dark .fi-main .fi-in .dark\:text-gray-200,
        .dark .fi-main .fi-in .dark\:text-gray-100 {
            color: var(--pluss-form-input-text) !important;
        }

        .dark .fi-main .fi-tabs-item-label,
        .dark .fi-main .fi-section-header-heading,
        .dark .fi-main .fi-section-header-description {
            color: #12345f !important;
        }

        .fi-badge,
        .fi-wi-stats-overview-stat-value {
            color: var(--pluss-blue-deep);
        }

        .pluss-home-hero {
            position: relative;
            overflow: hidden;
            border-radius: 22px;
            padding: clamp(1rem, 2.6vw, 2.2rem);
            background: linear-gradient(130deg, #173d78 0%, #1f5ca8 41%, #2d9cdb 100%);
            color: #ffffff;
            box-shadow: 0 28px 52px rgba(23, 61, 120, 0.32);
        }

        .pluss-home-hero__glow {
            position: absolute;
            inset: auto -4rem -5rem auto;
            width: 18rem;
            height: 18rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(88, 185, 71, 0.45) 0%, rgba(88, 185, 71, 0) 70%);
            pointer-events: none;
        }

        .pluss-home-hero__content {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 0.85rem;
            max-width: 76ch;
            margin-inline: auto;
            text-align: center;
        }

        .pluss-home-hero__eyebrow {
            margin: 0;
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.92;
        }

        .pluss-home-hero__title {
            margin: 0;
            font-size: clamp(1rem, 2.1vw, 1.55rem);
            line-height: 1.35;
            font-weight: 800;
            max-width: 72ch;
            margin-inline: auto;
        }

        .pluss-home-hero__subtitle {
            margin: 0;
            font-size: 0.96rem;
            opacity: 0.95;
            max-width: 64ch;
            margin-inline: auto;
        }

        .pluss-home-hero__meta {
            margin-top: 0.65rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 0.7rem;
            width: min(100%, 760px);
            margin-inline: auto;
        }

        .pluss-home-hero__meta > div {
            border-radius: 12px;
            border: 1px solid #bcd7f5;
            background: #f7fbff;
            padding: 0.7rem 0.85rem;
            display: grid;
            gap: 0.25rem;
            justify-items: center;
            text-align: center;
        }

        .pluss-home-hero__meta-label {
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            opacity: 1;
            color: #5c7fa8;
        }

        .pluss-home-hero__meta strong {
            font-size: 1rem;
            font-weight: 800;
            color: #163d73;
        }

        /* Correction finale couleur textarea Filament */
        .fi-fo-textarea,
        .fi-fo-textarea.filament-description-field,
        .filament-description-field textarea,
        textarea.fi-fo-textarea,
        textarea.filament-description-field,
        .fi-main textarea,
        .fi-main .fi-fo-textarea,
        .fi-main .filament-description-field textarea {
            background-color: #fff !important;
            color: #0f2f5f !important;
            border-color: #bcd7f5 !important;
        }

        /* Placeholder couleur pour textarea */
        .fi-main textarea::placeholder,
        .fi-main .fi-fo-textarea::placeholder,
        .fi-main .filament-description-field textarea::placeholder {
            color: #5a7da6 !important;
            opacity: 1;
        }

        /* Correction ciblée pour les champs d'upload Filament / Media Library */
        .fi-main .fi-fo-file-upload .filepond--drop-label label,
        .fi-main .fi-fo-file-upload .filepond--label-action,
        .fi-main .fi-fo-file-upload .filepond--file-info-main,
        .fi-main .fi-fo-file-upload .filepond--file-info-sub,
        .fi-main .fi-fo-file-upload .filepond--file-status-main,
        .fi-main .fi-fo-file-upload .filepond--file-status-sub,
        .fi-main .fi-fo-file-upload [class*='filepond--file-info'],
        .fi-main .fi-fo-file-upload [class*='filepond--file-status'],
        .fi-main .fi-fo-file-upload [class*='filepond--drop-label'],
        .fi-main .fi-fo-file-upload [class*='filepond--label-action'] {
            color: #0f2f5f !important;
            -webkit-text-fill-color: #0f2f5f !important;
        }

        .fi-main .fi-fo-file-upload .filepond--root,
        .fi-main .fi-fo-file-upload .filepond--panel-root,
        .fi-main .fi-fo-file-upload .filepond--item-panel,
        .fi-main .fi-fo-file-upload .filepond--file {
            background-color: #ffffff !important;
        }

        .fi-main .fi-fo-file-upload .filepond--drop-label,
        .fi-main .fi-fo-file-upload .filepond--file {
            color: #0f2f5f !important;
        }

        .dark .fi-main .fi-fo-file-upload .filepond--drop-label label,
        .dark .fi-main .fi-fo-file-upload .filepond--label-action,
        .dark .fi-main .fi-fo-file-upload .filepond--file-info-main,
        .dark .fi-main .fi-fo-file-upload .filepond--file-info-sub,
        .dark .fi-main .fi-fo-file-upload .filepond--file-status-main,
        .dark .fi-main .fi-fo-file-upload .filepond--file-status-sub,
        .dark .fi-main .fi-fo-file-upload [class*='filepond--file-info'],
        .dark .fi-main .fi-fo-file-upload [class*='filepond--file-status'],
        .dark .fi-main .fi-fo-file-upload [class*='filepond--drop-label'],
        .dark .fi-main .fi-fo-file-upload [class*='filepond--label-action'] {
            color: #0f2f5f !important;
            -webkit-text-fill-color: #0f2f5f !important;
        }

        /* Correction de lisibilité pour badges, aides et listes déroulantes des formulaires */
        .fi-main .fi-fo-field-wrp-helper-text,
        .fi-main .fi-fo-field-wrp-hint,
        .fi-main .fi-fo-field-wrp-description,
        .fi-main .fi-dropdown-header span,
        .fi-main .fi-dropdown-list-item,
        .fi-main .fi-dropdown-list-item-label,
        .fi-main .fi-select-input-value-label,
        .fi-main .fi-badge,
        .fi-main .fi-badge .fi-badge-label,
        .fi-main .fi-badge .fi-icon,
        .fi-main .fi-select-input-badge,
        .fi-main .fi-select-input-badge .fi-badge-label,
        .fi-main .fi-select-input-badge .fi-icon,
        .fi-main .fi-fo-tags-input-tags-ctn .fi-badge,
        .fi-main .fi-fo-tags-input-tags-ctn .fi-badge .fi-badge-label,
        .fi-main .fi-fo-tags-input-tags-ctn .fi-badge .fi-icon {
            color: #12345f !important;
            -webkit-text-fill-color: #12345f !important;
        }

        .fi-main .fi-dropdown-panel,
        .fi-main .fi-dropdown-list {
            background: #ffffff !important;
        }

        .fi-main .fi-badge,
        .fi-main .fi-select-input-badge,
        .fi-main .fi-fo-tags-input-tags-ctn .fi-badge {
            background: #e8f2ff !important;
            border: 1px solid #bcd7f5 !important;
        }

        .fi-modal > .fi-modal-window-ctn > .fi-modal-window,
        .dark .fi-modal > .fi-modal-window-ctn > .fi-modal-window {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 251, 255, 0.96)) !important;
            border: 1px solid rgba(23, 61, 120, 0.14) !important;
            box-shadow: 0 24px 60px rgba(9, 20, 37, 0.24) !important;
            color: #12345f !important;
        }

        .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-header,
        .dark .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(23, 61, 120, 0.1);
        }

        .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-content,
        .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-footer,
        .dark .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-content,
        .dark .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-footer {
            background: transparent !important;
        }

        .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-heading,
        .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-description,
        .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-close-btn,
        .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-close-btn .fi-icon,
        .dark .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-heading,
        .dark .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-description,
        .dark .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-close-btn,
        .dark .fi-modal > .fi-modal-window-ctn > .fi-modal-window .fi-modal-close-btn .fi-icon {
            color: #12345f !important;
        }

        .dark .fi-main .fi-fo-field-wrp-helper-text,
        .dark .fi-main .fi-fo-field-wrp-hint,
        .dark .fi-main .fi-fo-field-wrp-description,
        .dark .fi-main .fi-dropdown-header span,
        .dark .fi-main .fi-dropdown-list-item,
        .dark .fi-main .fi-dropdown-list-item-label,
        .dark .fi-main .fi-select-input-value-label,
        .dark .fi-main .fi-badge,
        .dark .fi-main .fi-badge .fi-badge-label,
        .dark .fi-main .fi-badge .fi-icon,
        .dark .fi-main .fi-select-input-badge,
        .dark .fi-main .fi-select-input-badge .fi-badge-label,
        .dark .fi-main .fi-select-input-badge .fi-icon,
        .dark .fi-main .fi-fo-tags-input-tags-ctn .fi-badge,
        .dark .fi-main .fi-fo-tags-input-tags-ctn .fi-badge .fi-badge-label,
        .dark .fi-main .fi-fo-tags-input-tags-ctn .fi-badge .fi-icon {
            color: #12345f !important;
            -webkit-text-fill-color: #12345f !important;
        }

    /* Style Metadonnées*/
                                                    /* Séparateurs visibles pour KeyValue Filament (structure div) */
                                                .fi-fo-key-value > div > div:not(:last-child) {
                                                    border-bottom: 2.5px solid #f08a2b !important;
                                                }
                                        /* Lignes intérieures très visibles dans le KeyValue (fond orange pâle + bordure orange vif) */
                                        .fi-fo-key-value tbody tr {
                                            background: #fff7ed !important; /* orange très pâle */
                                        }
                                        .fi-fo-key-value tbody td {
                                            border-bottom: 2.5px solid #f08a2b !important;
                                            background: #fff7ed !important;
                                        }
                                        .fi-fo-key-value tbody tr:last-child td {
                                            border-bottom: none !important;
                                        }
                                /* Lignes intérieures visibles dans le KeyValue */
                                .fi-fo-key-value tbody tr {
                                    border-bottom: 2px solid #f08a2b !important; /* orange vif */
                                }
                                .fi-fo-key-value tbody tr:last-child {
                                    border-bottom: none !important;
                                }
                                .fi-fo-key-value tbody td {
                                    border: none !important;
                                }
                        /* Bordure toujours visible pour le KeyValue même sans lignes */
                        .fi-fo-key-value {
                            border: 2px solid #173d78 !important;
                            border-radius: 10px !important;
                            background: #fff !important;
                        }
                        .fi-fo-key-value thead th {
                            border-bottom: 1.5px solid #173d78 !important;
                            color: #173d78 !important;
                            font-weight: 700 !important;
                        }
                        .fi-fo-key-value tbody td {
                            border: none !important;
                        }
                        .fi-fo-key-value .fi-fo-key-value-row {
                            border-bottom: 1.5px solid #173d78 !important;
                        }
                /* Rendre les lignes du KeyValue bien visibles en bleu */
                .fi-fo-key-value .fi-fo-key-value-row,
                .fi-fo-key-value .fi-fo-key-value-row input,
                .fi-fo-key-value .fi-fo-key-value-row select,
                .fi-fo-key-value .fi-fo-key-value-row textarea {
                    border-color: #173d78 !important;
                    border-width: 1.5px !important;
                }

                .fi-fo-key-value .fi-fo-key-value-row {
                    border-bottom: 1.5px solid #173d78 !important;
                }
        /* Style spécifique pour le label Métadonnées dans le formulaire de document */
        label:has(.fi-fo-field-label-content:contains('Métadonnées')),
        .fi-fo-field-label-content:contains('Métadonnées') {
            color: #173d78 !important;
            font-weight: 900 !important;
            font-size: 1.08em !important;
            letter-spacing: 0.01em;
        }
    
         /* Séparateur de colonne robuste pour KeyValue (bordure droite orange + fond pâle sur la colonne Clé) */
        .fi-fo-key-value [class*="key-value-row"] > *:first-child {
            border-right: 3.5px solid #f08a2b !important;
            background: #4b3e2a !important;
        }

         /* Style spécifique pour les badges de statut dans le KeyValue (fond vert pâle + texte vert foncé) */
        .fi-fo-key-value .fi-fo-key-value-row .fi-fo-badge {
            background-color: #4d8b5e !important;
            color: #2e7d32 !important;
        }

    </style>
@endonce