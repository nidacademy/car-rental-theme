<?php
/**
 * Elementor widget "Exotic Cars Brands": the glass brand bar of the homepage hero, fully editable in Elementor
 * (label, brands, logos, links, colours, sizes). Same look as [orego_brands style="hero"].
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'elementor/widgets/register', function ( $wm ) {
	if ( ! class_exists( 'OreGO_Brands_Widget' ) ) {
		class OreGO_Brands_Widget extends \Elementor\Widget_Base {

			public function get_name() {
				return 'orego_brands';
			}

			public function get_title() {
				return 'Car Brands';
			}

			public function get_icon() {
				return 'eicon-logo';
			}

			public function get_categories() {
				return array( 'general' );
			}

			public function get_keywords() {
				return array( 'brands', 'logos', 'orego', 'cars' );
			}

			private function builtin() {
				$out = array();
				foreach ( glob( get_template_directory() . '/assets/brands/*.svg' ) as $f ) {
					$slug         = basename( $f, '.svg' );
					$out[ $slug ] = ucfirst( $slug );
				}
				return $out;
			}

			protected function register_controls() {
				$c = '\Elementor\Controls_Manager';

				/* Content: label */
				$this->start_controls_section( 'sec_label', array( 'label' => 'Label' ) );
				$this->add_control( 'show_label', array( 'label' => 'Show label', 'type' => $c::SWITCHER, 'default' => 'yes' ) );
				$this->add_control( 'label_text', array( 'label' => 'Text', 'type' => $c::TEXT, 'default' => 'Our brands', 'condition' => array( 'show_label' => 'yes' ) ) );
				$this->end_controls_section();

				/* Content: brands */
				$this->start_controls_section( 'sec_brands', array( 'label' => 'Brands' ) );
				$rep = new \Elementor\Repeater();
				$rep->add_control( 'name', array( 'label' => 'Brand name', 'type' => $c::TEXT, 'default' => 'Brand', 'label_block' => true ) );
				$rep->add_control( 'logo_source', array( 'label' => 'Logo', 'type' => $c::SELECT, 'default' => 'builtin', 'options' => array( 'builtin' => 'Built-in logo', 'upload' => 'Upload an image' ) ) );
				$rep->add_control( 'builtin', array( 'label' => 'Built-in logo', 'type' => $c::SELECT, 'default' => 'dacia', 'options' => $this->builtin(), 'condition' => array( 'logo_source' => 'builtin' ) ) );
				$rep->add_control( 'image', array( 'label' => 'Image', 'type' => $c::MEDIA, 'description' => 'A square logo works best. Built-in logos follow the icon colours; uploaded images keep their own colours.', 'condition' => array( 'logo_source' => 'upload' ) ) );
				$rep->add_control( 'link_type', array( 'label' => 'Link', 'type' => $c::SELECT, 'default' => 'auto', 'options' => array( 'auto' => 'Search this brand in Cars', 'custom' => 'Custom link', 'none' => 'No link' ) ) );
				$rep->add_control( 'link', array( 'label' => 'URL', 'type' => $c::URL, 'condition' => array( 'link_type' => 'custom' ) ) );
				$defaults = array();
				foreach ( ( function_exists( 'orego_hero_brands' ) ? orego_hero_brands() : array() ) as $slug => $label ) {
					$defaults[] = array( 'name' => $label, 'logo_source' => 'builtin', 'builtin' => $slug, 'link_type' => 'auto' );
				}
				$this->add_control( 'brands', array( 'label' => 'Brands', 'type' => $c::REPEATER, 'fields' => $rep->get_controls(), 'default' => $defaults, 'title_field' => '{{{ name }}}' ) );
				$this->add_control( 'only_fleet', array( 'label' => 'Link only brands in your fleet', 'type' => $c::SWITCHER, 'default' => 'yes', 'description' => 'With "Search this brand in Cars", brands you don’t rent yet are shown without a link.' ) );
				$this->end_controls_section();

				/* Style: bar */
				$this->start_controls_section( 'st_bar', array( 'label' => 'Bar', 'tab' => $c::TAB_STYLE ) );
				$this->add_responsive_control( 'bar_align', array( 'label' => 'Alignment', 'type' => $c::CHOOSE, 'options' => array( 'flex-start' => array( 'title' => 'Start', 'icon' => 'eicon-h-align-left' ), 'center' => array( 'title' => 'Center', 'icon' => 'eicon-h-align-center' ), 'flex-end' => array( 'title' => 'End', 'icon' => 'eicon-h-align-right' ) ), 'selectors' => array( '{{WRAPPER}}' => 'align-self:{{VALUE}}' ) ) );
				$this->add_control( 'bar_bg', array( 'label' => 'Background', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb' => 'background:{{VALUE}}' ) ) );
				$this->add_control( 'bar_border', array( 'label' => 'Border colour', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb' => 'border-color:{{VALUE}}' ) ) );
				$this->add_responsive_control( 'bar_radius', array( 'label' => 'Corner radius', 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'selectors' => array( '{{WRAPPER}} .og-hb' => 'border-radius:{{SIZE}}{{UNIT}}' ) ) );
				$this->add_responsive_control( 'bar_padding', array( 'label' => 'Padding', 'type' => $c::DIMENSIONS, 'size_units' => array( 'px' ), 'selectors' => array( '{{WRAPPER}} .og-hb, html[dir=rtl] {{WRAPPER}} .og-hb' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}' ) ) );
				$this->add_control( 'bar_blur', array( 'label' => 'Glass blur', 'type' => $c::SWITCHER, 'default' => 'yes', 'prefix_class' => 'og-hb-blur-' ) );
				$this->end_controls_section();

				/* Style: label */
				$this->start_controls_section( 'st_label', array( 'label' => 'Label', 'tab' => $c::TAB_STYLE, 'condition' => array( 'show_label' => 'yes' ) ) );
				$this->add_control( 'label_color', array( 'label' => 'Text colour', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb-label' => 'color:{{VALUE}}' ) ) );
				$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'label_typo', 'selector' => '{{WRAPPER}} .og-hb-label, html[dir=ltr] {{WRAPPER}} .og-hb-label' ) );
				$this->add_control( 'divider_color', array( 'label' => 'Divider colour', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb-label::after, {{WRAPPER}} .og-hb-label::before' => 'background:{{VALUE}}' ) ) );
				$this->end_controls_section();

				/* Style: logos */
				$this->start_controls_section( 'st_logo', array( 'label' => 'Logos', 'tab' => $c::TAB_STYLE ) );
				$this->add_responsive_control( 'tile_size', array( 'label' => 'Circle size', 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 30, 'max' => 110 ) ), 'selectors' => array( '{{WRAPPER}} .og-hb-list a, {{WRAPPER}} .og-hb-list span' => 'width:{{SIZE}}{{UNIT}};height:{{SIZE}}{{UNIT}}' ) ) );
				$this->add_responsive_control( 'icon_size', array( 'label' => 'Logo size', 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 12, 'max' => 80 ) ), 'selectors' => array( '{{WRAPPER}} .og-hb-list svg, {{WRAPPER}} .og-hb-list img' => 'width:{{SIZE}}{{UNIT}};height:{{SIZE}}{{UNIT}}' ) ) );
				$this->add_responsive_control( 'gap', array( 'label' => 'Space between logos', 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'selectors' => array( '{{WRAPPER}} .og-hb-list' => 'gap:{{SIZE}}{{UNIT}}' ) ) );
				$this->add_control( 'tile_radius', array( 'label' => 'Circle roundness (%)', 'type' => $c::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 0, 'max' => 50 ) ), 'selectors' => array( '{{WRAPPER}} .og-hb-list a, {{WRAPPER}} .og-hb-list span' => 'border-radius:{{SIZE}}%' ) ) );
				$this->start_controls_tabs( 'logo_tabs' );
				$this->start_controls_tab( 'logo_normal', array( 'label' => 'Normal' ) );
				$this->add_control( 'icon_color', array( 'label' => 'Logo colour', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb-list a, {{WRAPPER}} .og-hb-list span' => 'color:{{VALUE}} !important' ) ) );
				$this->add_control( 'tile_bg', array( 'label' => 'Circle background', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb-list a, {{WRAPPER}} .og-hb-list span' => 'background:{{VALUE}}' ) ) );
				$this->add_control( 'tile_border', array( 'label' => 'Circle border', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb-list a, {{WRAPPER}} .og-hb-list span' => 'border-color:{{VALUE}}' ) ) );
				$this->end_controls_tab();
				$this->start_controls_tab( 'logo_hover', array( 'label' => 'Hover' ) );
				$this->add_control( 'icon_color_h', array( 'label' => 'Logo colour', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb-list a:hover, {{WRAPPER}} .og-hb-list span:hover, {{WRAPPER}} .og-hb-list a:focus-visible' => 'color:{{VALUE}} !important' ) ) );
				$this->add_control( 'tile_bg_h', array( 'label' => 'Circle background', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb-list a:hover, {{WRAPPER}} .og-hb-list span:hover, {{WRAPPER}} .og-hb-list a:focus-visible' => 'background:{{VALUE}}' ) ) );
				$this->add_control( 'tile_border_h', array( 'label' => 'Circle border', 'type' => $c::COLOR, 'selectors' => array( '{{WRAPPER}} .og-hb-list a:hover, {{WRAPPER}} .og-hb-list span:hover, {{WRAPPER}} .og-hb-list a:focus-visible' => 'border-color:{{VALUE}}' ) ) );
				$this->add_control( 'hover_lift', array( 'label' => 'Lift on hover', 'type' => $c::SWITCHER, 'default' => 'yes', 'prefix_class' => 'og-hb-lift-' ) );
				$this->end_controls_tab();
				$this->end_controls_tabs();
				$this->end_controls_section();
			}

			protected function render() {
				$s     = $this->get_settings_for_display();
				$fleet = function_exists( 'orego_fleet_makes' ) ? array_map( 'strtolower', array_map( 'remove_accents', orego_fleet_makes() ) ) : array();
				$base  = orego_purl( 'cars' );
				$items = '';
				foreach ( (array) ( $s['brands'] ?? array() ) as $b ) {
					$name = trim( (string) ( $b['name'] ?? '' ) );
					if ( 'upload' === ( $b['logo_source'] ?? '' ) ) {
						$url  = $b['image']['url'] ?? '';
						$logo = $url ? '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $name ) . '" loading="lazy" decoding="async">' : '';
					} else {
						$logo = function_exists( 'orego_brand_svg' ) ? orego_brand_svg( $b['builtin'] ?? '', $name ) : '';
					}
					if ( ! $logo ) {
						continue;
					}
					$href = '';
					$ext  = '';
					if ( 'custom' === ( $b['link_type'] ?? '' ) && ! empty( $b['link']['url'] ) ) {
						$href = $b['link']['url'];
						$ext  = ! empty( $b['link']['is_external'] ) ? ' target="_blank" rel="noopener"' : '';
					} elseif ( 'auto' === ( $b['link_type'] ?? 'auto' ) && $base && ( 'yes' !== ( $s['only_fleet'] ?? 'yes' ) || in_array( strtolower( remove_accents( $name ) ), $fleet, true ) ) ) {
						$href = add_query_arg( 'q', rawurlencode( remove_accents( $name ) ), $base );
					}
					$items .= $href
						? '<li><a href="' . esc_url( $href ) . '" title="' . esc_attr( $name ) . '"' . $ext . '>' . $logo . '</a></li>'
						: '<li><span title="' . esc_attr( $name ) . '">' . $logo . '</span></li>';
				}
				if ( ! $items ) {
					return;
				}
				$dup   = str_replace( '<a href=', '<a tabindex="-1" href=', $items );
				$label = 'yes' === ( $s['show_label'] ?? 'yes' ) && '' !== trim( (string) ( $s['label_text'] ?? '' ) ) ? '<span class="og-hb-label">' . esc_html( $s['label_text'] ) . '</span>' : '';
				echo '<div class="og-hb' . ( $label ? '' : ' no-label' ) . '">' . $label . '<div class="og-hb-view"><div class="og-hb-move"><ul class="og-hb-list notranslate" aria-label="Brands">' . $items . '</ul><ul class="og-hb-list og-hb-dup notranslate" aria-hidden="true">' . $dup . '</ul></div></div></div>'; // phpcs:ignore
			}
		}
	}
	$wm->register( new OreGO_Brands_Widget() );
} );

/* Small extras for the widget switches. */
add_action( 'wp_head', function () {
	echo '<style id="og-hb-widget">.og-hb-blur-.elementor-widget-orego_brands .og-hb{-webkit-backdrop-filter:none;backdrop-filter:none}.og-hb-lift-.elementor-widget-orego_brands .og-hb-list a:hover,.og-hb-lift-.elementor-widget-orego_brands .og-hb-list span:hover{transform:none}.elementor-widget-orego_brands .og-hb-list img{display:block;width:23px;height:23px;object-fit:contain}.elementor-widget-orego_brands .og-hb.no-label{gap:0}</style>';
}, 30 );
