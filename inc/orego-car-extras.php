<?php
/**
 * Exotic Cars car extras: your own features and details.
 * - Features box: add an option to any group, or a whole new group, right from the car page.
 *   New options are saved once and can be ticked on every car (option "orego_feature_extra").
 * - "More details" box: free label / value rows per car (meta "og_specs"), shown on the car page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_feature_extra() {
	$x = get_option( 'orego_feature_extra' );
	return is_array( $x ) ? $x : array();
}

add_filter( 'orego_feature_groups', function ( $groups ) {
	foreach ( orego_feature_extra() as $gk => $x ) {
		$items = isset( $x['items'] ) && is_array( $x['items'] ) ? $x['items'] : array();
		if ( isset( $groups[ $gk ] ) ) {
			$groups[ $gk ][2] = $groups[ $gk ][2] + $items;
		} elseif ( ! empty( $x['name'] ) ) {
			$groups[ $gk ] = array( $x['name'], 'check', $items );
		}
	}
	return $groups;
} );

function orego_car_specs( $id ) {
	$s = json_decode( (string) get_post_meta( $id, 'og_specs', true ), true );
	return is_array( $s ) ? array_values( array_filter( $s, fn( $r ) => is_array( $r ) && '' !== ( $r[0] ?? '' ) && '' !== ( $r[1] ?? '' ) ) ) : array();
}

/* ------------------------------------------------------------------ */
/* Boxes                                                               */
/* ------------------------------------------------------------------ */

add_action( 'add_meta_boxes_orego_car', function () {
	remove_meta_box( 'orego_car_features', 'orego_car', 'normal' );
	add_meta_box( 'orego_car_features', 'Features', 'orego_car_features_box2', 'orego_car', 'normal', 'default' );
	// "More details" now sits at the bottom of the Car details box (orego-cars.php).
}, 20 );

function orego_car_features_box2( $post ) {
	wp_nonce_field( 'orego_car_feat2', 'orego_car_feat2_nonce' );
	$have  = (array) get_post_meta( $post->ID, 'og_features', true );
	$extra = orego_feature_extra();
	echo '<p class="ogf-help">Tick what this car has. Missing something? Add your own option to a group, or create a new group. New options can be used on every car.</p>';
	echo '<div class="ogf-groups" id="ogf-groups">';
	foreach ( orego_feature_groups() as $gk => $g ) {
		$custom = isset( $extra[ $gk ]['items'] ) ? $extra[ $gk ]['items'] : array();
		echo '<fieldset class="ogf-g" data-g="' . esc_attr( $gk ) . '"><legend><span>' . esc_html( $g[0] ) . '</span>' . ( ! empty( $extra[ $gk ]['name'] ) ? '<button type="button" class="ogf-gdel" aria-label="Delete this group" title="Delete this group and its options"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>Delete</button>' : '' ) . '</legend><div class="ogf-items">';
		foreach ( $g[2] as $k => $label ) {
			echo '<label class="ogf-it"><input type="checkbox" name="og_features[]" value="' . esc_attr( $k ) . '"' . checked( in_array( $k, $have, true ), true, false ) . '><span>' . esc_html( $label ) . '</span>'
				. ( isset( $custom[ $k ] ) ? '<button type="button" class="ogf-rm" data-k="' . esc_attr( $gk . '|' . $k ) . '" aria-label="Remove this option" title="Remove this option from all cars">&times;</button>' : '' )
				. '</label>';
		}
		echo '</div><div class="ogf-add"><input type="text" maxlength="60" placeholder="Add an option…" aria-label="New option"><button type="button" class="button">Add</button></div></fieldset>';
	}
	echo '</div>';
	echo '<div class="ogf-newg"><input type="text" id="ogf-gname" maxlength="40" placeholder="New group, e.g. Comfort" aria-label="New group name"><button type="button" class="button" id="ogf-gadd">+ Add group</button></div>';
	echo '<div id="ogf-hidden"></div>';
	?>
	<style>
	.ogf-help{color:#646970;margin:4px 0 14px}
	.ogf-groups{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
	.ogf-g{border:1px solid #E5E7EB;border-radius:12px;padding:12px 14px 14px;margin:0;background:#fff;min-width:0}
	.ogf-g legend{font-weight:700;padding:0 6px;font-size:13px;display:flex;align-items:center;gap:8px}
	.ogf-gdel{display:inline-flex;align-items:center;gap:4px;border:1px solid #F3C4C4;background:#fff;color:#B91C1C;font-size:11.5px;font-weight:600;line-height:1;padding:4px 8px;border-radius:999px;cursor:pointer}
	.ogf-gdel:hover{background:#FEE2E2;border-color:#FCA5A5}
	.ogf-gdel:focus-visible{outline:2px solid #B91C1C;outline-offset:2px}
	.ogf-items{display:grid;gap:2px}
	.ogf-it{display:flex;align-items:center;gap:8px;padding:5px 6px;border-radius:8px;cursor:pointer}
	.ogf-it:hover{background:#F6F7F9}
	.ogf-it span{flex:1}
	.ogf-it.is-new span:after{content:"new";margin-inline-start:6px;font-size:10px;font-weight:700;color:#15803D;background:#ECFDF3;border-radius:999px;padding:1px 6px;vertical-align:1px}
	.ogf-rm{border:0;background:none;color:#9CA3AF;font-size:16px;line-height:1;cursor:pointer;padding:2px 4px;border-radius:6px}
	.ogf-rm:hover{color:#B91C1C;background:#FEE2E2}
	.ogf-add,.ogf-newg{display:flex;gap:6px;margin-top:10px}
	.ogf-add input,.ogf-newg input{flex:1;min-width:0}
	.ogf-newg{margin-top:14px;max-width:420px}
	</style>
	<script>
	(function(){
	var root=document.getElementById('ogf-groups'),hid=document.getElementById('ogf-hidden'),n=0;if(!root)return;
	function addItem(fs,label){
	 label=label.trim();if(!label)return;
	 var dup=[].some.call(fs.querySelectorAll('.ogf-it span'),function(s){if(s.textContent.trim().toLowerCase()===label.toLowerCase()){s.parentNode.querySelector('input').checked=true;return true}});
	 if(dup)return;
	 var l=document.createElement('label');l.className='ogf-it is-new';
	 var c=document.createElement('input');c.type='checkbox';c.checked=true;c.name='og_feat_new['+fs.getAttribute('data-g')+'][]';c.value=label;
	 var s=document.createElement('span');s.textContent=label;
	 var x=document.createElement('button');x.type='button';x.className='ogf-rm';x.setAttribute('aria-label','Remove');x.textContent='×';
	 l.appendChild(c);l.appendChild(s);l.appendChild(x);fs.querySelector('.ogf-items').appendChild(l)}
	root.addEventListener('click',function(e){
	 var b=e.target.closest('.ogf-add button');
	 if(b){var i=b.previousElementSibling;addItem(b.closest('.ogf-g'),i.value);i.value='';i.focus();return}
	 var gd=e.target.closest('.ogf-gdel');
	 if(gd){e.preventDefault();var g=gd.closest('.ogf-g'),nm=g.querySelector('legend span').textContent,isNew=!!g.querySelector('input[name^="og_group_new"]');
	  if(!isNew){if(!confirm('Delete the group “'+nm+'” and its options from every car? Click Update to save.'))return;var hg=document.createElement('input');hg.type='hidden';hg.name='og_group_del[]';hg.value=g.getAttribute('data-g');hid.appendChild(hg)}
	  g.remove();return}
	 var r=e.target.closest('.ogf-rm');
	 if(r){e.preventDefault();var it=r.closest('.ogf-it');
	  if(r.getAttribute('data-k')){if(!confirm('Remove “'+it.querySelector('span').textContent+'” from the list? It will disappear from every car.'))return;var h=document.createElement('input');h.type='hidden';h.name='og_feat_del[]';h.value=r.getAttribute('data-k');hid.appendChild(h)}
	  it.remove()}});
	root.addEventListener('keydown',function(e){if(e.key==='Enter'&&e.target.closest('.ogf-add input')){e.preventDefault();e.target.nextElementSibling.click()}});
	var gn=document.getElementById('ogf-gname');
	function addGroup(){var name=gn.value.trim();if(!name)return;var k='new'+(++n);
	 var fs=document.createElement('fieldset');fs.className='ogf-g';fs.setAttribute('data-g',k);
	 var lg=document.createElement('legend');var ls=document.createElement('span');ls.textContent=name;lg.appendChild(ls);lg.insertAdjacentHTML('beforeend','<button type="button" class="ogf-gdel" aria-label="Delete this group" title="Delete this group and its options"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>Delete</button>');fs.appendChild(lg);
	 var h=document.createElement('input');h.type='hidden';h.name='og_group_new['+k+']';h.value=name;fs.appendChild(h);
	 var it=document.createElement('div');it.className='ogf-items';fs.appendChild(it);
	 var ad=document.createElement('div');ad.className='ogf-add';var i=document.createElement('input');i.type='text';i.maxLength=60;i.placeholder='Add an option…';var bt=document.createElement('button');bt.type='button';bt.className='button';bt.textContent='Add';ad.appendChild(i);ad.appendChild(bt);fs.appendChild(ad);
	 root.appendChild(fs);gn.value='';i.focus()}
	document.getElementById('ogf-gadd').addEventListener('click',addGroup);
	gn.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();addGroup()}});
	})();
	</script>
	<?php
}

function orego_car_specs_box( $post ) {
	wp_nonce_field( 'orego_car_specs', 'orego_car_specs_nonce' );
	$rows   = orego_car_specs( $post->ID );
	$rows[] = array( '', '' );
	$labels = get_option( 'orego_spec_labels' );
	$labels = array_unique( array_merge( is_array( $labels ) ? $labels : array(), array( 'Doors', 'Luggage', 'Engine', 'Colour', 'Air conditioning', 'Fuel consumption', 'Mileage limit', 'Child seat' ) ) );
	$row    = function ( $r ) {
		return '<div class="ogs-row"><input type="text" name="og_spec_l[]" value="' . esc_attr( $r[0] ) . '" maxlength="60" list="ogs-labels" placeholder="Detail, e.g. Doors" aria-label="Detail"><input type="text" name="og_spec_v[]" value="' . esc_attr( $r[1] ) . '" maxlength="120" placeholder="Value, e.g. 4" aria-label="Value"><button type="button" class="ogs-rm" aria-label="Remove">&times;</button></div>';
	};
	echo '<p class="ogf-help" style="color:#646970;margin:4px 0 12px">Add any detail this car has that isn’t in the list above, for example Doors: 4 or Luggage: 2 large bags. They appear on the car page.</p>';
	echo '<div class="ogs-rows" id="ogs-rows">';
	foreach ( $rows as $r ) {
		echo $row( $r ); // phpcs:ignore
	}
	echo '</div><button type="button" class="button" id="ogs-add">+ Add detail</button>';
	echo '<template id="ogs-tpl">' . $row( array( '', '' ) ) . '</template>'; // phpcs:ignore
	echo '<datalist id="ogs-labels">';
	foreach ( $labels as $l ) {
		echo '<option value="' . esc_attr( $l ) . '">';
	}
	echo '</datalist>';
	?>
	<style>
	.ogs-rows{display:grid;gap:8px;margin-bottom:10px;max-width:720px}
	.ogs-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr) 32px;gap:8px;align-items:center}
	.ogs-row input{width:100%;min-height:34px}
	.ogs-rm{border:0;background:none;color:#9CA3AF;font-size:18px;cursor:pointer;border-radius:6px;height:32px}
	.ogs-rm:hover{color:#B91C1C;background:#FEE2E2}
	</style>
	<script>
	(function(){var box=document.getElementById('ogs-rows');if(!box)return;
	document.getElementById('ogs-add').addEventListener('click',function(){box.appendChild(document.getElementById('ogs-tpl').content.cloneNode(true));box.lastElementChild.querySelector('input').focus()});
	box.addEventListener('click',function(e){var b=e.target.closest('.ogs-rm');if(!b)return;var r=b.parentNode;if(box.children.length>1)r.remove();else r.querySelectorAll('input').forEach(function(i){i.value=''})});
	})();
	</script>
	<?php
}

/* ------------------------------------------------------------------ */
/* Save (after the main car save, so the new options are kept)         */
/* ------------------------------------------------------------------ */

add_action( 'save_post_orego_car', function ( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// Features: new options, new groups, removed options.
	if ( isset( $_POST['orego_car_feat2_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['orego_car_feat2_nonce'] ) ), 'orego_car_feat2' ) && current_user_can( 'edit_posts' ) ) {
		$extra   = orego_feature_extra();
		$groups  = orego_feature_groups();
		$have    = array_map( 'sanitize_key', (array) wp_unslash( $_POST['og_features'] ?? array() ) );
		$changed = false;
		// Whole groups you created, deleted with their options.
		foreach ( (array) wp_unslash( $_POST['og_group_del'] ?? array() ) as $gdel ) {
			$gdel = sanitize_key( $gdel );
			if ( ! empty( $extra[ $gdel ]['name'] ) ) {
				$have = array_values( array_diff( $have, array_keys( (array) ( $extra[ $gdel ]['items'] ?? array() ) ) ) );
				unset( $extra[ $gdel ] );
				$changed = true;
			}
		}
		foreach ( (array) wp_unslash( $_POST['og_feat_del'] ?? array() ) as $del ) {
			list( $gk, $k ) = array_pad( explode( '|', sanitize_text_field( $del ), 2 ), 2, '' );
			$gk = sanitize_key( $gk );
			$k  = sanitize_key( $k );
			if ( isset( $extra[ $gk ]['items'][ $k ] ) ) {
				unset( $extra[ $gk ]['items'][ $k ] );
				$have    = array_values( array_diff( $have, array( $k ) ) );
				$changed = true;
				if ( ! $extra[ $gk ]['items'] && empty( $extra[ $gk ]['name'] ) ) {
					unset( $extra[ $gk ] );
				}
			}
		}
		$map = array(); // temporary group key -> real key
		foreach ( (array) wp_unslash( $_POST['og_group_new'] ?? array() ) as $tmp => $name ) {
			$name = mb_substr( sanitize_text_field( $name ), 0, 40 );
			if ( '' === $name || count( $extra ) >= 20 ) {
				continue;
			}
			$gk = 'g_' . substr( sanitize_key( remove_accents( $name ) ), 0, 20 );
			$gk = 'g_' === $gk ? 'g_' . substr( md5( $name ), 0, 6 ) : $gk;
			while ( isset( $groups[ $gk ] ) && empty( $extra[ $gk ]['name'] ) ) {
				$gk .= '_2';
			}
			if ( ! isset( $extra[ $gk ] ) ) {
				$extra[ $gk ] = array( 'name' => $name, 'items' => array() );
			}
			$map[ sanitize_key( $tmp ) ] = $gk;
			$changed                     = true;
		}
		foreach ( (array) wp_unslash( $_POST['og_feat_new'] ?? array() ) as $gk => $labels ) {
			$gk = sanitize_key( $gk );
			$gk = $map[ $gk ] ?? $gk;
			if ( ! isset( $groups[ $gk ] ) && ! isset( $extra[ $gk ] ) ) {
				continue;
			}
			foreach ( (array) $labels as $label ) {
				$label = mb_substr( sanitize_text_field( $label ), 0, 60 );
				if ( '' === $label ) {
					continue;
				}
				$all = ( $groups[ $gk ][2] ?? array() ) + ( $extra[ $gk ]['items'] ?? array() );
				$hit = array_search( mb_strtolower( $label ), array_map( 'mb_strtolower', $all ), true );
				if ( false === $hit ) {
					if ( count( $extra[ $gk ]['items'] ?? array() ) >= 40 ) {
						continue;
					}
					$k = 'c_' . substr( sanitize_key( remove_accents( $label ) ), 0, 30 );
					$k = 'c_' === $k ? 'c_' . substr( md5( $label ), 0, 8 ) : $k;
					while ( isset( $all[ $k ] ) ) {
						$k .= '2';
					}
					$extra[ $gk ]['name']        = $extra[ $gk ]['name'] ?? '';
					$extra[ $gk ]['items'][ $k ] = $label;
					$hit                         = $k;
					$changed                     = true;
				}
				$have[] = $hit;
			}
		}
		if ( $changed ) {
			update_option( 'orego_feature_extra', $extra, false );
		}
		$valid = array();
		foreach ( orego_feature_groups() as $g ) {
			$valid = array_merge( $valid, array_keys( $g[2] ) );
		}
		update_post_meta( $post_id, 'og_features', array_values( array_unique( array_intersect( $have, $valid ) ) ) );
	}
	// More details.
	if ( isset( $_POST['orego_car_specs_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['orego_car_specs_nonce'] ) ), 'orego_car_specs' ) ) {
		$l    = (array) wp_unslash( $_POST['og_spec_l'] ?? array() );
		$v    = (array) wp_unslash( $_POST['og_spec_v'] ?? array() );
		$rows = array();
		foreach ( $l as $i => $label ) {
			$label = mb_substr( sanitize_text_field( $label ), 0, 60 );
			$value = mb_substr( sanitize_text_field( $v[ $i ] ?? '' ), 0, 120 );
			if ( '' !== $label && '' !== $value && count( $rows ) < 30 ) {
				$rows[] = array( $label, $value );
			}
		}
		if ( $rows ) {
			update_post_meta( $post_id, 'og_specs', wp_slash( wp_json_encode( $rows, JSON_UNESCAPED_UNICODE ) ) );
			$known = get_option( 'orego_spec_labels' );
			$known = array_slice( array_values( array_unique( array_merge( is_array( $known ) ? $known : array(), array_column( $rows, 0 ) ) ) ), 0, 100 );
			update_option( 'orego_spec_labels', $known, false );
		} else {
			delete_post_meta( $post_id, 'og_specs' );
		}
	}
}, 30 );

/* ------------------------------------------------------------------ */
/* Car page                                                            */
/* ------------------------------------------------------------------ */

function orego_car_specs_html( $id ) {
	$rows = orego_car_specs( $id );
	if ( ! $rows ) {
		return '';
	}
	$h = '<section class="ogx-sec"><h2>Car details</h2><dl class="ogx-specs">';
	foreach ( $rows as $r ) {
		$h .= '<div><dt>' . esc_html( $r[0] ) . '</dt><dd>' . esc_html( $r[1] ) . '</dd></div>';
	}
	$h .= '</dl></section>';
	$h .= '<style>.ogx-specs{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin:0}.ogx-specs div{border:1px solid #E7E9EE;border-radius:12px;padding:10px 14px;background:#fff}.ogx-specs dt{font-size:12px;color:#6B7280;margin:0}.ogx-specs dd{margin:2px 0 0;font-weight:700;color:#111827}</style>';
	return $h;
}
