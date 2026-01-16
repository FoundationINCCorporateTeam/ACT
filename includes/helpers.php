<?php
/**
 * ACT AI Tutor - Helper Functions
 * 
 * Common utility functions used throughout the application.
 */

/**
 * Sanitize output for HTML display
 * 
 * @param string $str The string to sanitize
 * @return string The sanitized string
 */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a unique ID
 * 
 * @return string A unique identifier
 */
function generate_id() {
    return bin2hex(random_bytes(16));
}

/**
 * Format a date for display
 * 
 * @param string $date The date string
 * @param string $format The format string
 * @return string The formatted date
 */
function format_date($date, $format = 'M j, Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

/**
 * Format a date as relative time
 * 
 * @param string $date The date string
 * @return string The relative time (e.g., "2 hours ago")
 */
function time_ago($date) {
    if (!$date) return '';
    
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return format_date($date);
    }
}

/**
 * Format seconds as time duration
 * 
 * @param int $seconds The number of seconds
 * @return string The formatted duration
 */
function format_duration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        $mins = floor($seconds / 60);
        $secs = $seconds % 60;
        return $mins . 'm ' . $secs . 's';
    } else {
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $mins . 'm';
    }
}

/**
 * Calculate letter grade from percentage
 * 
 * @param float $percentage The percentage score
 * @return string The letter grade
 */
function get_letter_grade($percentage) {
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}

/**
 * Calculate ACT composite score from section scores
 * 
 * @param array $sectionScores Array of section scores
 * @return int The composite score (1-36)
 */
function calculate_composite_score($sectionScores) {
    if (empty($sectionScores)) return 0;
    
    $total = array_sum($sectionScores);
    $count = count($sectionScores);
    
    return round($total / $count);
}

/**
 * Convert raw score to scaled ACT score (1-36)
 * 
 * @param int $correct Number of correct answers
 * @param int $total Total number of questions
 * @return int The scaled score
 */
function raw_to_scaled_score($correct, $total) {
    $percentage = ($correct / $total) * 100;
    
    // Approximate conversion (actual ACT uses curved scaling)
    if ($percentage >= 97) return 36;
    if ($percentage >= 93) return 35;
    if ($percentage >= 89) return 34;
    if ($percentage >= 85) return 33;
    if ($percentage >= 81) return 32;
    if ($percentage >= 78) return 31;
    if ($percentage >= 75) return 30;
    if ($percentage >= 72) return 29;
    if ($percentage >= 69) return 28;
    if ($percentage >= 66) return 27;
    if ($percentage >= 63) return 26;
    if ($percentage >= 60) return 25;
    if ($percentage >= 57) return 24;
    if ($percentage >= 54) return 23;
    if ($percentage >= 51) return 22;
    if ($percentage >= 48) return 21;
    if ($percentage >= 45) return 20;
    if ($percentage >= 42) return 19;
    if ($percentage >= 39) return 18;
    if ($percentage >= 36) return 17;
    if ($percentage >= 33) return 16;
    if ($percentage >= 30) return 15;
    if ($percentage >= 27) return 14;
    if ($percentage >= 24) return 13;
    if ($percentage >= 21) return 12;
    if ($percentage >= 18) return 11;
    if ($percentage >= 15) return 10;
    if ($percentage >= 12) return 9;
    if ($percentage >= 9) return 8;
    if ($percentage >= 6) return 7;
    if ($percentage >= 3) return 6;
    return 1;
}

/**
 * Get percentile for an ACT score
 * 
 * @param int $score The ACT score (1-36)
 * @return int The percentile
 */
function get_percentile($score) {
    $percentiles = [
        36 => 99, 35 => 99, 34 => 99, 33 => 98, 32 => 97,
        31 => 95, 30 => 93, 29 => 90, 28 => 87, 27 => 83,
        26 => 79, 25 => 74, 24 => 68, 23 => 62, 22 => 55,
        21 => 48, 20 => 41, 19 => 34, 18 => 28, 17 => 22,
        16 => 16, 15 => 12, 14 => 8, 13 => 5, 12 => 3,
        11 => 1, 10 => 1, 9 => 1, 8 => 1, 7 => 1,
        6 => 1, 5 => 1, 4 => 1, 3 => 1, 2 => 1, 1 => 1
    ];
    
    return $percentiles[$score] ?? 1;
}

/**
 * Get user's level based on XP
 * 
 * @param int $xp The user's XP
 * @return int The level
 */
function get_level($xp) {
    $level = 1;
    foreach (LEVEL_THRESHOLDS as $lvl => $threshold) {
        if ($xp >= $threshold) {
            $level = $lvl;
        }
    }
    return $level;
}

/**
 * Get XP needed for next level
 * 
 * @param int $currentXp Current XP
 * @return array Info about next level
 */
function get_next_level_info($currentXp) {
    $currentLevel = get_level($currentXp);
    $nextLevel = $currentLevel + 1;
    
    if (!isset(LEVEL_THRESHOLDS[$nextLevel])) {
        return [
            'next_level' => null,
            'xp_needed' => 0,
            'progress' => 100
        ];
    }
    
    $currentThreshold = LEVEL_THRESHOLDS[$currentLevel];
    $nextThreshold = LEVEL_THRESHOLDS[$nextLevel];
    $xpInLevel = $currentXp - $currentThreshold;
    $xpForLevel = $nextThreshold - $currentThreshold;
    $progress = ($xpInLevel / $xpForLevel) * 100;
    
    return [
        'next_level' => $nextLevel,
        'xp_needed' => $nextThreshold - $currentXp,
        'progress' => $progress
    ];
}

/**
 * Generate a random color for charts
 * 
 * @param int $index The index for consistent colors
 * @return string The hex color
 */
function get_chart_color($index) {
    $colors = [
        '#3b82f6', // blue
        '#8b5cf6', // purple
        '#ec4899', // pink
        '#ef4444', // red
        '#f97316', // orange
        '#eab308', // yellow
        '#22c55e', // green
        '#14b8a6', // teal
        '#06b6d4', // cyan
        '#6366f1'  // indigo
    ];
    
    return $colors[$index % count($colors)];
}

/**
 * Truncate text with ellipsis
 * 
 * @param string $text The text to truncate
 * @param int $length Maximum length
 * @return string The truncated text
 */
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length - 3) . '...';
}

/**
 * Send JSON response and exit
 * 
 * @param bool $success Whether the operation was successful
 * @param mixed $data The data to send
 * @param string $message Optional message
 */
function json_response($success, $data = null, $message = '') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

/**
 * Validate and sanitize input
 * 
 * @param string $key The input key
 * @param string $type The expected type (string, int, email, etc.)
 * @param mixed $default Default value if not set
 * @return mixed The sanitized value
 */
function input($key, $type = 'string', $default = null) {
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    
    if ($value === null) {
        return $default;
    }
    
    switch ($type) {
        case 'int':
            return (int)$value;
        case 'float':
            return (float)$value;
        case 'bool':
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        case 'email':
            return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
        case 'array':
            return is_array($value) ? $value : [$value];
        default:
            return trim($value);
    }
}

/**
 * Get all subjects with their topics flattened
 * 
 * @return array Array of subjects with topics
 */
function get_all_topics() {
    $result = [];
    
    foreach (ACT_TOPICS as $subject => $topics) {
        $result[$subject] = [];
        foreach ($topics as $topic => $description) {
            $result[$subject][] = [
                'name' => $topic,
                'description' => $description
            ];
        }
    }
    
    return $result;
}

/**
 * Check if request is AJAX
 * 
 * @return bool True if AJAX request
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Redirect to a URL
 * 
 * @param string $url The URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Set a flash message for the next request
 * 
 * @param string $type The message type (success, error, info, warning)
 * @param string $message The message text
 */
function flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null The flash message or null
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Paginate an array
 * 
 * @param array $items The items to paginate
 * @param int $page Current page
 * @param int $perPage Items per page
 * @return array Paginated data with metadata
 */
function paginate($items, $page = 1, $perPage = 20) {
    $total = count($items);
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'items' => array_slice($items, $offset, $perPage),
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * Search items by keyword
 * 
 * @param array $items Items to search
 * @param string $keyword Search keyword
 * @param array $fields Fields to search in
 * @return array Matching items
 */
function search_items($items, $keyword, $fields) {
    if (empty($keyword)) {
        return $items;
    }
    
    $keyword = strtolower($keyword);
    
    return array_filter($items, function($item) use ($keyword, $fields) {
        foreach ($fields as $field) {
            if (isset($item[$field]) && stripos($item[$field], $keyword) !== false) {
                return true;
            }
        }
        return false;
    });
}

/**
 * Sort items by field
 * 
 * @param array $items Items to sort
 * @param string $field Field to sort by
 * @param string $direction Sort direction (asc, desc)
 * @return array Sorted items
 */
function sort_items($items, $field, $direction = 'desc') {
    usort($items, function($a, $b) use ($field, $direction) {
        $aVal = $a[$field] ?? '';
        $bVal = $b[$field] ?? '';
        
        if ($direction === 'asc') {
            return $aVal <=> $bVal;
        }
        return $bVal <=> $aVal;
    });
    
    return $items;
}

/**
 * Get the current URL
 * 
 * @return string The current URL
 */
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if current page matches a pattern
 * 
 * @param string $pattern The URL pattern to match
 * @return bool True if matches
 */
function is_active($pattern) {
    $current = basename($_SERVER['PHP_SELF'], '.php');
    return strpos($current, $pattern) === 0;
}
