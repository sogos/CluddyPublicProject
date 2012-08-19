<?php

namespace api\route;

class routeManager {

	private $urls = array(
			'goog' => 'http://www.google.com',
			'fb' => 'http://www.facebook.com',
			);

	public function get($short){
		return $this->urls[$short];
	}

}

?>
