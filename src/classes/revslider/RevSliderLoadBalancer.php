<?php

namespace EphenyxDigital\QuantumCore;

/**
 * Stub class - WordPress/ThemePunch CDN load balancer is irrelevant in Phenyx.
 * Original 2 KB implementation removed. All methods return safe defaults.
 */
class RevSliderLoadBalancer {

	public $servers = [];

	public function call_url($url, $args = [], $type = '', $http_force = false) {
		return ['data' => '', 'body' => '', 'response' => ['code' => 0]];
	}

	public function get_url($type = '', $idx = 0, $force = false) {
		return '';
	}

	public function move_server_list() {
		return false;
	}

}
