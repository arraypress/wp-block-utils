# WordPress Block Utils - Lean Block Management

A lightweight WordPress library for working with blocks and Gutenberg content. Provides clean APIs for block operations, content parsing, and analysis perfect for plugins and themes.

## Features

* 🎯 **Clean API**: WordPress-style snake_case methods with consistent interfaces
* 🔍 **Built-in Search**: Block search with recursive inner block support
* 📋 **Form-Ready Options**: Perfect value/label arrays for block type selects
* ⚡ **Parse & Render**: Easy content parsing and block rendering
* 🔧 **Attribute Management**: Simple block attribute manipulation
* 📊 **Usage Analytics**: Block usage statistics and analysis
* 🎨 **Pattern Matching**: Wildcard support for block type matching
* 🧱 **Inner Blocks**: Full support for nested block operations

## Requirements

* PHP 7.4 or later
* WordPress 5.0 or later (with Gutenberg)

## Installation

```bash
composer require arraypress/wp-block-utils
```

## Basic Usage

### Working with Single Blocks

```php
use ArrayPress\BlockUtils\Block;

// Get block information
$block_name = Block::get_name( $block );
$attributes = Block::get_attributes( $block );
$inner_html = Block::get_inner_html( $block );

// Get specific attribute
$className = Block::get_attribute( $block, 'className', '' );

// Manipulate attributes
$block = Block::set_attribute( $block, 'className', 'my-custom-class' );
$block = Block::remove_attribute( $block, 'unwanted-attr' );

// Work with inner blocks
$inner_blocks = Block::get_inner_blocks( $block );
$block        = Block::add_inner_block( $block, $new_inner_block );

// Check block properties
if ( Block::has_inner_blocks( $block ) ) {
	// Block has inner blocks
}

if ( Block::is_reusable( $block ) ) {
	// Block is a reusable block
}

// Block type operations
if ( Block::type_exists( 'custom/my-block' ) ) {
	$is_dynamic = Block::is_dynamic( 'custom/my-block' );
	$category   = Block::get_category( 'custom/my-block' );
}

// Pattern matching
if ( Block::matches( $block, 'core/*' ) ) {
	// Block is a core block
}

if ( Block::matches( $block, 'core/heading' ) ) {
	// Block is specifically a heading block
}

// Convert blocks
$block_string  = Block::to_string( $block );
$block_array   = Block::to_array( $block_string );
$rendered_html = Block::render( $block );

// Utility functions
$short_name = Block::strip_core_namespace( 'core/paragraph' ); // Returns: 'paragraph'
```

### Working with Multiple Blocks

```php
use ArrayPress\BlockUtils\Blocks;

// Parse content
$blocks      = Blocks::parse( $post_content );
$post_blocks = Blocks::get_from_post( 123 );

// Render blocks
$html       = Blocks::render( $blocks );
$serialized = Blocks::serialize( $blocks );

// Search and filter
$headings      = Blocks::get_by_type( $blocks, 'core/heading' );
$core_blocks   = Blocks::get_by_type( $blocks, 'core/*' );
$custom_blocks = Blocks::search_by_type( $blocks, 'custom/*' );

// Search by attributes
$large_headings = Blocks::get_by_attribute( $blocks, 'level', 1 );

// Get from current content
$images    = Blocks::get_from_content( 'core/image' );
$galleries = Blocks::get_from_content( 'core/gallery', $custom_content );

// Block type registry
$all_types    = Blocks::get_registered_types();
$type_options = Blocks::get_type_options(); // For form selects
$custom_only  = Blocks::get_type_options( false ); // Exclude core blocks

// Search block types
$matching_types = Blocks::search_types( 'heading' );

// Statistics and analysis
$total_blocks  = Blocks::count_total();
$heading_count = Blocks::count_by_type( 'core/heading' );
$usage_stats   = Blocks::get_usage_stats();
$most_used     = Blocks::get_most_used_type();

// Conditional checks
if ( Blocks::uses_block_type( 'core/gallery' ) ) {
	// Content uses gallery blocks
}

if ( Blocks::is_editor_available() ) {
	// Gutenberg functions are available
}

// Content manipulation
$modified_content = Blocks::replace_by_type( $content, 'core/heading', function ( $block ) {
	return Block::set_attribute( $block, 'className', 'custom-heading' );
} );

$clean_content = Blocks::remove_by_type( $content, 'core/separator' );
```

### Advanced Examples

```php
// Find all headings and analyze their levels
$content  = get_the_content();
$headings = Blocks::get_from_content( 'core/heading', $content );

$heading_levels = array_map( function ( $heading ) {
	return Block::get_attribute( $heading, 'level', 2 );
}, $headings );

$level_distribution = array_count_values( $heading_levels );

// Replace all paragraphs with custom styling
$new_content = Blocks::replace_by_type( $content, 'core/paragraph', function ( $block ) {
	$existing_class = Block::get_attribute( $block, 'className', '' );
	$new_class      = trim( $existing_class . ' custom-paragraph' );

	return Block::set_attribute( $block, 'className', $new_class );
} );

// Get usage statistics for reporting
$stats = Blocks::get_usage_stats();
echo "Block Usage Report:\n";
foreach ( $stats as $block_type => $count ) {
	$clean_name = Block::strip_core_namespace( $block_type );
	echo "- {$clean_name}: {$count} blocks\n";
}

// Find blocks with specific attributes
$blocks_with_ids = Blocks::filter( $blocks, function ( $block ) {
	return ! empty( Block::get_attribute( $block, 'anchor' ) );
} );

// Check if content is block-based
if ( Blocks::count_total() > 0 ) {
	echo "This content uses the block editor";
} else {
	echo "This might be classic editor content";
}
```

### Block Type Options for Forms

```php
// Get all block types for admin select
$block_options = Blocks::get_type_options();
// Returns: [['value' => 'core/paragraph', 'label' => 'Paragraph'], ...]

// Get only custom blocks
$custom_options = Blocks::get_type_options( false );

// Use in WordPress settings
add_settings_field(
	'allowed_blocks',
	'Allowed Block Types',
	function () {
		$options = Blocks::get_type_options();
		// Render select with options
	},
	'my_settings_page'
);
```

## Key Features

- **Pattern Matching**: Use `core/*` to match all core blocks
- **Recursive Search**: Find blocks deep within inner blocks
- **Attribute Management**: Easy get/set/remove operations
- **Usage Analytics**: Understand block usage patterns
- **Form Integration**: Ready-made options for admin interfaces
- **Content Manipulation**: Replace and remove blocks programmatically
- **Type Safety**: Proper null handling and type checking

## Requirements

- PHP 7.4+
- WordPress 5.0+ (with Gutenberg support)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-block-utils)
- [Issue Tracker](https://github.com/arraypress/wp-block-utils/issues)