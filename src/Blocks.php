<?php
/**
 * Blocks Utility Class
 *
 * Provides utility functions for working with multiple WordPress blocks,
 * including parsing, filtering, searching, and bulk operations.
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
 * Operations for working with multiple WordPress blocks.
 */
class Blocks {

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
	 * Render array of blocks to HTML.
	 *
	 * @param array $blocks Array of block arrays.
	 *
	 * @return string The rendered HTML.
	 */
	public static function render( array $blocks ): string {
		return array_reduce( $blocks, function ( $content, $block ) {
			return $content . render_block( $block );
		}, '' );
	}

	/**
	 * Serialize array of blocks to string.
	 *
	 * @param array $blocks Array of block arrays.
	 *
	 * @return string The serialized blocks.
	 */
	public static function serialize( array $blocks ): string {
		return serialize_blocks( $blocks );
	}

	/**
	 * Get blocks from post content.
	 *
	 * @param int|WP_Post $post The post ID or post object.
	 *
	 * @return array Array of blocks.
	 */
	public static function get_from_post( $post ): array {
		$post = get_post( $post );

		return $post ? self::parse( $post->post_content ) : [];
	}

	// ========================================
	// Search & Filter Operations
	// ========================================

	/**
	 * Filter blocks using callback.
	 *
	 * @param array    $blocks   Array of blocks.
	 * @param callable $callback Filter callback function.
	 *
	 * @return array Filtered blocks.
	 */
	public static function filter( array $blocks, callable $callback ): array {
		return array_filter( $blocks, $callback );
	}

	/**
	 * Get blocks of specific type.
	 *
	 * @param array  $blocks     Array of blocks to search.
	 * @param string $block_name Block name or pattern (use * for wildcard).
	 *
	 * @return array Array of matching blocks.
	 */
	public static function get_by_type( array $blocks, string $block_name ): array {
		return self::filter( $blocks, function ( $block ) use ( $block_name ) {
			return Block::matches( $block, $block_name );
		} );
	}

	/**
	 * Search blocks recursively by type.
	 *
	 * @param array  $blocks     Array of blocks to search.
	 * @param string $block_name Block name or pattern.
	 *
	 * @return array Array of matching blocks found recursively.
	 */
	public static function search_by_type( array $blocks, string $block_name ): array {
		$found = [];

		foreach ( $blocks as $block ) {
			if ( Block::matches( $block, $block_name ) ) {
				$found[] = $block;
			}

			if ( Block::has_inner_blocks( $block ) ) {
				$found = array_merge( $found, self::search_by_type( $block['innerBlocks'], $block_name ) );
			}
		}

		return $found;
	}

	/**
	 * Get blocks by attribute value.
	 *
	 * @param array  $blocks    Array of blocks to search.
	 * @param string $attribute Attribute name.
	 * @param mixed  $value     Attribute value to match.
	 *
	 * @return array Array of matching blocks.
	 */
	public static function get_by_attribute( array $blocks, string $attribute, $value ): array {
		return self::filter( $blocks, function ( $block ) use ( $attribute, $value ) {
			return Block::get_attribute( $block, $attribute ) === $value;
		} );
	}

	/**
	 * Get blocks from specific content.
	 *
	 * @param string      $block_name The block name or pattern.
	 * @param string|null $content    The content to search. Null for current post.
	 *
	 * @return array Array of matching blocks.
	 */
	public static function get_from_content( string $block_name, ?string $content = null ): array {
		$content = $content ?? get_the_content();
		$blocks  = self::parse( $content );

		return self::search_by_type( $blocks, $block_name );
	}

	// ========================================
	// Block Type Registry
	// ========================================

	/**
	 * Get all registered block types.
	 *
	 * @return array Array of registered block types.
	 */
	public static function get_registered_types(): array {
		return WP_Block_Type_Registry::get_instance()->get_all_registered();
	}

	/**
	 * Get registered block types as options.
	 *
	 * @param bool $include_core Whether to include core blocks.
	 *
	 * @return array Array of ['value' => name, 'label' => title] items.
	 */
	public static function get_type_options( bool $include_core = true ): array {
		$block_types = self::get_registered_types();
		$options     = [];

		foreach ( $block_types as $name => $block_type ) {
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

	/**
	 * Search block types by name or title.
	 *
	 * @param string $search Search term.
	 *
	 * @return array Array of matching block type names.
	 */
	public static function search_types( string $search ): array {
		if ( empty( $search ) ) {
			return [];
		}

		$block_types = self::get_registered_types();
		$search      = strtolower( $search );
		$matches     = [];

		foreach ( $block_types as $name => $block_type ) {
			$title      = strtolower( $block_type->title ?? '' );
			$name_lower = strtolower( $name );

			if ( str_contains( $name_lower, $search ) || str_contains( $title, $search ) ) {
				$matches[] = $name;
			}
		}

		return $matches;
	}

	// ========================================
	// Statistics & Analysis
	// ========================================

	/**
	 * Count total blocks in content.
	 *
	 * @param string|null $content Content to analyze. Null for current post.
	 *
	 * @return int Total number of blocks.
	 */
	public static function count_total( ?string $content = null ): int {
		$content = $content ?? get_the_content();

		return count( self::parse( $content ) );
	}

	/**
	 * Count blocks of specific type.
	 *
	 * @param string      $block_name Block name or pattern.
	 * @param string|null $content    Content to analyze. Null for current post.
	 *
	 * @return int Number of matching blocks.
	 */
	public static function count_by_type( string $block_name, ?string $content = null ): int {
		return count( self::get_from_content( $block_name, $content ) );
	}

	/**
	 * Get block usage statistics.
	 *
	 * @param string|null $content Content to analyze. Null for current post.
	 *
	 * @return array Array of block names and their counts.
	 */
	public static function get_usage_stats( ?string $content = null ): array {
		$content     = $content ?? get_the_content();
		$blocks      = self::parse( $content );
		$block_names = array_column( $blocks, 'blockName' );

		return array_count_values( array_filter( $block_names ) );
	}

	/**
	 * Get most used block type.
	 *
	 * @param string|null $content Content to analyze. Null for current post.
	 *
	 * @return string|null Most used block name or null if no blocks.
	 */
	public static function get_most_used_type( ?string $content = null ): ?string {
		$stats = self::get_usage_stats( $content );

		if ( empty( $stats ) ) {
			return null;
		}

		arsort( $stats );

		return key( $stats );
	}

	// ========================================
	// Conditional Checks
	// ========================================

	/**
	 * Check if content uses specific block type.
	 *
	 * @param string      $block_name Block name or pattern.
	 * @param string|null $content    Content to check. Null for current post.
	 *
	 * @return bool True if content uses the block type.
	 */
	public static function uses_block_type( string $block_name, ?string $content = null ): bool {
		return self::count_by_type( $block_name, $content ) > 0;
	}

	/**
	 * Check if Gutenberg editor is available.
	 *
	 * @return bool True if block editor functions are available.
	 */
	public static function is_editor_available(): bool {
		return function_exists( 'parse_blocks' ) && function_exists( 'render_block' );
	}

	/**
	 * Check if current request is block editor preview.
	 *
	 * @return bool True if rendering block preview.
	 */
	public static function is_preview_request(): bool {
		global $wp;

		if ( ! defined( 'REST_REQUEST' ) || ! is_user_logged_in() ) {
			return false;
		}

		if ( ! $wp || empty( $wp->query_vars['rest_route'] ) ) {
			return false;
		}

		return str_contains( $wp->query_vars['rest_route'], '/block-renderer/' );
	}

	// ========================================
	// Utility Methods
	// ========================================

	/**
	 * Replace blocks of specific type in content.
	 *
	 * @param string   $content     Content to modify.
	 * @param string   $block_name  Block name to replace.
	 * @param callable $replacement Callback that receives block and returns new block.
	 *
	 * @return string Modified content.
	 */
	public static function replace_by_type( string $content, string $block_name, callable $replacement ): string {
		$blocks = self::parse( $content );

		$modified_blocks = array_map( function ( $block ) use ( $block_name, $replacement ) {
			if ( Block::matches( $block, $block_name ) ) {
				return $replacement( $block );
			}

			return $block;
		}, $blocks );

		return self::serialize( $modified_blocks );
	}

	/**
	 * Remove blocks of specific type from content.
	 *
	 * @param string $content    Content to modify.
	 * @param string $block_name Block name to remove.
	 *
	 * @return string Modified content.
	 */
	public static function remove_by_type( string $content, string $block_name ): string {
		$blocks = self::parse( $content );

		$filtered_blocks = self::filter( $blocks, function ( $block ) use ( $block_name ) {
			return ! Block::matches( $block, $block_name );
		} );

		return self::serialize( $filtered_blocks );
	}

}