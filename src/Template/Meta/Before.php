<?php

namespace LogDash\Template\Meta;

class Before extends Label {
	public function get(): string {
		$class = [ 'before' ];

		if ( empty( $this->value ) ) {
			$this->value = __( 'undefined', LOGDASH_DOMAIN );
			$class[]     = 'is_null';
		}

		if ( strlen( $this->value ) > 30 ) {
			$this->value = substr( $this->value, 0, 30 ) . '<span class="more">' . $this->value . '</span>';
			$class[]     = 'has_more';
		}

		return '<span class="' . implode( ' ', $class ) . '">' . $this->value . '</span>';
	}
}