<?php

namespace api\language;

class languageUtils {

	public static function getClientLanguage() {
		$langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		return substr($langs[0], 0, 2);
	}

}
