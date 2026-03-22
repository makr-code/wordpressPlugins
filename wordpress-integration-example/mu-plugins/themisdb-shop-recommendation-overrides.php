<?php
/**
 * MU Plugin: ThemisDB Shop Recommendation Overrides
 *
 * Purpose:
 * - Customize edition recommendation order used by themisdb_shop links.
 * - Optionally enforce a specific edition for selected contexts.
 *
 * Installation:
 * 1) Copy this file to wp-content/mu-plugins/
 * 2) Ensure ThemisDB Order Request plugin is active.
 *
 * Notes:
 * - MU plugins load automatically (no activation in admin needed).
 * - Keep logic lightweight to avoid slowing down page rendering.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adjust preference list by context and detail.
 *
 * @param array  $preferences
 * @param string $context  default|module|training
 * @param string $detail   module category or training type
 * @return array
 */
add_filter('themisdb_shop_recommended_edition_preferences', function ($preferences, $context, $detail) {
    $context = sanitize_key((string) $context);
    $detail = sanitize_key((string) $detail);

    // Default global order if no specific rule applies.
    $base = array('enterprise', 'hyperscaler', 'reseller', 'community');

    if ($context === 'module') {
        // Scaling workloads are commonly mapped to hyperscaler first.
        if (in_array($detail, array('scaling', 'cluster', 'high-availability'), true)) {
            return array('hyperscaler', 'enterprise', 'reseller', 'community');
        }

        // Compliance/security heavy modules prefer enterprise.
        if (in_array($detail, array('security', 'compliance', 'storage'), true)) {
            return array('enterprise', 'hyperscaler', 'reseller', 'community');
        }

        return $base;
    }

    if ($context === 'training') {
        // Onsite and workshops are typically enterprise-led engagements.
        if (in_array($detail, array('onsite', 'consulting', 'workshop'), true)) {
            return array('enterprise', 'hyperscaler', 'community', 'reseller');
        }

        // Online training can still favor community onboarding first.
        return array('community', 'enterprise', 'hyperscaler', 'reseller');
    }

    return $base;
}, 10, 3);

/**
 * Optional final override for specific contexts.
 *
 * Return an empty string to keep default resolution.
 *
 * @param string $edition
 * @param string $context
 * @param string $detail
 * @return string
 */
add_filter('themisdb_shop_recommended_edition', function ($edition, $context, $detail) {
    $context = sanitize_key((string) $context);
    $detail = sanitize_key((string) $detail);

    // Example: force enterprise for all training-related deep links.
    if ($context === 'training') {
        return 'enterprise';
    }

    // Example: for AI/ML modules, keep enterprise regardless of preference list.
    if ($context === 'module' && $detail === 'ai-ml') {
        return 'enterprise';
    }

    return sanitize_key((string) $edition);
}, 10, 3);
