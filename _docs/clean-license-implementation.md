# WPRTSP License Management - Clean Implementation

## Overview

This is a clean, trait-based implementation of license management for WP Real-Time Social Proof. It consists of:

1. **`WPRTSP_License_Handler_Trait`** - Contains all license handling functionality
2. **`WPRTSP_License_Management`** - Singleton class that uses the trait

## Usage

### Basic Usage

```php
// Get the singleton instance
$license_manager = WPRTSP_License_Management::get_instance();

// Check if pro license is valid
if ( $license_manager->is_valid_pro() ) {
    // Pro features are available
    echo 'Pro license is active!';
} else {
    // Pro features are not available
    echo 'Pro license is not active';
}
```

### License Activation

```php
$license_manager = WPRTSP_License_Management::get_instance();

$license_key = 'your-license-key-here';

if ( $license_manager->activate_license( $license_key ) ) {
    echo 'License activated: ' . $license_manager->get_last_error();
} else {
    echo 'Activation failed: ' . $license_manager->get_last_error();
}
```

### License Deactivation

```php
$license_manager = WPRTSP_License_Management::get_instance();

if ( $license_manager->deactivate_license() ) {
    echo 'License deactivated: ' . $license_manager->get_last_error();
} else {
    echo 'Deactivation failed: ' . $license_manager->get_last_error();
}
```

### Force Fresh License Check

```php
$license_manager = WPRTSP_License_Management::get_instance();

// Force bypass cache for real-time check
if ( $license_manager->is_valid_pro( true ) ) {
    echo 'License is currently active (fresh check)';
}
```

### Get Detailed License Status

```php
$license_manager = WPRTSP_License_Management::get_instance();

$status = $license_manager->get_license_status();

switch ( $status ) {
    case WPRTSP_License_Management::STATUS_VALID:
        echo 'License is valid and active';
        break;
    case WPRTSP_License_Management::STATUS_EXPIRED:
        echo 'License has expired';
        break;
    case WPRTSP_License_Management::STATUS_INVALID:
        echo 'License is invalid';
        break;
    case null:
        echo 'No license found';
        break;
    default:
        echo 'License status: ' . $status;
}
```

### Clear License Data

```php
$license_manager = WPRTSP_License_Management::get_instance();

// Clear all license data and cache
$license_manager->clear_license_data();

// Or just clear the cache
$license_manager->clear_cache();
```

## Available Constants

```php
WPRTSP_License_Management::STATUS_VALID                    // 'valid'
WPRTSP_License_Management::STATUS_INVALID                  // 'invalid'
WPRTSP_License_Management::STATUS_EXPIRED                  // 'expired'
WPRTSP_License_Management::STATUS_REVOKED                  // 'revoked'
WPRTSP_License_Management::STATUS_MISSING                  // 'missing'
WPRTSP_License_Management::STATUS_SITE_INACTIVE            // 'site_inactive'
WPRTSP_License_Management::STATUS_ITEM_NAME_MISMATCH       // 'item_name_mismatch'
WPRTSP_License_Management::STATUS_NO_ACTIVATIONS_LEFT      // 'no_activations_left'

WPRTSP_License_Management::CACHE_KEY                       // 'wprtsp_license_status_v2'
WPRTSP_License_Management::OPTION_KEY                      // 'wprtsp_license_v2'
WPRTSP_License_Management::ITEM_ID                         // 262
WPRTSP_License_Management::CACHE_DURATION                  // 86400 (24 hours)
```

## Integration with Existing WPRTSP Class

Replace the old `is_valid_pro()` method in the main WPRTSP class:

```php
class WPRTSP {
    
    // ... existing code ...
    
    /**
     * Check if this is a valid pro installation
     * Updated to use the new license management system
     */
    function is_valid_pro() {
        $license_manager = WPRTSP_License_Management::get_instance();
        return $license_manager->is_valid_pro();
    }
    
    /**
     * Get pro status - updated for backwards compatibility
     */
    function get_pro_status( $cached = true ) {
        $license_manager = WPRTSP_License_Management::get_instance();
        return $license_manager->is_valid_pro( !$cached ); // Invert $cached for force_check parameter
    }
    
    // ... rest of existing code ...
}
```

## Using the Trait in Custom Classes

You can also use the trait directly in your own classes:

```php
class My_Custom_License_Handler {
    
    use WPRTSP_License_Handler_Trait;
    
    public function __construct() {
        $this->init_license_handler();
    }
    
    // Add your custom methods here
    public function get_license_info() {
        return array(
            'is_valid' => $this->is_valid_pro(),
            'status' => $this->get_license_status(),
            'last_error' => $this->get_last_error(),
        );
    }
}
```

## Benefits

- **Clean Architecture**: No external functions, everything contained in classes/traits
- **Singleton Pattern**: Ensures single instance across the application
- **Trait-based**: Allows for flexible reuse in other classes
- **Secure**: All methods include proper security checks
- **Cached**: 24-hour intelligent caching to reduce API calls
- **Reliable**: Comprehensive error handling and validation
- **WordPress Standards**: Follows WordPress coding standards

## Security Features

- User capability checks (`current_user_can('manage_options')`)
- Input sanitization for all license keys
- Protected singleton methods to prevent cloning/unserialization
- Comprehensive API response validation

This implementation provides a robust, secure, and maintainable license management system for WP Real-Time Social Proof.