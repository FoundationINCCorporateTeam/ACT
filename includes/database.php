<?php
/**
 * ACT AI Tutor - JSON Database Utilities
 * 
 * Provides utilities for reading and writing JSON data files
 * with atomic writes and file locking to prevent corruption.
 */

/**
 * Read JSON data from a file
 * 
 * @param string $filename The name of the JSON file (without path)
 * @return array The decoded JSON data or empty array if file doesn't exist
 */
function db_read($filename) {
    $filepath = DATA_PATH . '/' . $filename;
    
    if (!file_exists($filepath)) {
        return [];
    }
    
    $handle = fopen($filepath, 'r');
    if (!$handle) {
        error_log("Failed to open file for reading: $filepath");
        return [];
    }
    
    // Acquire shared lock for reading
    if (flock($handle, LOCK_SH)) {
        $content = file_get_contents($filepath);
        flock($handle, LOCK_UN);
        fclose($handle);
        
        if ($content === false || $content === '') {
            return [];
        }
        
        $data = json_decode($content, true);
        return $data ?: [];
    }
    
    fclose($handle);
    error_log("Failed to acquire lock for reading: $filepath");
    return [];
}

/**
 * Write JSON data to a file atomically
 * 
 * @param string $filename The name of the JSON file (without path)
 * @param array $data The data to write
 * @return bool True on success, false on failure
 */
function db_write($filename, $data) {
    $filepath = DATA_PATH . '/' . $filename;
    $tempfile = DATA_PATH . '/.' . $filename . '.tmp';
    
    // Ensure data directory exists
    if (!is_dir(DATA_PATH)) {
        mkdir(DATA_PATH, 0755, true);
    }
    
    // Write to temp file first
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("Failed to encode JSON data for: $filepath");
        return false;
    }
    
    $handle = fopen($tempfile, 'w');
    if (!$handle) {
        error_log("Failed to open temp file for writing: $tempfile");
        return false;
    }
    
    // Acquire exclusive lock for writing
    if (flock($handle, LOCK_EX)) {
        $result = fwrite($handle, $json);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        
        if ($result === false) {
            unlink($tempfile);
            error_log("Failed to write to temp file: $tempfile");
            return false;
        }
        
        // Atomic rename
        if (!rename($tempfile, $filepath)) {
            unlink($tempfile);
            error_log("Failed to rename temp file to: $filepath");
            return false;
        }
        
        return true;
    }
    
    fclose($handle);
    unlink($tempfile);
    error_log("Failed to acquire lock for writing: $tempfile");
    return false;
}

/**
 * Get user-specific data file path
 * 
 * @param int $userId The user ID
 * @param string $type The type of data (lessons, quizzes, etc.)
 * @return string The filename for the user's data
 */
function get_user_data_file($userId, $type) {
    return "user_{$userId}_{$type}.json";
}

/**
 * Read user-specific data
 * 
 * @param int $userId The user ID
 * @param string $type The type of data
 * @return array The user's data
 */
function db_read_user($userId, $type) {
    return db_read(get_user_data_file($userId, $type));
}

/**
 * Write user-specific data
 * 
 * @param int $userId The user ID
 * @param string $type The type of data
 * @param array $data The data to write
 * @return bool True on success
 */
function db_write_user($userId, $type, $data) {
    return db_write(get_user_data_file($userId, $type), $data);
}

/**
 * Append item to a user's data array
 * 
 * @param int $userId The user ID
 * @param string $type The type of data
 * @param array $item The item to append
 * @return int|false The new item's ID or false on failure
 */
function db_append_user($userId, $type, $item) {
    $data = db_read_user($userId, $type);
    
    // Generate new ID
    $maxId = 0;
    foreach ($data as $existing) {
        if (isset($existing['id']) && $existing['id'] > $maxId) {
            $maxId = $existing['id'];
        }
    }
    
    $item['id'] = $maxId + 1;
    $item['created_at'] = date('Y-m-d H:i:s');
    $item['updated_at'] = date('Y-m-d H:i:s');
    
    $data[] = $item;
    
    if (db_write_user($userId, $type, $data)) {
        return $item['id'];
    }
    
    return false;
}

/**
 * Update an item in a user's data array
 * 
 * @param int $userId The user ID
 * @param string $type The type of data
 * @param int $itemId The ID of the item to update
 * @param array $updates The fields to update
 * @return bool True on success
 */
function db_update_user($userId, $type, $itemId, $updates) {
    $data = db_read_user($userId, $type);
    
    foreach ($data as &$item) {
        if (isset($item['id']) && $item['id'] == $itemId) {
            $item = array_merge($item, $updates);
            $item['updated_at'] = date('Y-m-d H:i:s');
            return db_write_user($userId, $type, $data);
        }
    }
    
    return false;
}

/**
 * Delete an item from a user's data array
 * 
 * @param int $userId The user ID
 * @param string $type The type of data
 * @param int $itemId The ID of the item to delete
 * @return bool True on success
 */
function db_delete_user($userId, $type, $itemId) {
    $data = db_read_user($userId, $type);
    
    $data = array_filter($data, function($item) use ($itemId) {
        return !isset($item['id']) || $item['id'] != $itemId;
    });
    
    return db_write_user($userId, $type, array_values($data));
}

/**
 * Get a single item from a user's data by ID
 * 
 * @param int $userId The user ID
 * @param string $type The type of data
 * @param int $itemId The ID of the item
 * @return array|null The item or null if not found
 */
function db_get_user_item($userId, $type, $itemId) {
    $data = db_read_user($userId, $type);
    
    foreach ($data as $item) {
        if (isset($item['id']) && $item['id'] == $itemId) {
            return $item;
        }
    }
    
    return null;
}

/**
 * Initialize data directory and required files
 */
function db_init() {
    if (!is_dir(DATA_PATH)) {
        mkdir(DATA_PATH, 0755, true);
    }
    
    // Initialize users file if it doesn't exist
    $usersFile = DATA_PATH . '/users.json';
    if (!file_exists($usersFile)) {
        db_write('users.json', []);
    }
    
    // Create .htaccess to protect data directory
    $htaccess = DATA_PATH . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all\n");
    }
}

// Initialize on include
db_init();

/**
 * Save/update a user's profile data
 * 
 * @param int $userId The user ID
 * @param array $userData The user data to save
 * @return bool True on success
 */
function db_save_user($userId, $userData) {
    $users = db_read('users.json');
    
    foreach ($users as &$user) {
        if ($user['id'] == $userId) {
            $user = array_merge($user, $userData);
            $user['updated_at'] = date('Y-m-d H:i:s');
            return db_write('users.json', $users);
        }
    }
    
    return false;
}

/**
 * Get the data directory path with optional subdirectory
 * 
 * @param string $subdir Optional subdirectory
 * @return string The full path
 */
function get_data_path($subdir = '') {
    $path = DATA_PATH;
    if ($subdir) {
        $path .= '/' . $subdir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    return $path;
}
