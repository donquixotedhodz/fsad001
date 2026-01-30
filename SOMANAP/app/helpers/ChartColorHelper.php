<?php

/**
 * ChartColorHelper - Provides chart colors based on the current color theme
 * 
 * This helper generates chart colors that match the application's color theme settings
 * stored in localStorage and synchronized with master.php CSS variables.
 */

class ChartColorHelper {
    
    /**
     * Color palettes for different themes
     */
    private static $themePalettes = [
        'autumn' => [
            'colors' => ['#ea580c', '#f59e0b', '#dc2626', '#991b1b', '#b91c1c', '#7f1d1d', '#c2410c', '#d97706', '#059669', '#10b981'],
            'rgba' => [
                'rgba(234, 88, 12, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(220, 38, 38, 0.8)',
                'rgba(153, 27, 27, 0.8)',
                'rgba(185, 28, 28, 0.8)',
                'rgba(127, 29, 29, 0.8)',
                'rgba(194, 65, 12, 0.8)',
                'rgba(217, 119, 6, 0.8)',
                'rgba(5, 150, 105, 0.8)',
                'rgba(16, 185, 129, 0.8)'
            ]
        ],
        'winter' => [
            'colors' => ['#3b82f6', '#06b6d4', '#4f46e5', '#0ea5e9', '#1e40af', '#0c4a6e', '#1d4ed8', '#164e63', '#0369a1', '#075985'],
            'rgba' => [
                'rgba(59, 130, 246, 0.8)',
                'rgba(6, 182, 212, 0.8)',
                'rgba(79, 70, 229, 0.8)',
                'rgba(14, 165, 233, 0.8)',
                'rgba(30, 64, 175, 0.8)',
                'rgba(12, 74, 110, 0.8)',
                'rgba(29, 78, 216, 0.8)',
                'rgba(22, 78, 99, 0.8)',
                'rgba(3, 105, 161, 0.8)',
                'rgba(7, 89, 133, 0.8)'
            ]
        ],
        'spring' => [
            'colors' => ['#10b981', '#ec4899', '#059669', '#a855f7', '#06b6d4', '#14b8a6', '#d946ef', '#7c3aed', '#0891b2', '#047857'],
            'rgba' => [
                'rgba(16, 185, 129, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(5, 150, 105, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(6, 182, 212, 0.8)',
                'rgba(20, 184, 166, 0.8)',
                'rgba(217, 70, 239, 0.8)',
                'rgba(124, 58, 237, 0.8)',
                'rgba(8, 145, 178, 0.8)',
                'rgba(4, 120, 87, 0.8)'
            ]
        ],
        'summer' => [
            'colors' => ['#eab308', '#f43f5e', '#ca8a04', '#facc15', '#fbbf24', '#fcd34d', '#fed7aa', '#fecaca', '#fca5a5', '#fb7185'],
            'rgba' => [
                'rgba(234, 179, 8, 0.8)',
                'rgba(244, 63, 94, 0.8)',
                'rgba(202, 138, 4, 0.8)',
                'rgba(250, 204, 21, 0.8)',
                'rgba(251, 191, 36, 0.8)',
                'rgba(252, 211, 77, 0.8)',
                'rgba(254, 215, 170, 0.8)',
                'rgba(252, 165, 165, 0.8)',
                'rgba(252, 165, 165, 0.8)',
                'rgba(251, 113, 133, 0.8)'
            ]
        ],
        'monochrome' => [
            'colors' => ['#4b5563', '#6b7280', '#1f2937', '#9ca3af', '#d1d5db', '#e5e7eb', '#f3f4f6', '#374151', '#2d3748', '#1a202c'],
            'rgba' => [
                'rgba(75, 85, 99, 0.8)',
                'rgba(107, 114, 128, 0.8)',
                'rgba(31, 41, 55, 0.8)',
                'rgba(156, 163, 175, 0.8)',
                'rgba(209, 213, 219, 0.8)',
                'rgba(229, 231, 235, 0.8)',
                'rgba(243, 244, 246, 0.8)',
                'rgba(55, 65, 81, 0.8)',
                'rgba(45, 55, 72, 0.8)',
                'rgba(26, 32, 44, 0.8)'
            ]
        ],
        'gradient' => [
            'colors' => ['#3b82f6', '#06b6d4', '#eab308', '#dc2626', '#10b981', '#8b5cf6', '#ec4899', '#f59e0b', '#14b8a6', '#6366f1'],
            'rgba' => [
                'rgba(59, 130, 246, 0.8)',
                'rgba(6, 182, 212, 0.8)',
                'rgba(234, 179, 8, 0.8)',
                'rgba(220, 38, 38, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(20, 184, 166, 0.8)',
                'rgba(99, 102, 241, 0.8)'
            ]
        ]
    ];

    /**
     * Get chart colors array for a specific theme
     * 
     * @param string $theme The theme name (autumn, winter, spring, summer, monochrome, gradient)
     * @param bool $useRgba Whether to return RGBA strings (true) or hex colors (false)
     * @return array Array of colors
     */
    public static function getChartColors($theme = 'autumn', $useRgba = true) {
        $theme = strtolower($theme);
        
        // Default to autumn if theme not found
        if (!isset(self::$themePalettes[$theme])) {
            $theme = 'autumn';
        }
        
        $palette = self::$themePalettes[$theme];
        return $useRgba ? $palette['rgba'] : $palette['colors'];
    }

    /**
     * Get a specific number of colors from a theme
     * 
     * @param int $count Number of colors needed
     * @param string $theme The theme name
     * @param bool $useRgba Whether to return RGBA strings
     * @return array Array of colors, cycles through palette if needed
     */
    public static function getChartColorsByCount($count, $theme = 'autumn', $useRgba = true) {
        $colors = self::getChartColors($theme, $useRgba);
        $result = [];
        
        for ($i = 0; $i < $count; $i++) {
            $result[] = $colors[$i % count($colors)];
        }
        
        return $result;
    }

    /**
     * Get line chart specific colors (primary colors)
     * 
     * @param string $theme The theme name
     * @return array Array with main colors suitable for line charts
     */
    public static function getLineChartColors($theme = 'autumn') {
        $palette = self::$themePalettes[strtolower($theme)] ?? self::$themePalettes['autumn'];
        
        // Return first 2-3 colors suitable for lines
        return [
            $palette['rgba'][0],  // Primary color
            $palette['rgba'][1],  // Secondary color
            $palette['rgba'][2]   // Tertiary color
        ];
    }

    /**
     * Get hex colors for border/stroke
     * 
     * @param string $theme The theme name
     * @param int $count Number of colors needed
     * @return array Array of hex colors with full opacity
     */
    public static function getBorderColors($theme = 'autumn', $count = 10) {
        $colors = self::getChartColors($theme, false);
        $result = [];
        
        for ($i = 0; $i < $count; $i++) {
            $result[] = $colors[$i % count($colors)];
        }
        
        return $result;
    }

    /**
     * Get a gradient color mapping for specific data types
     * 
     * @param string $type Type of data (debit, credit, balance, etc.)
     * @param string $theme The theme name
     * @return string RGBA color string
     */
    public static function getDataTypeColor($type, $theme = 'autumn') {
        $type = strtolower($type);
        $colors = self::getChartColors($theme, true);
        
        switch ($type) {
            case 'debit':
            case 'expense':
                return $colors[2]; // Red-ish
            case 'credit':
            case 'income':
                return $colors[1]; // Orange/Amber-ish
            case 'balance':
                return $colors[0]; // Primary color
            default:
                return $colors[0];
        }
    }

    /**
     * Convert RGBA string to hex color
     * 
     * @param string $rgba RGBA string like "rgba(234, 88, 12, 0.8)"
     * @return string Hex color like "#ea580c"
     */
    public static function rgbaToHex($rgba) {
        if (preg_match('/rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $rgba, $matches)) {
            $r = intval($matches[1]);
            $g = intval($matches[2]);
            $b = intval($matches[3]);
            return sprintf("#%02x%02x%02x", $r, $g, $b);
        }
        return '#000000';
    }

    /**
     * Get ApexCharts compatible color configuration
     * 
     * @param string $theme The theme name
     * @return array Configuration array for ApexCharts
     */
    public static function getApexChartsConfig($theme = 'autumn') {
        $colors = self::getChartColors($theme, false);
        
        return [
            'colors' => $colors,
            'chart' => [
                'toolbar' => [
                    'show' => false
                ]
            ]
        ];
    }
}
