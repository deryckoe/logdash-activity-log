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

	private static string $object_type = 'course';
	private static string $object_label;

	public function init() {

		self::$object_label = __('Curso', LOGDASH_DOMAIN );
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

		$meta = [
			new EventMeta( 'postTitle', $course->post_title ),
			new EventMeta( 'postType', $course->post_type ),
			new EventMeta( 'courseCompleted', $course_completed ),
		];

		if ( ! empty( $user->ID ) ) {
			$user_quizzes = get_user_meta( $user->ID, "_sfwd-quizzes", true );

			$highest_score        = 0;
			$highest_score_record = null;

			if ( is_array( $user_quizzes ) ) {
				foreach ( $user_quizzes as $quiz ) {
					if ( $quiz["course"] == $course->ID ) {
						if ( $quiz["points"] > $highest_score ) {
							$highest_score        = $quiz["points"];
							$highest_score_record = $quiz;
						}
					}
				}
			}

			if ( ! is_null( $highest_score_record ) ) {
				// totalPoints are the max points a user can have
				$quiz_meta = [
					new EventMeta( 'quizId', $highest_score_record["quiz"] ),
					new EventMeta( 'quizPoints', $highest_score_record["points"] ),
					new EventMeta( 'quizTotalPoints', $highest_score_record["total_points"] ),
					new EventMeta( 'quizPercentage', $highest_score_record["percentage"] ),
				];

				$meta = array_merge( $meta, $quiz_meta );
			}

		}

		$this->event->insert(
			EventTypes::COMPLETED,
			EventCodes::COMPLETED,
			self::$object_type,
			$course->post_type,
			$course->ID,
			$user->ID,
			$user->roles[0],
		)->attachMany( $meta );
	}


	public function event_meta_info( $output, $event_data, $meta_data ) {

		if ( $event_data['object_subtype'] !== 'sfwd-courses' ) {
			return $output;
		}

		$message = EventCodes::desc( $event_data['event_code'] );
		$details = [];

		switch ( $event_data['event_code'] ) {
			case EventCodes::COMPLETED:
				$data    = [
					new Label( $meta_data['postTitle'] ),
				];

				$post_type = get_post_type_object( $meta_data['postType']  );
				if ( ! empty( $post_type->labels ) ) {
					$singular_name = $post_type->labels->singular_name;
					$post_type = " $singular_name ($post_type->name)";
				}

				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $post_type ),
					new Specification( __( 'Completed', LOGDASH_DOMAIN ), date_i18n( 'd/m/Y H:i', $meta_data['courseCompleted'] ) ),

				];

				if ( ! empty( $meta_data['quizPercentage'] ) ) {
					$score_text = __( "%d%% (%d points of %d).", LOGDASH_DOMAIN );
					$score      = sprintf( $score_text, $meta_data['quizPercentage'], $meta_data['quizPoints'], $meta_data['quizTotalPoints'] );
					$details = array_merge( $details, [
						new Specification( __( 'Evaluation Score', LOGDASH_DOMAIN ), $score ),
					] );
				}

				break;
			default:
				$data = [];
		}

		if ( empty( $data ) ) {
			return $output;
		}

		$actions = [
			[
				'href'   => get_edit_post_link( $event_data['object_id'] ),
				'target' => '_self',
				'label'  => __( 'Edit Course', LOGDASH_DOMAIN )
			]
		];

		if ( ! empty( $meta_data['quizId'] ) ) {
			$actions[] = [
				'href'   => get_edit_post_link( $meta_data['quizId'] ),
				'target' => '_self',
				'label'  => __( 'Edit Quiz', LOGDASH_DOMAIN )
			];
		}

		$details = array_merge( $details, [
			new Specification( __( 'User Agent', LOGDASH_DOMAIN ), $event_data['user_agent'] ),
		] );

		$view = new View();
		$view
			->message( $message, $data )
			->actions( $actions )
			->details( $details );

		return $view->get();

	}

}