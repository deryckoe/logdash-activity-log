<?php

namespace LogDash\Template\Meta;

class Specification implements SpecificationInterface {
	private string $key;
	private string $value;

	public function __construct( $key, $value ) {
		$this->key =  $key;
		$this->value = $value ?? '';
	}

	public function getKey(): string {
		return $this->key;
	}

	public function getValue(): string {
		return $this->value;
	}
}