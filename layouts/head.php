<?php global $pageTitle; ?>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script>
        // Screen-size based mobile/tablet redirect
        if (window.innerWidth < 1024) {
            const basePath = '<?php echo appBasePath(); ?>';
            window.location.href = basePath + '/mobile';
        }
    </script>
    <!-- Premium Google Fonts: Geist -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Geist', sans-serif !important;
        }
        
        /* Custom Select Styles - Critical for hiding/showing */
        .select, .select-rich, .app-select, .app-select-rich {
            position: relative;
        }
        [data-custom-popover] {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            z-index: 50;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.2s ease;
        }
        .dark [data-custom-popover] {
            background: #18181b; /* zinc-900 */
        }
        [data-custom-popover][aria-hidden="true"] {
            opacity: 0 !important;
            transform: translateY(-10px) !important;
            pointer-events: none !important;
            display: none !important;
        }
        [data-custom-popover][aria-hidden="false"] {
            opacity: 1 !important;
            transform: translateY(0) !important;
            pointer-events: auto !important;
            display: flex !important;
        }
        [data-custom-popover] header {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #f9fafb; /* gray-50 */
        }
        .dark [data-custom-popover] header {
            background-color: #27272a; /* zinc-800 */
        }
        [data-custom-popover] header input {
            background: transparent;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            flex: 1;
            min-width: 0;
            font-size: 0.875rem;
            height: 2rem;
            padding: 0;
        }
        [data-select-option]:hover {
            background-color: #f4f4f5; /* zinc-100 */
        }
        .dark [data-select-option]:hover {
            background-color: #27272a; /* zinc-800 */
        }
        [data-select-option].selected {
            background-color: #f4f4f5 !important; /* zinc-100 */
        }
        .dark [data-select-option].selected {
            background-color: #27272a !important; /* zinc-800 */
        }
        [data-select-option].selected .check-icon {
            opacity: 1 !important;
        }
        .select button, .select-rich button, .app-select button, .app-select-rich button {
            height: 2.5rem;
            min-height: 2.5rem;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        
        /* Toast Top Layer & Positioning Fix */
        #toaster[popover] {
            position: fixed !important;
            inset: auto 1rem 1rem auto !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: none !important;
            width: auto !important;
            height: auto !important;
            z-index: 2147483647 !important;
            display: none;
            flex-direction: column !important;
            align-items: flex-end !important;
            justify-content: flex-end !important;
            gap: 0.5rem !important;
            pointer-events: none !important;
            overflow: visible !important;
        }
        #toaster[popover]:popover-open {
            display: flex !important;
        }
        #toaster[popover] > * {
            pointer-events: auto !important;
        }

        /* Dark Mode Critical Overrides */
        html.dark body {
            background-color: #09090b !important;
            color: #f4f4f5 !important;
        }

        html.dark .bg-white {
            background-color: #18181b !important;
        }

        html.dark dialog,
        html.dark .dialog,
        html.dark .dialog-content {
            background-color: #18181b !important;
            color: #f4f4f5 !important;
            border-color: #27272a !important;
        }

        html.dark .btn {
            background-color: #f4f4f5 !important;
            color: #18181b !important;
            border-color: #f4f4f5 !important;
        }

        html.dark .btn-outline {
            background-color: transparent !important;
            color: #f4f4f5 !important;
            border-color: #27272a !important;
        }

        html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
        html.dark select,
        html.dark textarea {
            background-color: #09090b !important;
            color: #f4f4f5 !important;
            border-color: #27272a !important;
        }

        html.dark .text-zinc-900 {
            color: #f4f4f5 !important;
        }

        html.dark .text-zinc-500 {
            color: #a1a1aa !important;
        }

        /* Border Overrides for Dark Mode */
        html.dark .border-zinc-200,
        html.dark .border-zinc-100,
        html.dark .border-border,
        html.dark .border,
        html.dark .divide-zinc-200 > *,
        html.dark .divide-y > * {
            border-color: #27272a !important;
        }

        html.dark .bg-zinc-50 {
            background-color: #18181b !important;
        }

        html.dark .bg-background {
            background-color: #18181b !important;
        }

        html.dark .hover\:bg-zinc-50:hover,
        html.dark .hover\:bg-zinc-100:hover {
            background-color: #27272a !important;
        }

        /* Force Black Focus Color Ring Globally for Inputs, Selects and Textareas */
        input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):focus,
        select:focus,
        textarea:focus {
            border-color: #18181b !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(24, 24, 27, 0.15) !important;
        }
        
        /* Dark Mode support */
        html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):focus,
        html.dark select:focus,
        html.dark textarea:focus {
            border-color: #f4f4f5 !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(244, 244, 245, 0.15) !important;
        }
    </style>
    <title><?php echo isset($pageTitle) ? $pageTitle . " | Sözleşme Yönetimi" : "Sözleşme Yönetimi"; ?></title>
    
    <!-- Theme Init -->
    <script src="<?php echo routeUrl('assets/js/theme.js'); ?>"></script>

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo routeUrl('assets/css/app.css'); ?>" type="text/tailwindcss">
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <!-- Basecoat UI -->
    <link rel="stylesheet" href="https://unpkg.com/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <script src="https://unpkg.com/basecoat-css@0.3.11/dist/js/all.min.js" defer></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-dt/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="<?php echo routeUrl('assets/css/datatable.custom.css'); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net/1.13.7/jquery.dataTables.min.js"></script>
    <script src="<?php echo routeUrl('assets/js/datatable.init.js'); ?>"></script>
    <script src="<?php echo routeUrl('assets/js/utils.js'); ?>"></script>
    
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
    <link rel="stylesheet" href="<?php echo routeUrl('assets/css/flatpickr.custom.css'); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script src="<?php echo routeUrl('assets/js/flatpickr.init.js'); ?>"></script>
    
    <!-- Layout Fix -->
    <style>
        @media (min-width: 48rem) {
            :root {
                --sidebar-width: 230px;
            }
            body {
                overflow: hidden !important;
            }
            .app-shell {
                display: flex !important;
                height: 100vh !important;
                width: 100% !important;
                overflow: hidden !important;
            }
            .sidebar:not([aria-hidden="true"]) {
                width: var(--sidebar-width) !important;
                height: 100vh !important;
                flex-shrink: 0 !important;
                z-index: 40 !important;
                display: block !important;
            }
            .sidebar[aria-hidden="true"] {
                width: 0 !important;
                height: 100vh !important;
                flex-shrink: 0 !important;
                overflow: hidden !important;
                display: none !important;
            }

            .sidebar nav {
                position: relative !important;
                width: var(--sidebar-width) !important;
                height: 100% !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
            }
            .app-view {
                flex: 1 !important;
                height: 100vh !important;
                overflow: hidden !important;
                min-width: 0 !important;
                display: flex !important;
                flex-direction: column !important;
            }
            .app-main {
                flex: 1 !important;
                overflow-y: auto !important;
            }
            .app-topbar {
                height: 65px !important;
                min-height: 65px !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 40 !important;
                flex-shrink: 0 !important;
            }
            /* Sidebar header height synchronization */
            .sidebar > nav > div:first-child {
                height: 65px !important;
                min-height: 65px !important;
                display: flex !important;
                align-items: center !important;
            }
        }
    </style>
</head>
