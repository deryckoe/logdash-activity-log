<?php

namespace LogDash\Template\Meta;
interface SpecificationInterface {
	public function __construct( $key, $value );

	public function getKey();

	public function getValue();
}