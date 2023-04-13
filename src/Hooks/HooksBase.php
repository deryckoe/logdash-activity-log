<?php

namespace LogDash\Hooks;

use LogDash\API\Event;

class HooksBase {

	protected Event $event;

	public function __construct() {
		$this->event = new Event();
	}

}