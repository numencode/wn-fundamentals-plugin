<?php

/*
 * NumenCode Fundamentals configuration.
 *
 * To change these values in a project, create config/numencode/fundamentals/config.php
 * in the project root and return only the keys you want to override.
 */

return [

    /*
     * Changes the plugin makes to the whole backend. Each one can be switched off.
     */
    'backend' => [

        // Show "switch" list columns as a green check / red cross with the (translated) Yes/No label
        'switch_column_icons' => true,

        // Winter.Pages: show the page settings (viewBag fields) in the primary tabs instead of the sidebar
        'pages_viewbag_primary_tabs' => true,

        // Load the plugin's backend JS/CSS: dynamic section titles (data-set-from) and the Translate language selector
        'assets' => true,

    ],

    /*
     * Winter.Translate integration (only active when Winter.Translate is installed).
     */
    'translate' => [

        // Winter.Pages caches its routes, menus and snippet maps per locale, but saving in the backend only clears
        // the caches of the backend's locale. Clear them for every enabled locale (needs Winter.Pages).
        'pages_cache_all_locales' => true,

    ],

];
