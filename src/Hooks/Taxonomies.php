<?php

namespace LogDash\Hooks;

use LogDash\API\EventMeta;
use LogDash\EventCodes;
use LogDash\EventTypes;
use LogDash\Template\Meta\After;
use LogDash\Template\Meta\Before;
use LogDash\Template\Meta\Label;
use LogDash\Template\Meta\Specification;
use LogDash\Template\Meta\View;

class Taxonomies extends HooksBase {

	private static string $object_type = 'taxonomy';

	/**
	 * @var array|\WP_Error|\WP_Term|null
	 */
	private $prev_term;

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'create_term', [ $this, 'term_created' ], 10, 4 );
		add_action( 'delete_term', [ $this, 'term_deleted' ], 10, 5 );
		add_action( 'edit_term', [ $this, 'before_edit_term' ], 10, 4 );
		add_action( 'edited_term', [ $this, 'term_edited' ], 10, 4 );

		add_filter( 'logdash_manage_columns-tag-content_event_meta', [ $this, 'event_meta_info' ], 10, 3 );
		add_filter( 'logdash_manage_columns-category-content_event_meta', [ $this, 'event_meta_info' ], 10, 3 );
		add_filter( 'logdash_manage_columns-taxonomy-content_event_meta', [ $this, 'event_meta_info' ], 10, 3 );
	}

	private function get_object_type( $taxonomy ): string {
		if ( empty( $taxonomy ) ) {
			return 'taxonomy';
		}

		switch ( $taxonomy ) {
			case 'post_tag':
				return 'tag';
			case 'category':
				return 'category';
			default:
				return 'taxonomy';
		}

	}

	public function term_created( $term_id, $tt_id, $taxonomy, $args ) {
		$term         = get_term( $term_id );
		$current_user = wp_get_current_user();

		switch ( $taxonomy ) {
			case 'post_tag':
				$event_code = EventCodes::TAG_CREATED;
				break;
			case 'category':
				$event_code = EventCodes::CATEGORY_CREATED;
				break;
			default:
				$event_code = EventCodes::TERM_CREATED;
				break;
		}

		$object_type = $this->get_object_type( $taxonomy );

		$this
			->event
			->insert( EventTypes::CREATED, $event_code, $object_type, $object_type, $term_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'termType', $term->taxonomy ),
				new EventMeta( 'termName', $term->name ),
				new EventMeta( 'termSlug', $term->slug ),
			] );
	}

	public function term_deleted( $term_id, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
		$current_user = wp_get_current_user();

		switch ( $taxonomy ) {
			case 'post_tag':
				$event_code = EventCodes::TAG_DELETED;
				break;
			case 'category':
				$event_code = EventCodes::CATEGORY_DELETED;
				break;
			default:
				$event_code = EventCodes::TERM_DELETED;
				break;
		}

		$object_type = $this->get_object_type( $taxonomy );

		$this
			->event
			->insert( EventTypes::DELETED, $event_code, $object_type, $object_type, $term_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'termType', $deleted_term->taxonomy ),
				new EventMeta( 'termName', $deleted_term->name ),
				new EventMeta( 'termSlug', $deleted_term->slug ),
			] );
	}

	public function before_edit_term( $term_id, $tt_id, $taxonomy, $args ) {
		$this->prev_term = get_term( $term_id, $taxonomy );

	}


	public function term_edited( $term_id, $tt_id, $taxonomy, $args ) {
		$term         = get_term( $term_id );
		$current_user = wp_get_current_user();

		switch ( $taxonomy ) {
			case 'post_tag':
				$event_code = EventCodes::TAG_UPDATED;
				break;
			case 'category':
				$event_code = EventCodes::CATEGORY_UPDATED;
				break;
			default:
				$event_code = EventCodes::TERM_UPDATED;
				break;
		}

		$object_type = $this->get_object_type( $taxonomy );

		$this
			->event
			->insert( EventTypes::MODIFIED, $event_code, $object_type, $object_type, $term_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'termType', $term->taxonomy ),
				new EventMeta( 'termOldName', $this->prev_term->name ),
				new EventMeta( 'termNewName', $term->name ),
				new EventMeta( 'termOldSlug', $this->prev_term->slug ),
				new EventMeta( 'termNewSlug', $term->slug ),
			] );
	}

	public function event_meta_info( $output, $event_data, $meta_data ) {

		$message = EventCodes::desc( $event_data['event_code'] );

		$term = get_term( $event_data['object_id'] );

		if ( is_wp_error( $term ) || is_null( $term ) ) {
			$actions = [];
		} else {
			$actions = [
				[
					'href'   => get_edit_term_link( $term->term_id, $term->taxonomy ),
					'target' => '_self',
					'label'  => __( 'Edit ' ),
				],
			];
		}

		switch ( $event_data['event_code'] ) {
			case EventCodes::TERM_UPDATED:
			case EventCodes::TAG_UPDATED:
			case EventCodes::CATEGORY_UPDATED:
				$data    = [
					new Label( $meta_data['termType'] ),
					new Before( $meta_data['termOldName'] ),
					new Before( $meta_data['termOldSlug'] ),
					new After( $meta_data['termNewName'] ),
					new After( $meta_data['termNewSlug'] ),
				];
				$details = [
					new Specification( __( 'Term type', LOGDASH_DOMAIN ), $meta_data['termType'] ),
					new Specification( __( 'Old name', LOGDASH_DOMAIN ), new Before( $meta_data['termOldName'] ) ),
					new Specification( __( 'New name', LOGDASH_DOMAIN ), new After( $meta_data['termNewName'] ) ),
					new Specification( __( 'Old slug', LOGDASH_DOMAIN ), new Before( $meta_data['termOldSlug'] ) ),
					new Specification( __( 'New slug', LOGDASH_DOMAIN ), new After( $meta_data['termNewSlug'] ) ),
				];
				break;
			case EventCodes::TERM_CREATED:
			case EventCodes::TERM_DELETED:
			case EventCodes::TAG_CREATED:
			case EventCodes::TAG_DELETED:
			case EventCodes::CATEGORY_CREATED:
			case EventCodes::CATEGORY_DELETED:
				$data    = [
					new Label( $meta_data['termName'] ),
					new Label( $meta_data['termType'] ),
				];
				$details = [
					new Specification( __( 'Term type', LOGDASH_DOMAIN ), $meta_data['termType'] ),
					new Specification( __( 'Term name', LOGDASH_DOMAIN ), $meta_data['termName'] ),
					new Specification( __( 'Term slug', LOGDASH_DOMAIN ), $meta_data['termSlug'] ),
				];
				break;
			default:
				$data = [];
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