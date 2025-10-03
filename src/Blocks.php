<?php
/**
 * Blocks Utility Class
 *
 * Essential utilities for working with WordPress blocks.
 *
 * @package ArrayPress\BlockUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\BlockUtils;

use WP_Block_Type_Registry;
use WP_Post;

/**
 * Blocks Class
 *
 * Core operations for WordPress blocks.
 */
class Blocks {

	// ========================================
	// Parsing & Rendering
	// ========================================

	/**
	 * Parse blocks from content.
	 *
	 * @param string $content The content to parse.
	 *
	 * @return array Array of parsed blocks.
	 */
	public static function parse( string $content ): array {
		return parse_blocks( $content );
	}

	/**
	 * Get blocks from post.
	 *
	 * @param int|WP_Post $post The post ID or post object.
	 *
	 * @return array Array of blocks.
	 */
	public static function get_from_post( $post ): array {
		$post = get_post( $post );

		return $post ? self::parse( $post->post_content ) : [];
	}

	/**
	 * Render blocks to HTML.
	 *
	 * @param array $blocks Array of blocks.
	 *
	 * @return string The rendered HTML.
	 */
	public static function render( array $blocks ): string {
		$html = '';
		foreach ( $blocks as $block ) {
			$html .= render_block( $block );
		}

		return $html;
	}

	// ========================================
	// Search & Find
	// ========================================

	/**
	 * Find all blocks of a specific type (searches recursively).
	 *
	 * @param array  $blocks     Array of blocks to search.
	 * @param string $block_name Block name (use * for wildcard, e.g., 'core/gallery' or 'woocommerce/*').
	 *
	 * @return array Array of matching blocks.
	 */
	public static function find_by_type( array $blocks, string $block_name ): array {
		$found = [];

		foreach ( $blocks as $block ) {
			if ( self::matches_type( $block, $block_name ) ) {
				$found[] = $block;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = array_merge( $found, self::find_by_type( $block['innerBlocks'], $block_name ) );
			}
		}

		return $found;
	}

	/**
	 * Find blocks with specific CSS class (searches recursively).
	 *
	 * @param array  $blocks    Array of blocks to search.
	 * @param string $classname CSS class name.
	 *
	 * @return array Array of matching blocks.
	 */
	public static function find_by_class( array $blocks, string $classname ): array {
		$found = [];

		foreach ( $blocks as $block ) {
			if ( self::has_class( $block, $classname ) ) {
				$found[] = $block;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = array_merge( $found, self::find_by_class( $block['innerBlocks'], $classname ) );
			}
		}

		return $found;
	}

	/**
	 * Check if content has specific block type.
	 *
	 * @param string      $block_name Block name or pattern.
	 * @param string|null $content    Content to check. Null for current post.
	 *
	 * @return bool True if content uses the block type.
	 */
	public static function has_block_type( string $block_name, ?string $content = null ): bool {
		$content = $content ?? get_the_content();
		$blocks  = self::parse( $content );
		$found   = self::find_by_type( $blocks, $block_name );

		return ! empty( $found );
	}

	// ========================================
	// Block Attributes
	// ========================================

	/**
	 * Get block attribute.
	 *
	 * @param array  $block     The block array.
	 * @param string $attribute The attribute name.
	 * @param mixed  $default   Default value if not found.
	 *
	 * @return mixed The attribute value or default.
	 */
	public static function get_attribute( array $block, string $attribute, $default = null ) {
		return $block['attrs'][ $attribute ] ?? $default;
	}

	/**
	 * Set block attribute.
	 *
	 * @param array  $block     The block to modify.
	 * @param string $attribute The attribute name.
	 * @param mixed  $value     The attribute value.
	 *
	 * @return array The modified block.
	 */
	public static function set_attribute( array $block, string $attribute, $value ): array {
		$block['attrs']               = $block['attrs'] ?? [];
		$block['attrs'][ $attribute ] = $value;

		return $block;
	}

	// ========================================
	// CSS Classes
	// ========================================

	/**
	 * Check if block has CSS class.
	 *
	 * @param array  $block     The block array.
	 * @param string $classname The class name to check.
	 *
	 * @return bool True if block has the class.
	 */
	public static function has_class( array $block, string $classname ): bool {
		$classes = self::get_attribute( $block, 'className', '' );
		if ( empty( $classes ) ) {
			return false;
		}

		$class_array = explode( ' ', $classes );

		return in_array( $classname, $class_array, true );
	}

	/**
	 * Add CSS class to block.
	 *
	 * @param array  $block     The block to modify.
	 * @param string $classname The class name to add.
	 *
	 * @return array The modified block.
	 */
	public static function add_class( array $block, string $classname ): array {
		$classes     = self::get_attribute( $block, 'className', '' );
		$class_array = empty( $classes ) ? [] : explode( ' ', $classes );

		if ( ! in_array( $classname, $class_array, true ) ) {
			$class_array[] = $classname;
		}

		return self::set_attribute( $block, 'className', implode( ' ', $class_array ) );
	}

	// ========================================
	// Statistics
	// ========================================

	/**
	 * Get block usage statistics (counts each block type).
	 *
	 * @param string|null $content Content to analyze. Null for current post.
	 *
	 * @return array Array of block names and their counts.
	 */
	public static function get_stats( ?string $content = null ): array {
		$content = $content ?? get_the_content();
		$blocks  = self::parse( $content );
		$stats   = [];

		self::count_blocks_recursive( $blocks, $stats );

		arsort( $stats );

		return $stats;
	}

	// ========================================
	// Modification
	// ========================================

	/**
	 * Remove all blocks of specific type from content.
	 *
	 * @param string $content    Content to modify.
	 * @param string $block_name Block name to remove.
	 *
	 * @return string Modified content.
	 */
	public static function remove_blocks( string $content, string $block_name ): string {
		$blocks   = self::parse( $content );
		$filtered = self::filter_blocks_recursive( $blocks, $block_name );

		return serialize_blocks( $filtered );
	}

	// ========================================
	// Block Registry
	// ========================================

	/**
	 * Check if block type is registered.
	 *
	 * @param string $block_name The block name.
	 *
	 * @return bool True if block type is registered.
	 */
	public static function is_registered( string $block_name ): bool {
		return WP_Block_Type_Registry::get_instance()->is_registered( $block_name );
	}

	/**
	 * Get registered block types as options array.
	 *
	 * @param bool $include_core Whether to include core blocks.
	 *
	 * @return array Array of ['value' => name, 'label' => title].
	 */
	public static function get_registered_options( bool $include_core = true ): array {
		$registry = WP_Block_Type_Registry::get_instance();
		$options  = [];

		foreach ( $registry->get_all_registered() as $name => $block_type ) {
			if ( ! $include_core && str_starts_with( $name, 'core/' ) ) {
				continue;
			}

			$options[] = [
				'value' => $name,
				'label' => $block_type->title ?? $name,
			];
		}

		return $options;
	}

	// ========================================
	// Private Helpers
	// ========================================

	/**
	 * Check if block matches type pattern.
	 *
	 * @param array  $block      The block array.
	 * @param string $block_name Block name or pattern.
	 *
	 * @return bool True if matches.
	 */
	private static function matches_type( array $block, string $block_name ): bool {
		$actual = $block['blockName'] ?? null;

		if ( ! $actual ) {
			return false;
		}

		// Handle wildcard
		if ( str_ends_with( $block_name, '*' ) ) {
			return str_starts_with( $actual, rtrim( $block_name, '*' ) );
		}

		return $actual === $block_name;
	}

	/**
	 * Recursively count blocks.
	 *
	 * @param array $blocks Array of blocks.
	 * @param array $stats  Stats array to update.
	 */
	private static function count_blocks_recursive( array $blocks, array &$stats ): void {
		foreach ( $blocks as $block ) {
			if ( isset( $block['blockName'] ) && $block['blockName'] ) {
				$stats[ $block['blockName'] ] = ( $stats[ $block['blockName'] ] ?? 0 ) + 1;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::count_blocks_recursive( $block['innerBlocks'], $stats );
			}
		}
	}

	/**
	 * Recursively filter out blocks.
	 *
	 * @param array  $blocks     Array of blocks.
	 * @param string $block_name Block name to remove.
	 *
	 * @return array Filtered blocks.
	 */
	private static function filter_blocks_recursive( array $blocks, string $block_name ): array {
		$filtered = [];

		foreach ( $blocks as $block ) {
			if ( self::matches_type( $block, $block_name ) ) {
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::filter_blocks_recursive( $block['innerBlocks'], $block_name );
			}

			$filtered[] = $block;
		}

		return $filtered;
	}

}