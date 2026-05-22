<?php

namespace EphenyxDigital\QuantumCore;

/**
 * Stub class - WordPress "Events Manager" plugin integration is irrelevant in Phenyx.
 * Original 5.5 KB implementation removed. All methods return safe defaults.
 */
class RevSliderEventsManager {

	public static function isEventsExists() {
		return false;
	}

	public static function get_event_post_data($post_id) {
		return [];
	}

	public static function getArrSortBy() {
		return [];
	}

}
