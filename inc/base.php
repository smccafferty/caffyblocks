<?php
// include the data structures
require_once __DIR__ . '/data-structures/foundation.php';
require_once __DIR__ . '/data-structures/building.php';
require_once __DIR__ . '/data-structures/room.php';
require_once __DIR__ . '/data-structures/accessory.php';

// include admin specific functionality
if ( is_admin() ) {
	require_once __DIR__ . '/admin/admin.php';
}