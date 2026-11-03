<?php
// The shared mobile navigation toggle still uses this local SVG helper.
return static function (string $name): string {
    $paths = [
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
    ];
    return '<svg class="recycler-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
};
