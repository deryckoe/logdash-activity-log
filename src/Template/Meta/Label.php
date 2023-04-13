<?php

namespace LogDash\Template\Meta;

class Label implements LabelInterface {
	protected string $value;

	public function __construct( $value ) {
		$this->value = $value ?? '';
	}

	public function get(): string {
		return '<b>' . $this->value . '</b>';
	}

	public function __toString() {
		return $this->get();
	}
}