<?php

namespace LogDash\Template\Meta;

class After extends Label {
	public function get(): string {

		$class = [ 'after' ];

		if ( empty( $this->value ) ) {
			$this->value = __( 'undefined' );
			$class[]     = 'is_null';
		}

		if ( strlen( $this->value) > 30 ) {
			$this->value = substr( $this->value, 0,30 ) . '<span class="more">' . $this->value . '</span>';
			$class[] = 'has_more';
		}

		return '<span class="' . implode( ' ', $class ) . '">' . $this->value . '</span>';
	}
}