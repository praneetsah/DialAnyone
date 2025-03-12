<?php
/**
 * Coupon Model
 * 
 * Coupon data management
 */

/**
 * Create a new coupon
 * 
 * @param string $code Coupon code
 * @param string $discountType Discount type (percentage, fixed)
 * @param float $discountValue Discount value
 * @param float $minPurchase Minimum purchase amount
 * @param float $maxDiscount Maximum discount amount
 * @param string $expirationDate Expiration date (Y-m-d)
 * @param int $usageLimit Usage limit
 * @return int|false Coupon ID or false on failure
 */
if (!function_exists('createCoupon')) {
    function createCoupon($code, $discountType, $discountValue, $minPurchase = 0, $maxDiscount = null, $expirationDate = null, $usageLimit = null) {
        // Create coupon record
        $couponId = dbInsert('coupons', [
            'code' => strtoupper($code),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'min_purchase' => $minPurchase,
            'max_discount' => $maxDiscount,
            'expiration_date' => $expirationDate,
            'usage_limit' => $usageLimit,
            'is_active' => 1
        ]);
        
        if ($couponId) {
            logInfo("Coupon created: $code", 'coupon');
            return $couponId;
        }
        
        logError("Failed to create coupon: $code", 'coupon');
        return false;
    }
}

/**
 * Get coupon by ID
 * 
 * @param int $couponId Coupon ID
 * @return array|false Coupon data or false if not found
 */
if (!function_exists('getCouponById')) {
    function getCouponById($couponId) {
        $sql = "SELECT * FROM coupons WHERE id = ?";
        return dbFetchRow($sql, [$couponId]);
    }
}

/**
 * Get coupon by code
 * 
 * @param string $code Coupon code
 * @return array|false Coupon data or false if not found
 */
if (!function_exists('getCouponByCode')) {
    function getCouponByCode($code) {
        $sql = "SELECT * FROM coupons WHERE code = ?";
        return dbFetchRow($sql, [strtoupper($code)]);
    }
}

/**
 * Get all coupons
 * 
 * @param int $limit Result limit
 * @param int $offset Result offset
 * @return array Coupons data
 */
function getAllCoupons($limit = 100, $offset = 0) {
    $sql = "SELECT * FROM coupons ORDER BY created_at DESC LIMIT ? OFFSET ?";
    return dbFetchAll($sql, [$limit, $offset]);
}

/**
 * Get coupon count
 * 
 * @return int Number of coupons
 */
function getCouponCount() {
    $sql = "SELECT COUNT(*) as count FROM coupons";
    $result = dbFetchRow($sql);
    
    return $result ? $result['count'] : 0;
}

/**
 * Update coupon status
 * 
 * @param int $couponId Coupon ID
 * @param bool $active Is active
 * @return bool Success or failure
 */
function updateCouponStatus($couponId, $active) {
    $success = dbUpdate('coupons', [
        'is_active' => $active ? 1 : 0
    ], 'id = ?', [$couponId]);
    
    if ($success) {
        logInfo("Coupon status updated: $couponId - " . ($active ? 'active' : 'inactive'), 'coupon');
        return true;
    }
    
    logError("Failed to update coupon status: $couponId", 'coupon');
    return false;
}

/**
 * Delete coupon
 * 
 * @param int $couponId Coupon ID
 * @return bool Success or failure
 */
if (!function_exists('deleteCoupon')) {
    function deleteCoupon($couponId) {
        $success = dbDelete('coupons', 'id = ?', [$couponId]);
        
        if ($success) {
            logInfo("Coupon deleted: $couponId", 'coupon');
            return true;
        }
        
        logError("Failed to delete coupon: $couponId", 'coupon');
        return false;
    }
}

/**
 * Validate coupon
 * 
 * @param string $code Coupon code
 * @param float $amount Purchase amount
 * @param int $userId User ID
 * @return array|false Coupon data or false if invalid
 */
function validateCoupon($code, $amount, $userId = null) {
    // Get coupon
    $coupon = getCouponByCode($code);
    
    if (!$coupon) {
        return false;
    }
    
    // Check if coupon is active
    if ($coupon['is_active'] != 1) {
        return false;
    }
    
    // Check if coupon has expired
    if ($coupon['expiration_date'] && strtotime($coupon['expiration_date']) < time()) {
        return false;
    }
    
    // Check if coupon has reached usage limit
    if ($coupon['usage_limit'] && $coupon['usage_count'] >= $coupon['usage_limit']) {
        return false;
    }
    
    // Check if purchase amount meets minimum
    if ($coupon['min_purchase'] > 0 && $amount < $coupon['min_purchase']) {
        return false;
    }
    
    // Check if user has already used this coupon (if user ID is provided)
    if ($userId) {
        $sql = "SELECT id FROM coupon_usage WHERE coupon_id = ? AND user_id = ?";
        $usage = dbFetchRow($sql, [$coupon['id'], $userId]);
        
        if ($usage) {
            return false;
        }
    }
    
    return $coupon;
}

/**
 * Calculate discount amount
 * 
 * @param array $coupon Coupon data
 * @param float $amount Purchase amount
 * @return array Discount information
 */
function calculateDiscount($coupon, $amount) {
    $discount = 0;
    
    if ($coupon['discount_type'] == 'percentage') {
        $discount = $amount * ($coupon['discount_value'] / 100);
        
        // Check if discount exceeds maximum
        if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        $discount = $coupon['discount_value'];
        
        // Make sure discount doesn't exceed amount
        if ($discount > $amount) {
            $discount = $amount;
        }
    }
    
    $discountedAmount = $amount - $discount;
    
    return [
        'original_amount' => $amount,
        'discount_amount' => $discount,
        'final_amount' => $discountedAmount
    ];
}

/**
 * Get coupon usage for coupon
 * 
 * @param int $couponId Coupon ID
 * @param int $limit Result limit
 * @return array Usage data
 */
function getCouponUsage($couponId, $limit = 100) {
    $sql = "SELECT coupon_usage.*, users.name, users.email, payments.amount, payments.created_at 
            FROM coupon_usage 
            LEFT JOIN users ON coupon_usage.user_id = users.id 
            LEFT JOIN payments ON coupon_usage.payment_id = payments.id 
            WHERE coupon_usage.coupon_id = ? 
            ORDER BY coupon_usage.created_at DESC LIMIT ?";
    
    return dbFetchAll($sql, [$couponId, $limit]);
}

/**
 * Get all active coupons
 * 
 * @param int $limit Limit of records
 * @param int $offset Offset for pagination
 * @return array List of active coupons
 */
if (!function_exists('getActiveCoupons')) {
    function getActiveCoupons($limit = 100, $offset = 0) {
        // ... existing code ...
    }
}

/**
 * Check if coupon is valid
 * 
 * @param array $coupon Coupon data
 * @return bool Coupon is valid or not
 */
if (!function_exists('isCouponValid')) {
    function isCouponValid($coupon) {
        // ... existing code ...
    }
}

/**
 * Calculate discount amount for a coupon
 * 
 * @param array $coupon Coupon data
 * @param float $amount Original amount
 * @return float Discount amount
 */
if (!function_exists('calculateCouponDiscount')) {
    function calculateCouponDiscount($coupon, $amount) {
        // ... existing code ...
    }
}

/**
 * Apply coupon to amount
 * 
 * @param array $coupon Coupon data
 * @param float $amount Original amount
 * @return array Discounted amount and discount amount
 */
if (!function_exists('applyCoupon')) {
    function applyCoupon($coupon, $amount) {
        // ... existing code ...
    }
} 