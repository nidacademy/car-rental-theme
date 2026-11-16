<?php
/**
 * Keep prices exactly as written ($55, €50, 506 MAD) in every language.
 * GTranslate uses Google's page translator, which rewrote "506 MAD" as "506 درهم مغربي"
 * and the lone "MAD" label as the Arabic word for "crazy". This marks every price
 * "notranslate" before the translator runs, including prices that update live.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_footer', function () {
	// Every currency symbol and code from Customize -> Currencies.
	$toks  = array();
	$words = array();
	foreach ( function_exists( 'orego_currency_list' ) ? orego_currency_list() : array() as $c ) {
		$toks[] = $c['symbol'];
		$toks[] = $c['code'];
		$words[] = $c['code'];
		if ( preg_match( '/^[A-Za-z]{2,}$/', $c['symbol'] ) ) {
			$words[] = $c['symbol'];
		}
	}
	if ( ! $toks ) {
		$toks  = array( '$', '€', 'MAD' );
		$words = array( 'MAD' );
	}
	$toks = array_values( array_unique( $toks ) );
	usort( $toks, fn( $a, $b ) => mb_strlen( $b ) - mb_strlen( $a ) ); // longest first: CA$ before $
	$words = array_values( array_unique( $words ) );
	?>
<script id="orego-money-notranslate">
(function(){
	var T=<?php echo wp_json_encode( $toks ); ?>,W=<?php echo wp_json_encode( $words ); ?>;
	function q(s){return s.replace(/[.*+?^${}()|[\]\\\/]/g,'\\$&');}
	var A=T.map(q).join('|');
	var RE=new RegExp('-?(?:(?:'+A+')\\s?\\d[\\d,]*(?:\\.\\d+)?|\\d[\\d,]*(?:\\.\\d+)?\\s?(?:'+A+')(?![A-Za-z]))'+(W.length?'|\\b(?:'+W.map(q).join('|')+')\\b':''),'g');
	var SKIP={SCRIPT:1,STYLE:1,TEXTAREA:1,INPUT:1,SELECT:1,OPTION:1,NOSCRIPT:1};
	function mark(el){el.classList.add('notranslate');el.setAttribute('translate','no');}
	function guarded(n){for(var p=n.parentNode;p&&p.nodeType===1;p=p.parentNode){if(SKIP[p.tagName]||(p.classList&&p.classList.contains('notranslate')))return true;}return false;}
	function fix(t){
		var s=t.nodeValue;if(!s||s.search(RE)<0||guarded(t))return;
		var p=t.parentNode;
		// The whole element is just a price: mark the element itself.
		if(p.childNodes.length===1&&s.replace(RE,'').replace(/[\s×≈+\/\-–·]/g,'')===''){mark(p);return;}
		var f=document.createDocumentFragment(),last=0;RE.lastIndex=0;var m;
		while((m=RE.exec(s))){if(m.index>last)f.appendChild(document.createTextNode(s.slice(last,m.index)));var sp=document.createElement('span');mark(sp);sp.textContent=m[0];f.appendChild(sp);last=m.index+m[0].length;}
		if(last<s.length)f.appendChild(document.createTextNode(s.slice(last)));
		p.replaceChild(f,t);
	}
	function scan(root){
		if(!root)return;
		if(root.nodeType===3){fix(root);return;}
		if(root.nodeType!==1||SKIP[root.tagName])return;
		var w=document.createTreeWalker(root,NodeFilter.SHOW_TEXT,null),a=[],n;while((n=w.nextNode()))a.push(n);a.forEach(fix);
	}
	scan(document.body);
	document.querySelectorAll('.og-money,.og-cur-sym').forEach(mark);
	new MutationObserver(function(list){list.forEach(function(r){if(r.type==='characterData')fix(r.target);else r.addedNodes.forEach(scan);});}).observe(document.body,{childList:true,subtree:true,characterData:true});
})();
</script>
	<?php
}, 1 );
