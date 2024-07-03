<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpMissingFieldTypeInspection */
/** @noinspection PhpMultipleClassDeclarationsInspection */


namespace LogDash\Hooks;

use LogDash\API\EventMeta;
use LogDash\EventCodes;
use LogDash\EventTypes;
use LogDash\Template\Meta\After;
use LogDash\Template\Meta\Before;
use LogDash\Template\Meta\Label;
use LogDash\Template\Meta\Specification;
use LogDash\Template\Meta\View;
use \WP_Post;

class LearnDash extends HooksBase {

	private static string $object_type = 'user';

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'learndash_course_completed', [ $this, 'course_completed' ] );
		add_filter( 'logdash_manage_columns-' . self::$object_type . '-content_event_meta', [
			$this,
			'event_meta_info'
		], 10, 3 );
	}


	public function course_completed( $args ) {

		/** @var WP_User $user */
		$user = $args['user'];
		/** @var WP_Post $course */
		$course           = $args['course'];
		$course_completed = $args['course_completed'];

		$this->event->insert(
			EventTypes::COMPLETED,
			EventCodes::COMPLETED,
			'user',
			$course->post_type,
			$course->ID,
			$user->ID,
			$user->roles[0],
		)->attachMany( [
			new EventMeta( 'postTitle', $course->post_title ),
			new EventMeta( 'postType', $course->post_type ),
			new EventMeta( 'courseCompleted', $course_completed ),
		] );
	}


	public function event_meta_info( $output, $event_data, $meta_data ) {

		$message = EventCodes::desc( $event_data['event_code'] );
		$details = [];

		if ( $event_data['object_subtype'] !== 'sfwd-courses' ) {
			return $output;
		}

		switch ( $event_data['event_code'] ) {
			case EventCodes::DEFAULT:
				$data    = [
					new Label( ucfirst( $meta_data['postType'] ) ),
					new Label( $meta_data['postTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Completed', LOGDASH_DOMAIN ), $meta_data['courseCompleted'] ),
				];
				break;
			default:
				$data = [];
		}

		if ( empty( $data ) ) {
			return $output;
		}

		$details = array_merge( $details, [
			new Specification( __( 'User Agent', LOGDASH_DOMAIN ), $event_data['user_agent'] ),
		] );

		$view = new View();
		$view
			->message( $message, $data )
			->actions( [] )
			->details( $details );

		return $view->get();

	}

}