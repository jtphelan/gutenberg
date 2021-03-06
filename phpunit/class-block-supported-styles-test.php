<?php
/**
 * Test block supported styles.
 *
 * @package Gutenberg
 */

class Block_Supported_Styles_Test extends WP_UnitTestCase {

	/**
	 * Registered block names.
	 *
	 * @var string[]
	 */
	private $registered_block_names = array();

	/**
	 * Sets up each test method.
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Tear down each test method.
	 */
	public function tearDown() {
		parent::tearDown();

		while ( ! empty( $this->registered_block_names ) ) {
			$block_name = array_pop( $this->registered_block_names );
			unregister_block_type( $block_name );
		}
	}

	/**
	 * Registers a block type.
	 *
	 * @param string|WP_Block_Type $name Block type name including namespace, or alternatively a
	 *                                   complete WP_Block_Type instance. In case a WP_Block_Type
	 *                                   is provided, the $args parameter will be ignored.
	 * @param array                $args {
	 *     Optional. Array of block type arguments. Any arguments may be defined, however the
	 *     ones described below are supported by default. Default empty array.
	 *
	 *     @type callable $render_callback Callback used to render blocks of this block type.
	 * }
	 */
	protected function register_block_type( $name, $args ) {
		register_block_type( $name, $args );

		$this->registered_block_names[] = $name;
	}

	/**
	 * Retrieves attribute such as 'class' or 'style' from the rendered block string.
	 *
	 * @param string $attribute Name of attribute to get.
	 * @param string $block String of rendered block to check.
	 */
	private function get_attribute_from_block( $attribute, $block ) {
		$start_index = strpos( $block, $attribute . '="' ) + strlen( $attribute ) + 2;
		$split_arr   = substr( $block, $start_index );
		$end_index   = strpos( $split_arr, '"' );
		return substr( $split_arr, 0, $end_index );
	}

	/**
	 * Retrieves block content from the rendered block string
	 * (i.e. what's wrapped by the block wrapper `<div />`).
	 *
	 * @param string $block String of rendered block to check.
	 */
	private function get_content_from_block( $block ) {
		$start_index = strpos( $block, '>' ) + 1;
		$split_arr   = substr( $block, $start_index );
		$end_index   = strpos( $split_arr, '<' );
		return substr( $split_arr, 0, $end_index );
	}

	/**
	 * Runs assertions that the rendered output has expected class/style attrs.
	 *
	 * @param array  $block Block to render.
	 * @param string $expected_classes Expected output class attr string.
	 * @param string $expected_styles Expected output styles attr string.
	 */
	private function assert_styles_and_classes_match( $block, $expected_classes, $expected_styles ) {
		$styled_block = apply_filters( 'render_block', self::BLOCK_MARKUP, $block );
		$class_list   = $this->get_attribute_from_block( 'class', $styled_block );
		$style_list   = $this->get_attribute_from_block( 'style', $styled_block );

		$this->assertEquals( $expected_classes, $class_list );
		$this->assertEquals( $expected_styles, $style_list );
	}

	/**
	 * Runs assertions that the rendered output has expected content and class/style attrs.
	 *
	 * @param array  $block Block to render.
	 * @param string $expected_classes Expected output class attr string.
	 * @param string $expected_styles Expected output styles attr string.
	 */
	private function assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles ) {
		$styled_block = apply_filters( 'render_block', self::BLOCK_MARKUP, $block );
		$content      = $this->get_content_from_block( $styled_block );
		$class_list   = $this->get_attribute_from_block( 'class', $styled_block );
		$style_list   = $this->get_attribute_from_block( 'style', $styled_block );

		$this->assertEquals( self::BLOCK_CONTENT, $content );
		$this->assertEquals( $expected_classes, $class_list );
		$this->assertEquals( $expected_styles, $style_list );
	}

	/**
	 * Block content to test with (i.e. what's wrapped by the block wrapper `<div />`).
	 *
	 * @var string
	 */
	const BLOCK_CONTENT = 'Some non-Latin chärs to make sure DOM öperations don\'t mess them up: こんにちは';

	/**
	 * Example block markup string to test with.
	 *
	 * @var string
	 */
	const BLOCK_MARKUP = '<div class="foo-bar-class" style="test:style;">' . self::BLOCK_CONTENT . '</div>';

	/**
	 * Tests color support for named color support for named colors.
	 */
	function test_named_color_support() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalColor' => true,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'textColor'       => 'red',
				'backgroundColor' => 'black',
				// The following should not be applied (subcatagories of color support).
				'gradient'        => 'some-gradient',
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example has-text-color has-red-color has-background has-black-background-color';
		$expected_styles  = 'test: style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests color support for custom colors.
	 */
	function test_custom_color_support() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalColor' => true,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'style' => array(
					'color' => array(
						'text'       => '#000',
						'background' => '#fff',
						// The following should not be applied (subcatagories of color support).
						'gradient'   => 'some-gradient',
						'style'      => array( 'color' => array( 'link' => '#fff' ) ),
					),
				),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_styles  = 'test: style; color: #000; background-color: #fff;';
		$expected_classes = 'foo-bar-class wp-block-example has-text-color has-background';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests link color support for named colors.
	 */
	function test_named_link_color_support() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalColor' => array(
					'linkColor' => true,
				),
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'style' => array( 'color' => array( 'link' => 'var:preset|color|red' ) ),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example has-link-color';
		$expected_styles  = 'test: style; --wp--style--color--link: var(--wp--preset--color--red);';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests link color support for custom colors.
	 */
	function test_custom_link_color_support() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalColor' => array(
					'linkColor' => true,
				),
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'style' => array( 'color' => array( 'link' => '#fff' ) ),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example has-link-color';
		$expected_styles  = 'test: style; --wp--style--color--link: #fff;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests gradient color support for named gradients.
	 */
	function test_named_gradient_support() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalColor' => array(
					'gradients' => true,
				),
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'gradient' => 'red',
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example has-background has-red-gradient-background';
		$expected_styles  = 'test: style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests gradient color support for custom gradients.
	 */
	function test_custom_gradient_support() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalColor' => array(
					'gradients' => true,
				),
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'style' => array( 'color' => array( 'gradient' => 'some-gradient-style' ) ),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example has-background';
		$expected_styles  = 'test: style; background: some-gradient-style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests that style attributes for colors are not applied without the support flag.
	 */
	function test_color_unsupported() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'textColor'       => 'red',
				'backgroundColor' => 'black',
				'style'           => array(
					'color' => array(
						'text'       => '#000',
						'background' => '#fff',
						'link'       => '#ggg',
						'gradient'   => 'some-gradient',
					),
				),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example';
		$expected_styles  = 'test: style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests support for named font sizes.
	 */
	function test_named_font_size() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalFontSize' => true,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'fontSize' => 'large',
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example has-large-font-size';
		$expected_styles  = 'test: style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests support for custom font sizes.
	 */
	function test_custom_font_size() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalFontSize' => true,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'style' => array( 'typography' => array( 'fontSize' => '10' ) ),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example';
		$expected_styles  = 'test: style; font-size: 10px;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests that font size attributes are not applied without support flag.
	 */
	function test_font_size_unsupported() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'fontSize' => 'large',
				'style'    => array( 'typography' => array( 'fontSize' => '10' ) ),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example';
		$expected_styles  = 'test: style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests line height support.
	 */
	function test_line_height() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalLineHeight' => true,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'style' => array( 'typography' => array( 'lineHeight' => '10' ) ),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example';
		$expected_styles  = 'test: style; line-height: 10;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests line height not applied without support flag.
	 */
	function test_line_height_unsupported() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'style' => array( 'typography' => array( 'lineHeight' => '10' ) ),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example';
		$expected_styles  = 'test: style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests support for block alignment.
	 */
	function test_block_alignment() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'align' => true,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'align' => 'wide',
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example alignwide';
		$expected_styles  = 'test: style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests block alignment requires support to be added.
	 */
	function test_block_alignment_unsupported() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'align' => 'wide',
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example';
		$expected_styles  = 'test: style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests all support flags together to ensure they work together as expected.
	 */
	function test_all_supported() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalColor'      => array(
					'gradients' => true,
					'linkColor' => true,
				),
				'__experimentalFontSize'   => true,
				'__experimentalLineHeight' => true,
				'align'                    => true,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'align' => 'wide',
				'style' => array(
					'color'      => array(
						'text'       => '#000',
						'background' => '#fff',
						'gradient'   => 'some-gradient',
						'style'      => array( 'color' => array( 'link' => '#fff' ) ),
					),
					'typography' => array(
						'lineHeight' => '20',
						'fontSize'   => '10',
					),
				),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example has-text-color has-background alignwide';
		$expected_styles  = 'test: style; color: #000; background-color: #fff; background: some-gradient; font-size: 10px; line-height: 20;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests that only styles for the supported flag are added.
	 * Verify one support enabled does not imply multiple supports enabled.
	 */
	function test_one_supported() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'__experimentalFontSize' => true,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'align' => 'wide',
				'style' => array(
					'color'      => array(
						'text'       => '#000',
						'background' => '#fff',
						'gradient'   => 'some-gradient',
						'style'      => array( 'color' => array( 'link' => '#fff' ) ),
					),
					'typography' => array(
						'lineHeight' => '20',
						'fontSize'   => '10',
					),
				),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class wp-block-example';
		$expected_styles  = 'test: style; font-size: 10px;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests there is no change without 'render_callback' in block_type.
	 */
	function test_render_callback_required() {
		$block_type_settings = array(
			'attributes' => array(),
			'supports'   => array(
				'align'                    => true,
				'__experimentalColor'      => array(
					'gradients' => true,
					'linkColor' => true,
				),
				'__experimentalFontSize'   => true,
				'__experimentalLineHeight' => true,
			),
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'style' => array(
					'align'      => 'wide',
					'color'      => array(
						'text'       => '#000',
						'background' => '#fff',
						'gradient'   => 'some-gradient',
						'style'      => array( 'color' => array( 'link' => '#fff' ) ),
					),
					'typography' => array(
						'lineHeight' => '20',
						'fontSize'   => '10',
					),
				),
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_classes = 'foo-bar-class';
		$expected_styles  = 'test:style;';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests custom classname server-side block support.
	 */
	function test_custom_classnames_support() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'className' => 'my-custom-classname',
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_styles  = 'test: style;';
		$expected_classes = 'foo-bar-class wp-block-example my-custom-classname';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests custom classname server-side block support opt-out.
	 */
	function test_custom_classnames_support_opt_out() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'customClassName' => false,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(
				'className' => 'my-custom-classname',
			),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_styles  = 'test: style;';
		$expected_classes = 'foo-bar-class wp-block-example';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}

	/**
	 * Tests generated classname server-side block support opt-out.
	 */
	function test_generatted_classnames_support_opt_out() {
		$block_type_settings = array(
			'attributes'      => array(),
			'supports'        => array(
				'className' => false,
			),
			'render_callback' => true,
		);
		$this->register_block_type( 'core/example', $block_type_settings );

		$block = array(
			'blockName'    => 'core/example',
			'attrs'        => array(),
			'innerBlock'   => array(),
			'innerContent' => array(),
			'innerHTML'    => array(),
		);

		$expected_styles  = 'test:style;';
		$expected_classes = 'foo-bar-class';

		$this->assert_content_and_styles_and_classes_match( $block, $expected_classes, $expected_styles );
	}
}
