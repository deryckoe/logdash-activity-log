<?php

namespace LogDash\Template\Meta;

interface LabelInterface {

	public function __construct($value);

	public function get() : string;

}