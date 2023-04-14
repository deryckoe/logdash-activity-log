<?php

namespace LogDash\Template\Meta;

class View {

	private string $message;
	private array $details;
	private array $actions;

	public function get(): string {
		$output = '<div class="event_meta_container">';
		$output .= '<h2 class="event_title">' . $this->message . '</h2>';

		$output .= '<div class="event_actions">';

		if ( $this->details ) {
			$output .= '<span class="details">
						<svg class="icon" width="16" height="17" viewBox="0 0 16 17" fill="none">
							<path d="M8 14.8596C6.27609 14.8596 4.62279 14.1747 3.40381 12.9558C2.18482 11.7368 1.5 10.0835 1.5 8.35956C1.5 6.63565 2.18482 4.98235 3.40381 3.76336C4.62279 2.54438 6.27609 1.85956 8 1.85956C9.72391 1.85956 11.3772 2.54438 12.5962 3.76336C13.8152 4.98235 14.5 6.63565 14.5 8.35956C14.5 10.0835 13.8152 11.7368 12.5962 12.9558C11.3772 14.1747 9.72391 14.8596 8 14.8596ZM8 0.359558C5.87827 0.359558 3.84344 1.20241 2.34315 2.7027C0.842855 4.203 0 6.23783 0 8.35956C0 10.4813 0.842855 12.5161 2.34315 14.0164C3.84344 15.5167 5.87827 16.3596 8 16.3596C10.1217 16.3596 12.1566 15.5167 13.6569 14.0164C15.1571 12.5161 16 10.4813 16 8.35956C16 6.23783 15.1571 4.203 13.6569 2.7027C12.1566 1.20241 10.1217 0.359558 8 0.359558ZM4.21875 7.89081L7.46875 11.1408C7.7625 11.4346 8.2375 11.4346 8.52812 11.1408L11.7813 7.89081C12.075 7.59706 12.075 7.12206 11.7813 6.83143C11.4875 6.54081 11.0125 6.53768 10.7219 6.83143L8.00313 9.55018L5.28438 6.83143C4.99063 6.53768 4.51562 6.53768 4.225 6.83143C3.93438 7.12518 3.93125 7.60018 4.225 7.89081H4.21875Z" fill="#C3C4C7"/>
						</svg>
						<i class="more">' . __( 'More', LOGDASH_DOMAIN ) . '</i><i class="less">' . __( 'Less', LOGDASH_DOMAIN ) . '</i>
					</span>';
		}

		$output .= implode( '', $this->create_actions() );

		$output .= '</div>';

		if ( $this->details ) {

			$lines = array_map( function ( SpecificationInterface $detail ) {
				return '<div class="event_line"><span class="event_line_key">' . $detail->getKey() . '</span><span class="event_line_value">' . $detail->getValue() . '</span></div>';
			}, $this->details );

			$output .= '<div class="event_details">';
			$output .= '<div class="event_lines">';
			$output .= implode( '', $lines );
			$output .= '</div>';
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}

	public function message( $message, $values ): View {

		$replacements = [];

		if ( $values ) {
			$replacements = array_map( function ( LabelInterface $value ) {
				return $value->get();
			}, $values );
		}

		$this->message = sprintf( $message, ...$replacements );

		return $this;
	}

	public function details( array $details ): View {
		$this->details = $details;

		return $this;
	}

	public function actions( array $actions ): View {
		$this->actions = $actions;

		return $this;
	}

	private function create_actions(): array {

		return array_map( function ( $action ) {

			$target = $action['target'] ?? '_self';

			return '<a href="' . $action['href'] . '" target="' . $target . '" class="action" >' . $action['label'] . '</a>';

		}, $this->actions );


	}

}