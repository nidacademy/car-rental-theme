<?php
/**
 * Exotic Cars admin language: "Your Cars" follows the WordPress dashboard language of the signed-in user.
 * English and Arabic. The public website and customer emails stay in the site language.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function orego_user_ar() {
	// The booking system can have its own language (admin bar: Bookings), otherwise it follows WordPress.
	$pref = function_exists( 'orego_admin_lang_pref' ) ? orego_admin_lang_pref() : 'auto';
	if ( 'auto' !== $pref ) {
		return 'ar' === $pref;
	}
	return function_exists( 'get_user_locale' ) && 0 === strpos( (string) get_user_locale(), 'ar' );
}

function orego_L( $en, $ar ) {
	return orego_user_ar() ? $ar : $en;
}

function orego_ar_n( $n, $one, $two, $few, $many ) {
	$n = (int) $n;
	if ( 1 === $n ) {
		return $one;
	}
	if ( 2 === $n ) {
		return $two;
	}
	return $n . ' ' . ( $n >= 3 && $n <= 10 ? $few : $many );
}

/* ------------------------------------------------------------------ */
/* Labels WordPress shows for cars, car types and bookings             */
/* ------------------------------------------------------------------ */

add_filter( 'post_type_labels_orego_car', function ( $l ) {
	$set = orego_user_ar() ? array(
		'name' => 'سياراتك', 'singular_name' => 'سيارة', 'menu_name' => 'سياراتك', 'name_admin_bar' => 'سيارة', 'all_items' => 'قائمة السيارات', 'add_new' => 'إضافة سيارة', 'add_new_item' => 'إضافة سيارة جديدة', 'edit_item' => 'تعديل السيارة', 'new_item' => 'سيارة جديدة', 'view_item' => 'عرض السيارة', 'view_items' => 'عرض السيارات', 'search_items' => 'ابحث في السيارات', 'not_found' => 'لا توجد سيارات', 'not_found_in_trash' => 'لا توجد سيارات في السلة', 'featured_image' => 'صورة السيارة', 'set_featured_image' => 'تعيين صورة السيارة', 'remove_featured_image' => 'إزالة صورة السيارة', 'use_featured_image' => 'استخدام كصورة للسيارة', 'item_published' => 'تم نشر السيارة.', 'item_updated' => 'تم تحديث السيارة.', 'item_reverted_to_draft' => 'أصبحت السيارة مسودة.'
	) : array(
		'name' => 'Your Cars', 'singular_name' => 'Car', 'menu_name' => 'Your Cars', 'name_admin_bar' => 'Car', 'all_items' => 'Listing', 'add_new' => 'Add Car', 'add_new_item' => 'Add New Car', 'edit_item' => 'Edit Car', 'new_item' => 'New Car', 'view_item' => 'View Car', 'view_items' => 'View Cars', 'search_items' => 'Search Cars', 'not_found' => 'No cars found', 'not_found_in_trash' => 'No cars in trash', 'featured_image' => 'Car photo', 'set_featured_image' => 'Set car photo', 'remove_featured_image' => 'Remove car photo', 'use_featured_image' => 'Use as car photo', 'item_published' => 'Car published.', 'item_updated' => 'Car updated.', 'item_reverted_to_draft' => 'Car switched to draft.'
	);
	foreach ( $set as $k => $v ) {
		$l->$k = $v;
	}
	return $l;
} );

add_filter( 'post_type_labels_orego_booking', function ( $l ) {
	$set = orego_user_ar()
		? array( 'name' => 'الحجوزات', 'singular_name' => 'حجز', 'menu_name' => 'الحجوزات', 'all_items' => 'الحجوزات', 'edit_item' => 'تفاصيل الحجز', 'search_items' => 'ابحث في الحجوزات', 'not_found' => 'لا توجد حجوزات', 'not_found_in_trash' => 'لا توجد حجوزات في السلة' )
		: array( 'name' => 'Bookings', 'singular_name' => 'Booking', 'menu_name' => 'Bookings', 'all_items' => 'Bookings', 'edit_item' => 'Booking details', 'search_items' => 'Search bookings', 'not_found' => 'No bookings found', 'not_found_in_trash' => 'No bookings in trash' );
	foreach ( $set as $k => $v ) {
		$l->$k = $v;
	}
	return $l;
} );

add_filter( 'taxonomy_labels_car_type', function ( $l ) {
	$set = orego_user_ar()
		? array( 'name' => 'فئات السيارات', 'singular_name' => 'الفئة', 'menu_name' => 'فئات السيارات', 'all_items' => 'كل الفئات', 'add_new_item' => 'إضافة فئة جديدة', 'edit_item' => 'تعديل الفئة', 'update_item' => 'تحديث الفئة', 'new_item_name' => 'اسم الفئة الجديدة', 'search_items' => 'ابحث في الفئات', 'not_found' => 'لا توجد فئات', 'parent_item' => 'الفئة الأم', 'back_to_items' => '→ العودة إلى الفئات' )
		: array( 'name' => 'Car Types', 'singular_name' => 'Car Type', 'menu_name' => 'Car Types', 'all_items' => 'All Car Types', 'add_new_item' => 'Add New Car Type', 'edit_item' => 'Edit Car Type', 'update_item' => 'Update Car Type', 'new_item_name' => 'New Car Type Name', 'search_items' => 'Search Car Types', 'not_found' => 'No car types found', 'parent_item' => 'Parent Car Type', 'back_to_items' => '← Back to Car Types' );
	foreach ( $set as $k => $v ) {
		$l->$k = $v;
	}
	return $l;
} );

add_filter( 'post_updated_messages', function ( $m ) {
	$p    = get_post();
	$view = $p ? ' <a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( orego_L( 'View car', 'عرض السيارة' ) ) . '</a>' : '';
	$prev = $p ? ' <a target="_blank" href="' . esc_url( get_preview_post_link( $p ) ) . '">' . esc_html( orego_L( 'Preview car', 'معاينة السيارة' ) ) . '</a>' : '';
	$m['orego_car'] = array(
		0  => '',
		1  => orego_L( 'Car updated.', 'تم تحديث السيارة.' ) . $view,
		4  => orego_L( 'Car updated.', 'تم تحديث السيارة.' ),
		6  => orego_L( 'Car published. It now shows on the website.', 'تم نشر السيارة. أصبحت تظهر على الموقع.' ) . $view,
		7  => orego_L( 'Car saved.', 'تم حفظ السيارة.' ),
		8  => orego_L( 'Car submitted.', 'تم إرسال السيارة.' ) . $prev,
		9  => orego_L( 'Car scheduled.', 'تم جدولة السيارة.' ) . $prev,
		10 => orego_L( 'Car draft updated.', 'تم تحديث مسودة السيارة.' ) . $prev,
	);
	return $m;
} );

/* ------------------------------------------------------------------ */
/* Translators                                                         */
/* ------------------------------------------------------------------ */

/** English -> Arabic for the Your Cars pages (Dashboard, Listing, Bookings, Locations). */
function orego_i18n_en2ar() {
	static $d = null;
	if ( null !== $d ) {
		return $d;
	}
	$d = array(
		'Dashboard' => 'لوحة التحكم', 'Fleet Calendar' => 'تقويم الأسطول', 'Statistics' => 'الإحصائيات', 'Listing' => 'قائمة السيارات', 'Bookings' => 'الحجوزات', 'Pickup Locations' => 'مواقع الاستلام',
		'Your fleet and bookings at a glance' => 'أسطولك وحجوزاتك في لمحة', 'Every car and booking across the coming weeks' => 'كل سيارة وكل حجز خلال الأسابيع القادمة', 'How your rentals perform over the last 6 months' => 'أداء تأجيراتك خلال آخر 6 أشهر',
		'Export' => 'تصدير', 'Bookings (CSV)' => 'الحجوزات (CSV)', 'Cars (CSV)' => 'السيارات (CSV)', 'Export CSV' => 'تصدير CSV',
		'Pending requests' => 'طلبات بانتظار الموافقة', 'Waiting for your approval' => 'بانتظار موافقتك', 'All caught up' => 'لا شيء بانتظارك', 'On rental now' => 'مؤجرة الآن', 'Out with customers today' => 'مع العملاء اليوم', 'Revenue this month' => 'إيرادات هذا الشهر', 'Fleet booked, next 30 days' => 'نسبة الحجز، الـ 30 يومًا القادمة',
		'Location' => 'الموقع', 'All locations' => 'كل المواقع', 'Pick Up Date' => 'تاريخ الاستلام', 'Drop off Date' => 'تاريخ الإرجاع', 'Car Type' => 'الفئة', 'All types' => 'كل الفئات', 'Show' => 'العرض', 'All cars' => 'كل السيارات', 'Published only' => 'المنشورة فقط', 'Find available cars' => 'ابحث عن سيارات متاحة',
		'Available Cars' => 'السيارات المتاحة', 'Reset filters' => 'إعادة ضبط الفلاتر', 'No cars yet' => 'لا توجد سيارات بعد', 'Add your first car' => 'أضف سيارتك الأولى', 'Draft' => 'مسودة', 'On rental' => 'مؤجرة', 'Available' => 'متاحة', 'Booked' => 'محجوزة', 'Free for these dates' => 'متاحة في هذه التواريخ', 'Edit car' => 'تعديل السيارة', 'View on site' => 'عرض على الموقع', 'Type:' => 'الفئة:', 'Gear:' => 'الناقل:', 'Fuel:' => 'الوقود:', 'No price' => 'بدون سعر', '/day' => '/ يوم', 'No upcoming bookings' => 'لا حجوزات قادمة', 'No cars match' => 'لا توجد سيارات مطابقة', 'Try other dates or clear the filters.' => 'جرّب تواريخ أخرى أو امسح الفلاتر.',
		'Recent Activity' => 'آخر النشاطات', 'All bookings' => 'كل الحجوزات', 'No bookings yet' => 'لا توجد حجوزات بعد', 'New requests from the website appear here the moment they arrive.' => 'تظهر الطلبات الجديدة من الموقع هنا فور وصولها.', 'Pick up' => 'الاستلام', 'Return' => 'الإرجاع', 'Duration' => 'المدة', 'Daily rate' => 'السعر اليومي', 'Total' => 'الإجمالي', 'Payment' => 'الدفع', 'At pickup' => 'عند الاستلام', 'Phone' => 'الهاتف', 'Requested' => 'تاريخ الطلب', 'Approve' => 'موافقة', 'Decline' => 'رفض', 'Open booking' => 'فتح الحجز', 'Call' => 'اتصال', 'Email' => 'بريد', 'WhatsApp' => 'واتساب',
		'Pending' => 'بانتظار الموافقة', 'Confirmed' => 'مؤكد', 'Declined' => 'مرفوض', 'Cancelled' => 'ملغى', 'Completed' => 'مكتمل', 'On road' => 'في الطريق', 'Mark as on road' => 'تحديد كفي الطريق', 'Mark on road' => 'تحديد كفي الطريق', 'On the road' => 'في الطريق', 'Returned' => 'تم الإرجاع', 'Car returned' => 'تم إرجاع السيارة', 'Mark as returned' => 'تحديد كمُرجَعة', 'Mark returned' => 'تحديد كمُرجَعة', 'Confirmed & returned' => 'المؤكدة والمُرجَعة', 'Automatic' => 'أوتوماتيك', 'Manual' => 'يدوي', 'Petrol' => 'بنزين', 'Hybrid' => 'هجين', 'Diesel' => 'ديزل', 'Electric' => 'كهرباء',
		'Closed manually' => 'مغلقة يدويًا', 'Closed' => 'مغلقة', 'Today' => 'اليوم', 'Previous 2 weeks' => 'الأسبوعان السابقان', 'Next 2 weeks' => 'الأسبوعان التاليان', 'Car' => 'السيارة',
		'Revenue, all time' => 'إجمالي الإيرادات', 'Confirmed & completed' => 'المؤكدة والمكتملة', 'Average booking' => 'متوسط الحجز', 'Approval rate' => 'نسبة الموافقة', 'Of requests you decided' => 'من الطلبات التي بتّ فيها', 'All requests' => 'كل الطلبات', 'Revenue by month' => 'الإيرادات حسب الشهر', 'Confirmed & completed bookings, by pickup month' => 'الحجوزات المؤكدة والمكتملة حسب شهر الاستلام', 'Top cars' => 'السيارات الأعلى أداءً', 'Ranked by revenue' => 'مرتبة حسب الإيرادات', 'No confirmed bookings yet' => 'لا توجد حجوزات مؤكدة بعد', 'Approve requests and your best performers show up here.' => 'وافق على الطلبات وستظهر هنا سياراتك الأفضل أداءً.', 'Requests by status' => 'الطلبات حسب الحالة', 'All booking requests received' => 'كل طلبات الحجز المستلمة',
		'Add Car' => 'إضافة سيارة', 'All' => 'الكل', 'Published' => 'المنشورة', 'Drafts' => 'المسودات', 'Trash' => 'السلة', 'Status' => 'الحالة', 'Search cars' => 'ابحث في السيارات', 'Search cars by name…' => 'ابحث عن سيارة بالاسم…', 'Car type' => 'الفئة', 'Sort' => 'الترتيب', 'Newest first' => 'الأحدث أولًا', 'Name A–Z' => 'الاسم أ–ي', 'Price: low to high' => 'السعر: من الأقل للأعلى', 'Price: high to low' => 'السعر: من الأعلى للأقل', 'Top rated' => 'الأعلى تقييمًا', 'View' => 'العرض', 'Table view' => 'عرض الجدول', 'Grid view' => 'عرض البطاقات', 'selected' => 'محددة', 'Bulk action' => 'إجراء جماعي', 'Restore' => 'استعادة', 'Delete permanently' => 'حذف نهائي', 'Publish' => 'نشر', 'Switch to draft' => 'تحويل إلى مسودة', 'Move to trash' => 'نقل إلى السلة', 'Apply' => 'تطبيق', 'Clear' => 'مسح', 'No cars match your search' => 'لا توجد سيارات مطابقة لبحثك', 'Trash is empty' => 'السلة فارغة', 'Try another name or clear the filters.' => 'جرّب اسمًا آخر أو امسح الفلاتر.', 'Add your first car and it appears here and on the website.' => 'أضف سيارتك الأولى وستظهر هنا وعلى الموقع.', 'Clear filters' => 'مسح الفلاتر', 'Select all' => 'تحديد الكل', 'Price' => 'السعر', 'Specs' => 'المواصفات', 'Rating' => 'التقييم', 'Actions' => 'الإجراءات', 'Live' => 'منشورة', 'Pending review' => 'بانتظار المراجعة', 'Scheduled' => 'مجدولة', 'Private' => 'خاصة', 'In trash' => 'في السلة', 'Edit' => 'تعديل', 'View on website' => 'عرض على الموقع', 'Preview' => 'معاينة', 'Duplicate' => 'نسخ', 'Unpublish (hide from website)' => 'إلغاء النشر (إخفاء من الموقع)', 'Unpublish' => 'إلغاء النشر', 'Publish to website' => 'نشر على الموقع', 'No type' => 'بدون فئة', '(no name)' => '(بدون اسم)', 'None' => 'لا يوجد', 'No bookings' => 'لا حجوزات', 'Pages' => 'الصفحات',
		'Waiting for you' => 'بانتظارك', 'Upcoming confirmed' => 'مؤكدة قادمة', 'Booked revenue' => 'إيرادات محجوزة', 'Search bookings' => 'ابحث في الحجوزات', 'Name, email, phone or booking #…' => 'الاسم أو البريد أو الهاتف أو رقم الحجز…', 'Any time' => 'أي وقت', 'Upcoming' => 'القادمة', 'Past' => 'السابقة', 'Pickup this month' => 'استلام هذا الشهر', 'Period' => 'الفترة', 'Newest requests' => 'الأحدث أولًا', 'Pickup date' => 'تاريخ الاستلام', 'Highest total' => 'الأعلى قيمة', 'Approve (emails customers)' => 'موافقة (يُرسل بريد للعملاء)', 'Decline (emails customers)' => 'رفض (يُرسل بريد للعملاء)', 'Mark completed' => 'تحديد كمكتمل', 'Cancel (emails customers)' => 'إلغاء (يُرسل بريد للعملاء)', 'No bookings match' => 'لا توجد حجوزات مطابقة', 'No bookings here yet' => 'لا توجد حجوزات هنا بعد', 'Try another search or clear the filters.' => 'جرّب بحثًا آخر أو امسح الفلاتر.', 'Requests from the “احجز رحلتك” page land here, waiting for your approval.' => 'تصل هنا الطلبات من صفحة «احجز رحلتك» بانتظار موافقتك.', 'Booking' => 'الحجز', 'Customer' => 'العميل', 'Trip' => 'الرحلة', 'Approve (emails the customer)' => 'موافقة (يُرسل بريد للعميل)', 'Decline (emails the customer)' => 'رفض (يُرسل بريد للعميل)', 'Mark as completed' => 'تحديد كمكتمل', 'Cancel (emails the customer)' => 'إلغاء (يُرسل بريد للعميل)', 'Cancel booking' => 'إلغاء الحجز',
		'Delete this booking permanently?' => 'حذف هذا الحجز نهائيًا؟', 'Decline this request? The customer will be emailed.' => 'رفض هذا الطلب؟ سيُرسل بريد للعميل.', 'Cancel this confirmed booking? The customer will be emailed.' => 'إلغاء هذا الحجز المؤكد؟ سيُرسل بريد للعميل.', 'Delete this car permanently? This cannot be undone.' => 'حذف هذه السيارة نهائيًا؟ لا يمكن التراجع عن ذلك.',
		'Booking not found' => 'الحجز غير موجود', 'It may have been deleted.' => 'ربما تم حذفه.', 'Back to bookings' => 'العودة إلى الحجوزات', 'Delivery' => 'التوصيل', 'Free' => 'مجاني', 'Total, paid at pickup' => 'الإجمالي، يُدفع عند الاستلام', 'Other bookings by this customer' => 'حجوزات أخرى لهذا العميل', 'Restore it to manage this booking again.' => 'استعده لإدارة هذا الحجز من جديد.', 'Delete' => 'حذف', 'This request is waiting for your answer.' => 'هذا الطلب بانتظار ردّك.', 'Approve booking' => 'الموافقة على الحجز', 'Confirmed. Mark it completed once the car is back.' => 'مؤكد. حدّده كمكتمل بعد إرجاع السيارة.', 'Status & message' => 'الحالة والرسالة', 'Message to the customer (optional)' => 'رسالة للعميل (اختيارية)', 'Added to the confirmation or decline email' => 'تُضاف إلى بريد التأكيد أو الرفض', 'Email the customer when the status changes' => 'إرسال بريد للعميل عند تغيير الحالة', 'Save changes' => 'حفظ التغييرات', 'At pickup, cash or card' => 'عند الاستلام، نقدًا أو بالبطاقة', 'Car page' => 'صفحة السيارة', 'Open' => 'فتح', 'payment is collected at pickup' => 'الدفع عند الاستلام',
		'Where customers can collect and return cars. Shown on the “Book your trip” booking page.' => 'الأماكن التي يستلم ويُرجع فيها العملاء السيارات. تظهر في صفحة «احجز رحلتك».', 'Locations' => 'المواقع', 'The first one is suggested first. Use the arrows to change the order.' => 'يُقترح الموقع الأول أولًا. استخدم الأسهم لتغيير الترتيب.', 'Add location' => 'إضافة موقع', 'Location name' => 'اسم الموقع', 'Move up' => 'تحريك للأعلى', 'Move down' => 'تحريك للأسفل', 'Remove location' => 'حذف الموقع', 'New' => 'جديد', 'Renaming a location doesn’t change bookings already made with the old name.' => 'تغيير اسم الموقع لا يغيّر الحجوزات السابقة التي تحمل الاسم القديم.', 'Save locations' => 'حفظ المواقع',
		'Copy created as a draft.' => 'تم إنشاء نسخة كمسودة.', 'Saved.' => 'تم الحفظ.', 'Nothing selected.' => 'لم يتم تحديد أي عنصر.',
	);
	return $d;
}

/** Patterns for text built from numbers and dates (English -> Arabic). */
function orego_i18n_en2ar_dynamic( $s ) {
	$days = fn( $n ) => orego_ar_n( $n, 'يوم واحد', 'يومان', 'أيام', 'يومًا' );
	$cars = fn( $n ) => orego_ar_n( $n, 'سيارة واحدة', 'سيارتان', 'سيارات', 'سيارة' );
	$bk   = fn( $n ) => orego_ar_n( $n, 'حجز واحد', 'حجزان', 'حجوزات', 'حجزًا' );
	$rules = array(
		'/^(\d+) cars? in your fleet$/'                  => fn( $m ) => $cars( $m[1] ) . ' في أسطولك',
		'/^(\d+) live on the website$/'                  => fn( $m ) => $m[1] . ' منشورة على الموقع',
		'/^(\d+) published cars?$/'                      => fn( $m ) => $cars( $m[1] ) . ' منشورة',
		'/^(\d+) requests? received$/'                   => fn( $m ) => orego_ar_n( $m[1], 'طلب واحد', 'طلبان', 'طلبات', 'طلبًا' ) . ' مستلم',
		'/^(\d+) cars?$/'                                => fn( $m ) => $cars( $m[1] ),
		'/^(\d+) days?$/'                                => fn( $m ) => $days( $m[1] ),
		'/^(\d+) day rental$/'                           => fn( $m ) => 'تأجير ' . $days( $m[1] ),
		'/^(\d+) days? × (.+)$/u'                        => fn( $m ) => $days( $m[1] ) . ' × ' . $m[2],
		'/^(\d+(?:\.\d+)?) days on average$/'           => fn( $m ) => $m[1] . ' يوم في المتوسط',
		'/^(\d+) upcoming$/'                             => fn( $m ) => $m[1] . ' قادمة',
		'/^(\d+) bookings?$/'                            => fn( $m ) => $bk( $m[1] ),
		'/^(\d+) seats$/'                                => fn( $m ) => orego_ar_n( $m[1], 'مقعد واحد', 'مقعدان', 'مقاعد', 'مقعدًا' ),
		'/^(\d+) waiting for you$/'                      => fn( $m ) => $m[1] . ' بانتظارك',
		'/^Next booking (.+)$/u'                          => fn( $m ) => 'الحجز القادم ' . $m[1],
		'/^Booking #(\d+)$/'                             => fn( $m ) => 'الحجز #' . $m[1],
		'/^Select booking (\d+)$/'                       => fn( $m ) => 'تحديد الحجز ' . $m[1],
		'/^Requested (.+) ago$/u'                         => fn( $m ) => 'طُلب منذ ' . $m[1],
		'/^(.+) ago$/u'                                   => fn( $m ) => 'منذ ' . $m[1],
		'/^(.+) at (\d{1,2}:\d{2})$/u'                   => fn( $m ) => $m[1] . ' الساعة ' . $m[2],
		'/^Confirmed & (?:completed|returned), (.+)$/u'                => fn( $m ) => 'المؤكدة والمكتملة، ' . $m[1],
		'/^(\d+) moved to trash\.$/'                     => fn( $m ) => 'نُقل ' . $m[1] . ' إلى السلة.',
		'/^(\d+) restored\.$/'                           => fn( $m ) => 'تمت استعادة ' . $m[1] . '.',
		'/^(\d+) permanently deleted\.$/'                => fn( $m ) => 'حُذف ' . $m[1] . ' نهائيًا.',
		'/^(\d+) published\.$/'                          => fn( $m ) => 'نُشر ' . $m[1] . '.',
		'/^(\d+) switched to draft\.$/'                  => fn( $m ) => 'تحوّل ' . $m[1] . ' إلى مسودة.',
		'/^(\d+) approved\. The customer was emailed\.$/' => fn( $m ) => 'تمت الموافقة على ' . $m[1] . '. أُرسل بريد للعميل.',
		'/^(\d+) declined\. The customer was emailed\.$/' => fn( $m ) => 'رُفض ' . $m[1] . '. أُرسل بريد للعميل.',
		'/^(\d+) cancelled\. The customer was emailed\.$/' => fn( $m ) => 'أُلغي ' . $m[1] . '. أُرسل بريد للعميل.',
		'/^(\d+) marked as (?:completed|returned)\.$/'                => fn( $m ) => 'اكتمل ' . $m[1] . '.',
		'/^(\d+) set back to pending\.$/'                => fn( $m ) => 'أُعيد ' . $m[1] . ' إلى الانتظار.',
		'/^e\.g\. (.+)$/u'                               => fn( $m ) => 'مثال: ' . $m[1],
		'/^Edit (.+)$/u'                                  => fn( $m ) => 'تعديل ' . $m[1],
		'/^View (.+) on the site$/u'                      => fn( $m ) => 'عرض ' . $m[1] . ' على الموقع',
		'/^Select (.+)$/u'                                => fn( $m ) => 'تحديد ' . $m[1],
		'/^Call (.+)$/u'                                  => fn( $m ) => 'اتصال بـ ' . $m[1],
		'/^Email (.+)$/u'                                 => fn( $m ) => 'مراسلة ' . $m[1],
	);
	foreach ( $rules as $re => $fn ) {
		if ( preg_match( $re, $s, $m ) ) {
			return $fn( $m );
		}
	}
	return null;
}

/** Arabic -> English for the car editor boxes (written in Arabic). */
function orego_i18n_ar2en() {
	return array(
		'السعر اليومي ($) *' => 'Daily price ($) *', 'سنة الصنع' => 'Year', 'عدد المقاعد' => 'Seats', 'ناقل الحركة' => 'Transmission', 'الوقود' => 'Fuel', 'التقييم (0 إلى 5)' => 'Rating (0 to 5)', 'اتركه فارغًا إن لم يوجد' => 'Leave empty if none', 'الموقع' => 'Location',
		'أوتوماتيك' => 'Automatic', 'يدوي' => 'Manual', 'بنزين' => 'Petrol', 'هجين' => 'Hybrid', 'ديزل' => 'Diesel', 'كهرباء' => 'Electric',
		'اكتب اسم السيارة في العنوان (مثل: 2026 Toyota Corolla)، واختر الفئة من مربع «الفئات»، وأضف الصورة من «صورة السيارة». تظهر السيارة في الموقع بمجرد النشر.' => 'Write the car name in the title (e.g. 2026 Toyota Corolla), pick its type in the “Car Types” box and set its photo under “Car photo”. The car appears on the website as soon as it is published.',
		'الصورة الرئيسية تأتي من «صورة السيارة». أضف هنا باقي الصور لتظهر في معرض صفحة السيارة.' => 'The main photo comes from “Car photo”. Add the other photos here to show them in the car page gallery.', 'اختيار الصور' => 'Choose photos', 'إزالة الكل' => 'Remove all', 'صور السيارة' => 'Car photos', 'استخدام الصور' => 'Use photos',
		'الأمان' => 'Safety', 'الاتصال' => 'Connectivity', 'مميزات إضافية' => 'More features', 'كاميرا خلفية' => 'Backup camera', 'تنبيه النقطة العمياء' => 'Blind spot warning', 'مساعد الكبح' => 'Brake assist', 'تنبيه مغادرة المسار' => 'Lane departure warning', 'مثبت سرعة تكيّفي' => 'Adaptive cruise control', 'مساعد البقاء في المسار' => 'Lane keeping assist', 'بلوتوث' => 'Bluetooth', 'منفذ USB' => 'USB input', 'مدخل AUX' => 'Auxiliary input', 'نظام ملاحة GPS' => 'GPS navigation', 'دخول بدون مفتاح' => 'Keyless entry', 'شاحن USB' => 'USB charger', 'مقاعد مدفأة' => 'Heated seats', 'فتحة سقف' => 'Sunroof', 'صف ثالث من المقاعد' => 'Third-row seating',
		'الأميال المشمولة يوميًا' => 'Miles included per day', 'عدد التقييمات' => 'Number of reviews', 'يظهر بجانب التقييم، مثل: 4.9 (124 تقييمًا)' => 'Shown next to the rating, e.g. 4.9 (124 reviews)', 'أيام مغلقة يدويًا (صيانة أو حجز خارجي)' => 'Manually closed days (maintenance or outside bookings)',
		'الحجوزات من الموقع تُغلق الأيام تلقائيًا. استخدم هذا الحقل فقط لإغلاق أيام أخرى، سطر لكل فترة: 2026-10-16 to 2026-10-17 (يوم الإرجاع يبقى متاحًا للاستلام).' => 'Website bookings close days automatically. Use this only to close other days, one period per line: 2026-10-16 to 2026-10-17 (the return day stays available for pickup).',
	);
}

/** JavaScript string literals inside the pages. */
function orego_i18n_js_en2ar() {
	return array(
		"'Booked'" => "'محجوزة'", "'Free for these dates'" => "'متاحة في هذه التواريخ'", "' of '" => "' من '", "' cars free for these dates'" => "' متاحة في هذه التواريخ'", "' car'" => "' سيارة'", "' cars'" => "' سيارات'",
		"'Draft'" => "'مسودة'", "'Available'" => "'متاحة'",
		"'Delete the selected items permanently? This cannot be undone.'" => "'حذف العناصر المحددة نهائيًا؟ لا يمكن التراجع عن ذلك.'", "'The selected customers will be emailed. Continue?'" => "'سيُرسل بريد للعملاء المحددين. هل تريد المتابعة؟'", "'Keep at least one location.'" => "'احتفظ بموقع واحد على الأقل.'",
	);
}

/** Translate visible text and labels in an HTML fragment; scripts get literal swaps; styles untouched. */
function orego_i18n_html( $html, $dir ) {
	$dict = 'ar' === $dir ? orego_i18n_en2ar() : orego_i18n_ar2en();
	$js   = 'ar' === $dir ? orego_i18n_js_en2ar() : array( "'صور السيارة'" => "'Car photos'", "'استخدام الصور'" => "'Use photos'" );
	$tr   = function ( $text ) use ( &$tr, $dict, $dir ) {
		$raw = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( isset( $dict[ $raw ] ) ) {
			return esc_html( $dict[ $raw ] );
		}
		if ( 'ar' === $dir && false !== strpos( $raw, ' · ' ) ) {
			$parts = array_map( function ( $p ) use ( $tr ) {
				return html_entity_decode( $tr( esc_html( $p ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			}, explode( ' · ', $raw ) );
			return esc_html( implode( ' · ', $parts ) );
		}
		if ( 'ar' === $dir ) {
			$d = orego_i18n_en2ar_dynamic( $raw );
			if ( null !== $d ) {
				return esc_html( $d );
			}
		}
		return $text;
	};
	$parts = preg_split( '#(<script\b.*?</script>|<style\b.*?</style>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $i => $part ) {
		if ( preg_match( '#^<script\b#i', $part ) ) {
			if ( false === stripos( $part, 'application/json' ) ) {
				$parts[ $i ] = strtr( $part, $js );
			}
			continue;
		}
		if ( preg_match( '#^<style\b#i', $part ) ) {
			continue;
		}
		$part = preg_replace_callback( '/>([^<>]+)</u', function ( $m ) use ( $tr ) {
			$t = trim( $m[1] );
			if ( '' === $t || is_numeric( str_replace( array( '$', ',', '%', '#', '.' ), '', $t ) ) ) {
				return $m[0];
			}
			$n = $tr( $t );
			return $n === $t ? $m[0] : '>' . str_replace( $t, $n, $m[1] ) . '<';
		}, $part );
		$part = preg_replace_callback( '/\b(title|aria-label|placeholder|data-confirm|data-default)="([^"]*)"/u', function ( $m ) use ( $tr ) {
			$n = $tr( $m[2] );
			return $m[1] . '="' . ( $n === $m[2] ? $m[2] : esc_attr( html_entity_decode( $n, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) . '"';
		}, $part );
		$parts[ $i ] = $part;
	}
	return implode( '', $parts );
}

/* ------------------------------------------------------------------ */
/* Apply                                                               */
/* ------------------------------------------------------------------ */

/* Your Cars pages: written in English, shown in Arabic for Arabic dashboards */
add_action( 'admin_menu', function () {
	foreach ( array( 'orego-dashboard', 'orego-listing', 'orego-bookings', 'orego-places' ) as $slug ) {
		add_action( 'load-orego_car_page_' . $slug, function () {
			if ( ! orego_user_ar() ) {
				return;
			}
			ob_start( function ( $html ) {
				$a = strpos( $html, '<div class="ogdx' );
				$b = strpos( $html, '<!-- wpbody-content -->' );
				if ( false === $a || false === $b || $b <= $a ) {
					return $html;
				}
				return substr( $html, 0, $a ) . orego_i18n_html( substr( $html, $a, $b - $a ), 'ar' ) . substr( $html, $b );
			} );
		} );
	}
}, 1001 );

/* Car editor boxes: written in Arabic, shown in English for English dashboards */
add_action( 'add_meta_boxes_orego_car', function () {
	global $wp_meta_boxes;
	$titles = array(
		'orego_car_details'  => array( 'Car details', 'تفاصيل السيارة' ),
		'orego_car_gallery'  => array( 'Extra photos', 'صور إضافية للسيارة' ),
		'orego_car_features' => array( 'Vehicle features', 'مميزات السيارة' ),
		'orego_car_trips'    => array( 'Distance, reviews & closed days', 'المسافة والتقييمات والأيام المغلقة' ),
	);
	foreach ( (array) ( $wp_meta_boxes['orego_car'] ?? array() ) as $ctx => $prios ) {
		foreach ( $prios as $prio => $boxes ) {
			foreach ( $boxes as $id => $box ) {
				if ( ! $box || ! isset( $titles[ $id ] ) ) {
					continue;
				}
				$orig = $box['callback'];
				$wp_meta_boxes['orego_car'][ $ctx ][ $prio ][ $id ]['title']    = orego_L( $titles[ $id ][0], $titles[ $id ][1] ); // phpcs:ignore
				$wp_meta_boxes['orego_car'][ $ctx ][ $prio ][ $id ]['callback'] = function ( $post, $b ) use ( $orig ) { // phpcs:ignore
					if ( orego_user_ar() ) {
						call_user_func( $orig, $post, $b );
						return;
					}
					ob_start();
					call_user_func( $orig, $post, $b );
					echo orego_i18n_html( ob_get_clean(), 'en' ); // phpcs:ignore
				};
			}
		}
	}
}, 99 );
