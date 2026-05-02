<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Engagement score calculation.
 *
 * Formula (time-decayed weighted sum, scaled logarithmically to 0–40):
 *
 *   raw   = (views × 1 + reads × 5 + plays × 3 + completions × 8)
 *           ─────────────────────────────────────────────────────────
 *                     (hours_since_publish + 2) ^ 1.5
 *
 *   bonus = min(40, round(ln(raw + 1) × 10))
 *
 * Signal weights rationale:
 *   view      = 1  — passive impression, easy to accumulate
 *   read      = 5  — user consumed >60 % of content
 *   play      = 3  — active choice to start podcast
 *   complete  = 8  — highest engagement, listened >80 %
 *
 * The gravity (1.5) mirrors HackerNews / Medium decay so recent articles
 * can rank high even with fewer absolute signals.
 */
class TDET_Score {

    const MAX_BONUS  = 40;
    const W_VIEW     = 1;
    const W_READ     = 5;
    const W_PLAY     = 3;
    const W_COMPLETE = 8;
    const GRAVITY    = 1.5;

    /**
     * Recalculate and return the engagement bonus for a post (0–40).
     * Reads current counters from post meta.
     */
    public static function calculate_bonus( int $post_id ): int {
        $views       = (int) get_post_meta( $post_id, TDET_Tracker::META_VIEWS,       true );
        $reads       = (int) get_post_meta( $post_id, TDET_Tracker::META_READS,       true );
        $plays       = (int) get_post_meta( $post_id, TDET_Tracker::META_PLAYS,       true );
        $completions = (int) get_post_meta( $post_id, TDET_Tracker::META_COMPLETIONS, true );

        $published   = (int) get_post_timestamp( $post_id );
        $hours_since = $published > 0 ? max( 0, ( time() - $published ) / HOUR_IN_SECONDS ) : 0;
        $decay       = pow( $hours_since + 2, self::GRAVITY );

        $weighted = (
            $views       * self::W_VIEW     +
            $reads       * self::W_READ     +
            $plays       * self::W_PLAY     +
            $completions * self::W_COMPLETE
        );

        $raw   = $decay > 0 ? $weighted / $decay : 0;
        $bonus = (int) min( self::MAX_BONUS, round( log1p( $raw ) * 10 ) );

        /**
         * Filter the engagement bonus before it is stored or returned.
         *
         * @param int   $bonus    Calculated bonus 0–40.
         * @param int   $post_id  Post ID.
         * @param array $signals  Raw signal counts: views, reads, plays, completions.
         */
        return (int) apply_filters(
            'themisdb_engagement_bonus',
            $bonus,
            $post_id,
            compact( 'views', 'reads', 'plays', 'completions' )
        );
    }

    /**
     * Return the last stored bonus (fast path, no recalculation).
     */
    public static function get_bonus( int $post_id ): int {
        return (int) get_post_meta( $post_id, '_themisdb_engagement_score', true );
    }

    /**
     * Return all raw signal counters for a post.
     *
     * @return array{views:int, reads:int, plays:int, completions:int, bonus:int}
     */
    public static function get_signals( int $post_id ): array {
        return [
            'views'       => (int) get_post_meta( $post_id, TDET_Tracker::META_VIEWS,       true ),
            'reads'       => (int) get_post_meta( $post_id, TDET_Tracker::META_READS,       true ),
            'plays'       => (int) get_post_meta( $post_id, TDET_Tracker::META_PLAYS,       true ),
            'completions' => (int) get_post_meta( $post_id, TDET_Tracker::META_COMPLETIONS, true ),
            'bonus'       => self::get_bonus( $post_id ),
        ];
    }
}
