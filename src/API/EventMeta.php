<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace LogDash\API;

class EventMeta {

	public string $name;
	public $value;

	public function __construct( string $name, $value = null ) {
		$this->name = $name;

		if ( ! is_array( $value ) && ! is_object( $value ) && ! is_string( $value ) && ! is_null( $value ) ) {
			trigger_error('Value [' . $this->name .  ']: ' . gettype( $value ) .' type in EventMeta is not supported', E_USER_WARNING );
		}

		$this->value = $value;
	}

}