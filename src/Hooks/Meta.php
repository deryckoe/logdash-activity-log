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

class Meta extends HooksBase {

	private static string $object_type = 'post';
	private $prev_meta;

	public function init() {

		$this->actions();
	}

	public function actions() {
		add_action( 'update_post_meta', [ $this, 'before_update_meta' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'meta_updated' ], 10, 4 );
		add_action( 'added_post_meta', [ $this, 'meta_added' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'meta_deleted' ], 10, 4 );
		add_filter( 'logdash_manage_columns-post-content_event_meta', [ $this, 'event_meta_info' ], 10, 3 );
	}

	public function before_update_meta( $meta_id, $object_id, $meta_key, $meta_value ) {

		if ( ! Posts::is_allowed( $object_id ) ) {
			return;
		}

		$this->prev_meta = get_post_meta( $object_id, $meta_key, true );
	}

	public function meta_updated( $meta_id, $object_id, $meta_key, $meta_value ) {

		if ( ! Posts::is_allowed( $object_id ) ) {
			return;
		}

		if ( in_array( $meta_key, [ '_edit_lock', '_edit_last', '_encloseme' ] ) ) {
			return;
		}

		if ( wp_is_post_revision( $object_id ) ) {
			return;
		}

		$current_user = wp_get_current_user();
		$post         = get_post( $object_id );

		$this
			->event
			->insert( EventTypes::MODIFIED, EventCodes::POST_UPDATED_META, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postTitle', get_the_title( $post ) ),
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postStatus', get_post_status( $post ) ),
				new EventMeta( 'postMetaKey', $meta_key ),
				new EventMeta( 'postOldMetaValue', $this->prev_meta ),
				new EventMeta( 'postNewMetaValue', $meta_value ),
			] );

	}

	public function meta_added( $mid, $object_id, $meta_key, $meta_value ) {
		if ( ! Posts::is_allowed( $object_id ) ) {
			return;
		}

		if ( in_array( $meta_key, [ '_edit_lock', '_edit_last', '_encloseme' ] ) ) {
			return;
		}

		if ( wp_is_post_revision( $object_id ) ) {
			return;
		}

		$current_user = wp_get_current_user();
		$post         = get_post( $object_id );

		$this
			->event
			->insert( EventTypes::MODIFIED, EventCodes::POST_CREATED_META, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postTitle', get_the_title( $post ) ),
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postStatus', get_post_status( $post ) ),
				new EventMeta( 'postMetaKey', $meta_key ),
				new EventMeta( 'postMetaValue', $meta_value ),
			] );

	}

	public function meta_deleted( $meta_ids, $object_id, $meta_key, $meta_value ) {

		if ( ! Posts::is_allowed( $object_id ) ) {
			return;
		}

		if ( in_array( $meta_key, [ '_edit_lock', '_edit_last', '_encloseme' ] ) ) {
			return;
		}

		if ( wp_is_post_revision( $object_id ) ) {
			return;
		}

		if ( empty( get_post_meta( $object_id, $meta_key, true ) ) ) {
			return;
		}

		$current_user = wp_get_current_user();
		$post         = get_post( $object_id );

		$this
			->event
			->insert( EventTypes::MODIFIED, EventCodes::POST_DELETED_META, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postTitle', get_the_title( $post ) ),
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postStatus', get_post_status( $post ) ),
				new EventMeta( 'postMetaKey', $meta_key ),
				new EventMeta( 'postMetaValue', $meta_value ),
			] );
	}

	public function event_meta_info( $output, $event_data, $meta_data ) {

		$message = EventCodes::desc( $event_data['event_code'] );
		if ( post_type_exists( $event_data['object_subtype'] ) ) {
			$actions = [
				[
					'href'   => get_edit_post_link( $event_data['object_id'] ),
					'target' => '_self',
					'label'  => __( 'Edit ' ) . ucfirst( $meta_data['postType'] ),
				],
			];
		} else {
			$actions = [];
		}
		$details = [
			new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
			new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
			new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
			new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
		];
		switch ( $event_data['event_code'] ) {
			case EventCodes::POST_UPDATED_META:
				$data    = [
					new Label( $meta_data['postMetaKey'] ),
					new Before( $meta_data['postOldMetaValue'] ),
					new After( $meta_data['postNewMetaValue'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details =
					array_merge( $details, [
						new Specification( __( 'Meta key', LOGDASH_DOMAIN ), $meta_data['postMetaKey'] ),
						new Specification( __( 'Old value', LOGDASH_DOMAIN ), new Before( $meta_data['postOldMetaValue'] ) ),
						new Specification( __( 'New value', LOGDASH_DOMAIN ), new After( $meta_data['postNewMetaValue'] ) ),
					] );
				break;

			case EventCodes::POST_CREATED_META:
			case EventCodes::POST_DELETED_META:
				$data    = [
					new Label( $meta_data['postMetaKey'] ),
					new After( $meta_data['postMetaValue'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details =
					array_merge( $details, [
						new Specification( __( 'Meta key', LOGDASH_DOMAIN ), $meta_data['postMetaKey'] ),
						new Specification( __( 'Old value', LOGDASH_DOMAIN ), $meta_data['postMetaValue'] ),
					] );

				break;

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
			->actions( $actions )
			->details( $details );

		return $view->get();
	}

}