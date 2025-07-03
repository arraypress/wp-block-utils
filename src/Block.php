<?php
/**
 * Block Utility Class
 *
 * Provides utility functions for working with individual WordPress blocks,
 * including attribute manipulation, content extraction, and block analysis.
 *
 * @package ArrayPress\BlockUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\BlockUtils;

use WP_Block_Type;
use WP_Block_Type_Registry;

/**
 * Block Class
 *
 * Core operations for working with individual WordPress blocks.
 */
class Block {

	/**
	 * Get block name from block array.
	 *
	 * @param array $block The block array.
	 *
	 * @return string|null The block name or null if not found.
	 */
	public static function get_name( array $block ): ?string {
		return $block['blockName'] ?? null;
	}

	/**
	 * Get block attributes.
	 *
	 * @param array $block The block array.
	 *
	 * @return array The block's attributes.
	 */
	public static function get_attributes( array $block ): array {
		return $block['attrs'] ?? [];
	}

	/**
	 * Get specific attribute value.
	 *
	 * @param array  $block     The block array.
	 * @param string $attribute The attribute name.
	 * @param mixed  $default   Default value if attribute not found.
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

	/**
	 * Remove block attribute.
	 *
	 * @param array  $block     The block to modify.
	 * @param string $attribute The attribute name to remove.
	 *
	 * @return array The modified block.
	 */
	public static function remove_attribute( array $block, string $attribute ): array {
		unset( $block['attrs'][ $attribute ] );

		return $block;
	}

	/**
	 * Get block inner HTML content.
	 *
	 * @param array $block The block array.
	 *
	 * @return string The inner HTML content.
	 */
	public static function get_inner_html( array $block ): string {
		return $block['innerHTML'] ?? '';
	}

	/**
	 * Set block inner HTML content.
	 *
	 * @param array  $block The block to modify.
	 * @param string $html  The new inner HTML content.
	 *
	 * @return array The modified block.
	 */
	public static function set_inner_html( array $block, string $html ): array {
		$block['innerHTML'] = $html;

		return $block;
	}

	// ========================================
	// Inner Blocks Operations
	// ========================================

	/**
	 * Get inner blocks.
	 *
	 * @param array $block The block array.
	 *
	 * @return array The inner blocks.
	 */
	public static function get_inner_blocks( array $block ): array {
		return $block['innerBlocks'] ?? [];
	}

	/**
	 * Set inner blocks.
	 *
	 * @param array $block        The block to modify.
	 * @param array $inner_blocks The new inner blocks.
	 *
	 * @return array The modified block.
	 */
	public static function set_inner_blocks( array $block, array $inner_blocks ): array {
		$block['innerBlocks'] = $inner_blocks;

		return $block;
	}

	/**
	 * Add inner block.
	 *
	 * @param array $block       The block to modify.
	 * @param array $inner_block The inner block to add.
	 *
	 * @return array The modified block.
	 */
	public static function add_inner_block( array $block, array $inner_block ): array {
		$block['innerBlocks']   = $block['innerBlocks'] ?? [];
		$block['innerBlocks'][] = $inner_block;

		return $block;
	}

	/**
	 * Remove inner block by index.
	 *
	 * @param array $block The block to modify.
	 * @param int   $index The index of the inner block to remove.
	 *
	 * @return array The modified block.
	 */
	public static function remove_inner_block( array $block, int $index ): array {
		unset( $block['innerBlocks'][ $index ] );
		$block['innerBlocks'] = array_values( $block['innerBlocks'] ?? [] );

		return $block;
	}

	/**
	 * Check if block has inner blocks.
	 *
	 * @param array $block The block array.
	 *
	 * @return bool True if block has inner blocks.
	 */
	public static function has_inner_blocks( array $block ): bool {
		return ! empty( $block['innerBlocks'] );
	}

	// ========================================
	// Block Type Operations
	// ========================================

	/**
	 * Get block type object.
	 *
	 * @param string $block_name The block name.
	 *
	 * @return WP_Block_Type|null The block type object or null if not found.
	 */
	public static function get_type( string $block_name ): ?WP_Block_Type {
		return WP_Block_Type_Registry::get_instance()->get_registered( $block_name );
	}

	/**
	 * Check if block type exists.
	 *
	 * @param string $block_name The block name.
	 *
	 * @return bool True if block type is registered.
	 */
	public static function type_exists( string $block_name ): bool {
		return ! is_null( self::get_type( $block_name ) );
	}

	/**
	 * Check if block type is dynamic.
	 *
	 * @param string $block_name The block name.
	 *
	 * @return bool True if block is dynamic (server-rendered).
	 */
	public static function is_dynamic( string $block_name ): bool {
		$block_type = self::get_type( $block_name );

		return $block_type && $block_type->is_dynamic();
	}

	/**
	 * Get block category.
	 *
	 * @param string $block_name The block name.
	 *
	 * @return string|null The block category or null if not found.
	 */
	public static function get_category( string $block_name ): ?string {
		$block_type = self::get_type( $block_name );

		return $block_type ? $block_type->category : null;
	}

	// ========================================
	// Block Matching & Validation
	// ========================================

	/**
	 * Check if block matches name pattern.
	 *
	 * @param array  $block      The block array.
	 * @param string $block_name The block name or pattern (use * for wildcard).
	 *
	 * @return bool True if block matches pattern.
	 */
	public static function matches( array $block, string $block_name ): bool {
		$actual_name = self::get_name( $block );

		if ( ! $actual_name ) {
			return false;
		}

		// Wildcard pattern matching
		if ( str_ends_with( $block_name, '*' ) ) {
			return str_starts_with( $actual_name, rtrim( $block_name, '*' ) );
		}

		return $actual_name === $block_name;
	}

	/**
	 * Check if block is reusable.
	 *
	 * @param array $block The block array.
	 *
	 * @return bool True if block is reusable.
	 */
	public static function is_reusable( array $block ): bool {
		return self::get_name( $block ) === 'core/block';
	}

	/**
	 * Check if block supports feature.
	 *
	 * @param array  $block   The block array.
	 * @param string $feature The feature to check.
	 *
	 * @return bool True if block supports feature.
	 */
	public static function supports_feature( array $block, string $feature ): bool {
		$block_name = self::get_name( $block );
		if ( ! $block_name ) {
			return false;
		}

		$block_type = self::get_type( $block_name );

		return $block_type && ! empty( $block_type->supports[ $feature ] );
	}

	// ========================================
	// Conversion & Serialization
	// ========================================

	/**
	 * Convert block array to string.
	 *
	 * @param array $block The block array.
	 *
	 * @return string The serialized block string.
	 */
	public static function to_string( array $block ): string {
		return serialize_block( $block );
	}

	/**
	 * Convert block string to array.
	 *
	 * @param string $block_string The block string.
	 *
	 * @return array The parsed block array.
	 */
	public static function to_array( string $block_string ): array {
		$blocks = parse_blocks( $block_string );

		return $blocks[0] ?? [];
	}

	/**
	 * Render block to HTML.
	 *
	 * @param array $block The block array.
	 *
	 * @return string The rendered HTML.
	 */
	public static function render( array $block ): string {
		return render_block( $block );
	}

	// ========================================
	// Utility Methods
	// ========================================

	/**
	 * Strip core namespace from block name.
	 *
	 * @param string $block_name The block name.
	 *
	 * @return string Block name without core/ prefix.
	 */
	public static function strip_core_namespace( string $block_name ): string {
		return str_starts_with( $block_name, 'core/' )
			? substr( $block_name, 5 )
			: $block_name;
	}

	/**
	 * Get reusable block content.
	 *
	 * @param int $block_id The reusable block ID.
	 *
	 * @return string The block content.
	 */
	public static function get_reusable_content( int $block_id ): string {
		$post = get_post( $block_id );

		return $post ? $post->post_content : '';
	}

}