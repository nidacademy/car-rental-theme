<?php
/**
 * Elementor widget "Exotic Cars Listing": the car cards grid, with content and design controlled in Elementor.
 * Renders the same markup as [orego_cars], so the Cars page filters keep working with it.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'elementor/widgets/register', function ( $wm ) {
	if ( ! class_exists( 'OreGO_Cars_Widget' ) ) {
		class OreGO_Cars_Widget extends \Elementor\Widget_Base {

			public function get_name() {
				return 'orego_cars';
			}

			public function get_title() {
				return 'Car Listing';
			}

			public function get_icon() {
				return 'eicon-posts-grid';
			}

			public function get_categories() {
				return array( 'general' );
			}

			public function get_keywords() {
				return array( 'cars', 'fleet', 'grid', 'orego', 'rental' );
			}

			protected function register_controls() {
				$c   = '\Elementor\Controls_Manager';
				$sel = function ( $s ) {
					return '{{WRAPPER}} .og-cars ' . $s;
				};
				$types = array();
				foreach ( get_terms( array( 'taxonomy' => 'car_type', 'hide_empty' => false ) ) as $t ) {
					if ( ! is_wp_error( $t ) ) {
						$types[ $t->slug ] = $t->name;
					}
				}

				/* ---------- Content ---------- */
				$this->start_controls_section( 'sec_query', array( 'label' => 'Cars' ) );
				$this->add_control( 'limit', array( 'label' => 'Number of cars', 'type' => $c::NUMBER, 'min' => 0, 'max' => 60, 'default' => 9, 'description' => '0 = all cars. With Load more on, this many show at first and each click adds this many more.' ) );
				$this->add_control( 'load_more', array( 'label' => 'Load more button', 'type' => $c::SWITCHER, 'default' => 'yes', 'label_on' => 'On', 'label_off' => 'Off', 'description' => 'Shown only when there are more cars than the number above.' ) );
				$this->add_control( 'more_text', array( 'label' => 'Button text', 'type' => $c::TEXT, 'default' => 'Load more cars', 'condition' => array( 'load_more' => 'yes' ) ) );
				$this->add_control( 'orderby', array( 'label' => 'Order', 'type' => $c::SELECT, 'default' => 'date', 'options' => array( 'date' => 'Newest first', 'oldest' => 'Oldest first', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'rating' => 'Best rated', 'title' => 'Name A–Z', 'random' => 'Random' ) ) );
				$this->add_control( 'types', array( 'label' => 'Only these car types', 'type' => $c::SELECT2, 'multiple' => true, 'options' => $types, 'label_block' => true, 'description' => 'Leave empty to show every type.' ) );
				$this->end_controls_section();

				$this->start_controls_section( 'sec_parts', array( 'label' => 'Card content' ) );
				$this->add_control( 'parts_note', array( 'type' => $c::RAW_HTML, 'raw' => 'By default these follow <b>Appearance → Customize → Car Cards</b>. Change one here only to make this block different.', 'content_classes' => 'elementor-descriptor' ) );
				foreach ( array( 'tag' => 'Car type badge', 'fav' => 'Favourite button', 'meta' => 'Rating and year', 'specs' => 'Seats, gearbox, fuel', 'loc' => 'Location', 'perks' => 'Perks line' ) as $k => $l ) {
					$this->add_control( 'show_' . $k, array( 'label' => $l, 'type' => $c::SELECT, 'default' => '', 'options' => array( '' => 'Like Car Cards settings', 'yes' => 'Show', 'no' => 'Hide' ) ) );
				}
				$this->add_control( 'perk1', array( 'label' => 'Perk 1', 'type' => $c::TEXT, 'default' => '', 'placeholder' => 'From Car Cards settings' ) );
				$this->add_control( 'perk2', array( 'label' => 'Perk 2', 'type' => $c::TEXT, 'default' => '', 'placeholder' => 'From Car Cards settings' ) );
				$this->add_control( 'perk3', array( 'label' => 'Perk 3', 'type' => $c::TEXT, 'default' => '', 'placeholder' => 'From Car Cards settings' ) );
				$this->add_control( 'per', array( 'label' => 'After the price', 'type' => $c::TEXT, 'default' => '', 'placeholder' => 'From Car Cards settings', 'separator' => 'before' ) );
				$this->add_control( 'ask', array( 'label' => 'Text when there is no price', 'type' => $c::TEXT, 'default' => '', 'placeholder' => 'Price on request' ) );
				$this->add_control( 'btn', array( 'label' => 'Button text', 'type' => $c::TEXT, 'default' => '', 'placeholder' => 'From Car Cards settings' ) );
				$this->end_controls_section();

				/* ---------- Style: grid ---------- */
				$this->start_controls_section( 'st_grid', array( 'label' => 'Grid', 'tab' => $c::TAB_STYLE ) );
				$this->add_responsive_control( 'columns', array( 'label' => 'Columns', 'type' => $c::SELECT, 'default' => '', 'options' => array( '' => 'Default', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5' ), 'selectors' => array( '{{WRAPPER}} .og-cars:not(.og-cars-none)' => 'grid-template-columns:repeat({{VALUE}},minmax(0,1fr))' ) ) );
				$this->add_responsive_control( 'gap', array( 'label' => 'Space between cards', 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'selectors' => array( '{{WRAPPER}} .og-cars' => 'gap:{{SIZE}}{{UNIT}}' ) ) );
				$this->end_controls_section();

				/* ---------- Style: card ---------- */
				$this->start_controls_section( 'st_card', array( 'label' => 'Card', 'tab' => $c::TAB_STYLE ) );
				$this->add_control( 'card_bg', array( 'label' => 'Background', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-v' ) => 'background:{{VALUE}}' ) ) );
				$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array( 'name' => 'card_border', 'selector' => $sel( '.og-v' ) ) );
				$this->add_responsive_control( 'card_radius', array( 'label' => 'Corner radius', 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'selectors' => array( $sel( '.og-v' ) => 'border-radius:{{SIZE}}{{UNIT}}' ) ) );
				$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array( 'name' => 'card_shadow', 'selector' => $sel( '.og-v' ) ) );
				$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array( 'name' => 'card_shadow_h', 'label' => 'Shadow on hover', 'selector' => $sel( '.og-v:hover' ) ) );
				$this->add_control( 'card_lift', array( 'label' => 'Lift on hover', 'type' => $c::SWITCHER, 'default' => 'yes', 'prefix_class' => 'og-cw-lift-' ) );
				$this->add_responsive_control( 'body_pad', array( 'label' => 'Text padding', 'type' => $c::DIMENSIONS, 'size_units' => array( 'px' ), 'selectors' => array( $sel( '.og-body' ) => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}' ) ) );
				$this->add_responsive_control( 'body_gap', array( 'label' => 'Space between lines', 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 30 ) ), 'selectors' => array( $sel( '.og-body' ) => 'gap:{{SIZE}}{{UNIT}}' ) ) );
				$this->end_controls_section();

				/* ---------- Style: image ---------- */
				$this->start_controls_section( 'st_img', array( 'label' => 'Image', 'tab' => $c::TAB_STYLE ) );
				$this->add_control( 'img_ratio', array( 'label' => 'Shape', 'type' => $c::SELECT, 'default' => '', 'options' => array( '' => 'Default (4:3)', '1/1' => 'Square', '3/2' => '3:2', '16/9' => 'Wide 16:9', '21/9' => 'Extra wide' ), 'selectors' => array( $sel( '.og-media' ) => 'aspect-ratio:{{VALUE}}' ) ) );
				$this->add_control( 'img_fit', array( 'label' => 'Fit', 'type' => $c::SELECT, 'default' => '', 'options' => array( '' => 'Fill (crop)', 'contain' => 'Show whole car' ), 'selectors' => array( $sel( '.og-media img' ) => 'object-fit:{{VALUE}}' ) ) );
				$this->add_control( 'img_bg', array( 'label' => 'Background behind the photo', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-media' ) => 'background:{{VALUE}}' ) ) );
				$this->add_control( 'img_zoom', array( 'label' => 'Zoom photo on hover', 'type' => $c::SWITCHER, 'default' => '', 'prefix_class' => 'og-cw-zoom-' ) );
				$this->end_controls_section();

				/* ---------- Style: badges ---------- */
				$this->start_controls_section( 'st_badge', array( 'label' => 'Badge & favourite', 'tab' => $c::TAB_STYLE ) );
				$this->add_control( 'tag_bg', array( 'label' => 'Badge background', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-tag' ) => 'background:{{VALUE}}' ) ) );
				$this->add_control( 'tag_color', array( 'label' => 'Badge text', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-tag' ) => 'color:{{VALUE}}' ) ) );
				$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'tag_typo', 'label' => 'Badge font', 'selector' => $sel( '.og-tag' ) ) );
				$this->add_control( 'fav_bg', array( 'label' => 'Heart background', 'type' => $c::COLOR, 'separator' => 'before', 'selectors' => array( $sel( '.og-fav' ) => 'background:{{VALUE}}' ) ) );
				$this->add_control( 'fav_color', array( 'label' => 'Heart colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-fav' ) => 'color:{{VALUE}}' ) ) );
				$this->add_control( 'fav_on', array( 'label' => 'Heart colour when saved', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-fav[aria-pressed=true]' ) => 'color:{{VALUE}}', $sel( '.og-fav[aria-pressed=true] svg' ) => 'fill:{{VALUE}}' ) ) );
				$this->end_controls_section();

				/* ---------- Style: texts ---------- */
				$this->start_controls_section( 'st_title', array( 'label' => 'Car name', 'tab' => $c::TAB_STYLE ) );
				$this->add_control( 'title_color', array( 'label' => 'Colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-name' ) => 'color:{{VALUE}}' ) ) );
				$this->add_control( 'title_color_h', array( 'label' => 'Hover colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-name a:hover' ) => 'color:{{VALUE}}' ) ) );
				$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'title_typo', 'selector' => $sel( '.og-name' ) ) );
				$this->end_controls_section();

				$this->start_controls_section( 'st_info', array( 'label' => 'Details', 'tab' => $c::TAB_STYLE ) );
				$this->add_control( 'info_color', array( 'label' => 'Text colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-meta' ) => 'color:{{VALUE}}', $sel( '.og-specs' ) => 'color:{{VALUE}}', $sel( '.og-loc' ) => 'color:{{VALUE}}' ) ) );
				$this->add_control( 'info_icon', array( 'label' => 'Icon colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-specs svg' ) => 'color:{{VALUE}}', $sel( '.og-loc svg' ) => 'color:{{VALUE}}' ) ) );
				$this->add_control( 'star_color', array( 'label' => 'Star colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-star' ) => 'color:{{VALUE}}' ) ) );
				$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'info_typo', 'selector' => $sel( '.og-meta' ) . ', ' . $sel( '.og-specs' ) . ', ' . $sel( '.og-loc' ) ) );
				$this->add_control( 'perk_color', array( 'label' => 'Perks text', 'type' => $c::COLOR, 'separator' => 'before', 'selectors' => array( $sel( '.og-perks' ) => 'color:{{VALUE}}' ) ) );
				$this->add_control( 'perk_icon', array( 'label' => 'Perks icon', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-perks svg' ) => 'color:{{VALUE}}' ) ) );
				$this->add_control( 'line_color', array( 'label' => 'Line above the price', 'type' => $c::COLOR, 'separator' => 'before', 'selectors' => array( $sel( '.og-foot' ) => 'border-top-color:{{VALUE}}' ) ) );
				$this->end_controls_section();

				$this->start_controls_section( 'st_price', array( 'label' => 'Price', 'tab' => $c::TAB_STYLE ) );
				$this->add_control( 'price_color', array( 'label' => 'Colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-price' ) => 'color:{{VALUE}}' ) ) );
				$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'price_typo', 'selector' => $sel( '.og-price' ) ) );
				$this->add_control( 'per_color', array( 'label' => '"/ day" colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-price small' ) => 'color:{{VALUE}}' ) ) );
				$this->end_controls_section();

				/* ---------- Style: button ---------- */
				$this->start_controls_section( 'st_btn', array( 'label' => 'Button', 'tab' => $c::TAB_STYLE ) );
				$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'btn_typo', 'selector' => $sel( '.og-more' ) ) );
				$this->start_controls_tabs( 'btn_tabs' );
				$this->start_controls_tab( 'btn_n', array( 'label' => 'Normal' ) );
				$this->add_control( 'btn_bg', array( 'label' => 'Background', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-more' ) => 'background:{{VALUE}}' ) ) );
				$this->add_control( 'btn_color', array( 'label' => 'Text colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-more' ) => 'color:{{VALUE}}' ) ) );
				$this->end_controls_tab();
				$this->start_controls_tab( 'btn_h', array( 'label' => 'Hover' ) );
				$this->add_control( 'btn_bg_h', array( 'label' => 'Background', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-more:hover' ) => 'background:{{VALUE}}' ) ) );
				$this->add_control( 'btn_color_h', array( 'label' => 'Text colour', 'type' => $c::COLOR, 'selectors' => array( $sel( '.og-more:hover' ) => 'color:{{VALUE}}' ) ) );
				$this->end_controls_tab();
				$this->end_controls_tabs();
				$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array( 'name' => 'btn_border', 'selector' => $sel( '.og-more' ), 'separator' => 'before' ) );
				$this->add_responsive_control( 'btn_radius', array( 'label' => 'Corner radius', 'type' => $c::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'selectors' => array( $sel( '.og-more' ) => 'border-radius:{{SIZE}}{{UNIT}}' ) ) );
				$this->add_responsive_control( 'btn_pad', array( 'label' => 'Padding', 'type' => $c::DIMENSIONS, 'size_units' => array( 'px' ), 'selectors' => array( $sel( '.og-more' ) => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}' ) ) );
				$this->add_control( 'btn_icon', array( 'label' => 'Show arrow', 'type' => $c::SWITCHER, 'default' => 'yes', 'prefix_class' => 'og-cw-arrow-' ) );
				$this->end_controls_section();
			}

			protected function render() {
				$s    = $this->get_settings_for_display();
				// Start from Appearance -> Customize -> Car Cards, then apply this block's own choices.
				$hide = array_filter( explode( ',', function_exists( 'orego_card_hide' ) ? orego_card_hide() : '' ) );
				foreach ( array( 'tag', 'fav', 'meta', 'specs', 'loc', 'perks' ) as $k ) {
					$v = $s[ 'show_' . $k ] ?? '';
					if ( 'no' === $v ) {
						$hide[] = $k;
					} elseif ( 'yes' === $v ) {
						$hide = array_diff( $hide, array( $k ) );
					}
				}
				$atts = array(
					'limit'   => (int) ( $s['limit'] ?? 9 ),
					'orderby' => (string) ( $s['orderby'] ?? 'date' ),
					'types'   => implode( ',', (array) ( $s['types'] ?? array() ) ),
					'hide'    => implode( ',', array_unique( $hide ) ),
				);
				if ( '' !== trim( (string) ( $s['perk1'] ?? '' ) . ( $s['perk2'] ?? '' ) . ( $s['perk3'] ?? '' ) ) ) {
					$atts['perks'] = implode( '|', array_filter( array( $s['perk1'] ?? '', $s['perk2'] ?? '', $s['perk3'] ?? '' ), fn( $v ) => '' !== trim( (string) $v ) ) );
				}
				foreach ( array( 'btn', 'per', 'ask' ) as $k ) {
					if ( '' !== trim( (string) ( $s[ $k ] ?? '' ) ) ) {
						$atts[ $k ] = (string) $s[ $k ];
					}
				}
				// Load more: fetch every car, show the first $per, reveal $per more on each click.
				$per = (int) $atts['limit'];
				$lm  = 'yes' === ( $s['load_more'] ?? 'yes' ) && $per > 0;
				if ( $lm ) {
					$atts['limit'] = -1;
				}
				global $shortcode_tags;
				if ( isset( $shortcode_tags['orego_cars'] ) && is_callable( $shortcode_tags['orego_cars'] ) ) {
					$html = call_user_func( $shortcode_tags['orego_cars'], $atts, '', 'orego_cars' );
					if ( $lm ) {
						$txt  = trim( (string) ( $s['more_text'] ?? '' ) );
						$html = '<div class="og-lm" data-per="' . esc_attr( $per ) . '" data-more="' . esc_attr( '' !== $txt ? $txt : 'Load more cars' ) . '">' . $html . '</div>';
					}
					echo $html; // phpcs:ignore
				}
			}
		}
	}
	$wm->register( new OreGO_Cars_Widget() );
} );

add_action( 'wp_head', function () {
	echo '<style id="og-cars-widget">.og-cw-lift-.elementor-widget-orego_cars .og-cars .og-v:hover{transform:none}.og-cw-zoom-yes .og-cars .og-media{overflow:hidden}.og-cw-zoom-yes .og-cars .og-media img{transition:transform .5s ease}.og-cw-zoom-yes .og-cars .og-v:hover .og-media img{transform:scale(1.05)}.og-cw-arrow-.elementor-widget-orego_cars .og-more svg{display:none}</style>';
}, 30 );

/* Load more button for the Exotic Cars Listing widget (always loaded: Elementor may serve a saved copy of the widget). */
add_action( 'wp_footer', 'orego_cars_load_more_js', 120 );
function orego_cars_load_more_js() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	?>
<style id="og-lm-css">
.og-lm-off{display:none!important}
.og-lm-wrap{display:flex;flex-direction:column;align-items:center;gap:10px;margin-top:32px;font-family:Cairo,Tahoma,sans-serif}
.og-lm-n{margin:0;font-size:14px;font-weight:600;color:#6B7280}
.og-lm-btn{display:inline-flex;align-items:center;gap:8px;font:700 15px/1.2 Cairo,Tahoma,sans-serif;color:#15171C;background:#FFFFFF;border:1.5px solid #E2E5EA;border-radius:999px;padding:14px 30px;cursor:pointer;box-shadow:0 4px 14px rgba(21,23,28,.06);transition:border-color .15s,box-shadow .15s,transform .1s}
.og-lm-btn:hover{border-color:var(--hx-primary,#15171C);box-shadow:0 8px 22px rgba(21,23,28,.10)}
.og-lm-btn:active{transform:translateY(1px)}
.og-lm-btn:focus-visible{outline:2px solid var(--hx-primary,#15171C);outline-offset:3px}
.og-lm-btn svg{width:16px;height:16px}
</style>
<script id="og-lm-js">
(function(){
	function setup(box){
		if(box.dataset.lmReady)return;box.dataset.lmReady='1';
		var grid=box.querySelector('.og-cars:not(.og-cars-none)');if(!grid)return;
		var per=parseInt(box.dataset.per,10)||9,cards=[].slice.call(grid.querySelectorAll('.og-v')),shown=per;
		if(cards.length<=per)return; // nothing more to load
		var wrap=document.createElement('div');wrap.className='og-lm-wrap';
		wrap.innerHTML='<p class="og-lm-n" aria-live="polite"></p><button type="button" class="og-lm-btn"><span></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>';
		wrap.querySelector('span').textContent=box.dataset.more||'Load more cars';
		grid.parentNode.insertBefore(wrap,grid.nextSibling);
		var info=wrap.querySelector('p');
		function paint(){
			cards.forEach(function(c,i){c.classList.toggle('og-lm-off',i>=shown);});
			if(shown>=cards.length){wrap.remove();return;}
			info.textContent='Showing '+shown+' of '+cards.length+' cars';
		}
		wrap.querySelector('button').addEventListener('click',function(){
			var first=cards[shown];shown+=per;paint();
			var link=first&&first.querySelector('a:not([tabindex="-1"])');if(link)link.focus({preventScroll:true}); // keyboard users land on the first new car
		});
		paint();
	}
	function run(){[].forEach.call(document.querySelectorAll('.og-lm'),setup);}
	if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run);else run();
	window.addEventListener('elementor/frontend/init',function(){if(window.elementorFrontend)elementorFrontend.hooks.addAction('frontend/element_ready/orego_cars.default',function($s){var b=$s[0]&&$s[0].querySelector('.og-lm');if(b)setup(b);});});
})();
</script>
	<?php
}