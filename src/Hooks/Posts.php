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

class Posts extends HooksBase {

	private static string $object_type = 'post';
	private $prev_post;
	private $prev_link;
	private $prev_template;
	private $prev_status;
	private $prev_meta;

	private static $post_excluded = [
		'_acf-field-group',
		'_acf-field',
		'attachment',
		'revision',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'wp_global_styles',
	];

	public static function is_allowed( $post ): bool {
		$post = get_post( $post );

		if ( in_array( get_post_type( $post ), self::$post_excluded ) ) {
			return false;
		}

		return true;
	}

	public function init() {
		$this->actions();
	}

	public function actions() {
//		add_action( 'current_screen', [ $this, 'post_opened' ] );
		add_action( 'pre_post_update', [ $this, 'before_post_update' ], 10, 2 );
		add_action( 'wp_trash_post', [ $this, 'post_trashed' ], 10, 1 );
		add_action( 'untrash_post', [ $this, 'post_untrashed' ] );
		add_action( 'delete_post', [ $this, 'post_deleted' ], 10, 1 );
		add_action( 'post_stuck', [ $this, 'post_stuck' ] );
		add_action( 'post_unstuck', [ $this, 'post_unstuck' ] );
		add_action( 'save_post', [ $this, 'post_saved' ], 10, 3 );
		add_action( 'added_post_meta', [ $this, 'check_meta_updated' ], 10, 4 );
		add_action( 'update_post_meta', [ $this, 'before_update_meta' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'check_meta_updated' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'check_meta_updated' ], 10, 4 );
		add_action( 'set_object_terms', [ $this, 'category_assignment' ], 10, 6 );
		add_filter( 'logdash_manage_columns-' . self::$object_type . '-content_event_meta', [
			$this,
			'event_meta_info'
		], 10, 3 );
	}


	public function post_opened( \WP_Screen $current_screen ) {

		if ( $current_screen->base !== 'post' || empty( $_GET['post'] ) ) {
			return;
		}

		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], [ 'editpost', 'trash', 'untrash', 'delete' ] ) ) {
			return;
		}

		$post_id      = intval( $_GET['post'] );
		$post         = get_post( $post_id );
		$current_user = wp_get_current_user();

		$current_path = isset( $_SERVER['SCRIPT_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) . '?post=' . $post->ID : false;
		$referrer     = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : false;

		if ( ! empty( $referrer ) && strpos( $referrer, $current_path ) !== false ) {
			return;
		}

		if ( ! $this->event->is_last_event( EventCodes::POST_OPENED, $post_id ) ) {
			$this
				->event
				->insert( EventTypes::OPENED, EventCodes::POST_OPENED, self::$object_type, $post->post_type, $post_id, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post_id ) ),
					new EventMeta( 'postType', $current_screen->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post_id ) ),
				] );
		}

	}

	public function before_post_update( $post_id, $data ) {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id ); // Get post.

		if ( empty( $post ) || ! $post instanceof WP_Post ) {
			return;
		}

		$this->prev_post     = $post;
		$this->prev_link     = get_permalink( $post_id );
		$this->prev_template = $this->get_post_template( $this->prev_post );
		$this->prev_status = $post->post_status;
		$this->prev_meta   = get_post_meta( $post_id );
	}

	public function post_saved( $post_id, $post, $update ) {

		if ( empty( $post->post_type ) || 'revision' === $post->post_type || 'trash' === $post->post_status ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			if ( $this->prev_post && 'auto-draft' === $this->prev_post->post_status && 'draft' === $post->post_status ) {

				$this->create_post_event( $this->prev_post, $post );
			}

			return;
		}

		if ( ! defined( 'REST_REQUEST' ) && ! defined( 'DOING_AJAX' ) ) {

			if ( ! isset( $_REQUEST['classic-editor'] ) ) {
				$editor_replace = get_option( 'classic-editor-replace', 'classic' );
				$allow_users    = get_option( 'classic-editor-allow-users', 'disallow' );

				// If block editor is selected and users are not allowed to switch editors then it is Gutenberg's second request.
				if ( 'block' === $editor_replace && 'disallow' === $allow_users ) {
					return;
				}

				// If users are allowed to switch then it is Gutenberg's second request.
				if ( 'allow' === $allow_users ) {
					return;
				}
			}
		}

		$current_user = wp_get_current_user();

		if ( true === $update ) {

			// Dealing with auto-draft

			if ( $this->prev_post->post_status === 'auto-draft' ) {

				$this->create_post_event( $this->prev_post, $post );

			} else {

				$this->update_post_event( $this->prev_post, $post );
			}

		} else {

			// probably, let's create and auto draft.

		}

	}

	public function check_meta_updated( $meta_id, $post_id, $meta_key, $meta_value ) {

		switch ( $meta_key ) {
			case '_wp_page_template':
				$this->check_template_change( $post_id, $meta_value );
				break;
			case '_thumbnail_id':
				$this->check_featured_image_change( $post_id, $meta_value );
				break;
			default:
				return;
		}

	}

	public function before_update_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( '_edit_lock' === $meta_key ) {

			if ( ! function_exists( 'wp_check_post_lock' ) ) {
				require_once ABSPATH . 'wp-admin/includes/post.php';
			}
			$user_id = \wp_check_post_lock( $object_id );

			if ( $user_id ) {
				$old_user    = get_userdata( $user_id );
				$lock        = explode( ':', $meta_value );
				$new_user_id = $lock[1];

				if ( $new_user_id ) {
					$new_editor_user = get_userdata( $new_user_id );
					if ( $new_editor_user ) {
						$post         = get_post( $object_id );
						$current_user = wp_get_current_user();

						$this
							->event
							->insert( EventTypes::MODIFIED, EventCodes::POST_UNLOCKED, self::$object_type, $post->post_type, $object_id, $post->post_type, $current_user->ID, $current_user->roles[0] )
							->attachMany( [
								new EventMeta( 'postTitle', get_the_title( $object_id ) ),
								new EventMeta( 'postType', $post->post_type ),
								new EventMeta( 'postStatus', get_post_status( $object_id ) ),
								new EventMeta( 'postOldUser', $old_user->user_login ),
								new EventMeta( 'postNewUser', $new_editor_user->user_login ),
							] );
					}
				}
			}

		}
	}

	public function category_assignment( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {

		if ( $taxonomy === 'category' && empty( $old_tt_ids ) ) {
			return;
		}

		if ( empty( array_diff( $tt_ids, $old_tt_ids ) ) && empty( array_diff( $old_tt_ids, $tt_ids ) ) ) {
			return;
		}

		$old_term_names = $this->get_term_names( $old_tt_ids, $taxonomy );

		$new_term_names = $this->get_term_names( $tt_ids, $taxonomy );

		$current_user = wp_get_current_user();
		$post         = get_post( $object_id );
		$tax_data     = get_taxonomy( $taxonomy );

		if ( $taxonomy === 'category' ) {
			$event_code = EventCodes::POST_UPDATED_CATEGORY;
		} elseif ( $taxonomy === 'post_tag' ) {
			$event_code = EventCodes::POST_UPDATED_TAG;
		} else {
			$event_code = EventCodes::POST_UPDATED_TERM;
		}

		$this
			->event
			->insert( EventTypes::MODIFIED, $event_code, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postTitle', get_the_title( $post ) ),
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postStatus', get_post_status( $post ) ),
				new EventMeta( 'postTermName', $tax_data->label ),
				new EventMeta( 'postOldValues', implode( ', ', $old_term_names ) ),
				new EventMeta( 'postNewValues', implode( ', ', $new_term_names ) ),
			] );

	}

	private function get_term_names( $term_ids, $taxonomy ): array {
		if ( empty( $term_ids ) ) {
			if ( $taxonomy === 'category' ) {
				return [ get_cat_name( get_option( 'default_category' ) ) ];
			} else {
				return [ __( 'Unassigned' ) ];
			}
		}

		$terms = get_terms( [ 'hide_empty' => false, 'taxonomy' => $taxonomy, 'include' => $term_ids ] );

		return array_map( function ( $item ) {
			return $item->name;
		}, $terms );

	}


	public function post_trashed( $post_id ) {

		$post         = get_post( $post_id );
		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::TRASHED, EventCodes::POST_MOVED_TRASH, self::$object_type, $post->post_type, $post_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postTitle', $post->post_title ),
				new EventMeta( 'postStatus', $post->post_status ),
				new EventMeta( 'postDate', $post->post_date ),
				new EventMeta( 'postUrl', get_permalink( $post->ID ) ),
			] );

	}

	/**
	 * Post restored from trash.
	 *
	 * @param integer $post_id - Post ID.
	 */
	public function post_untrashed( $post_id ) {

		$post         = get_post( $post_id );
		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::RESTORED, EventCodes::POST_RESTORED, self::$object_type, $post->post_type, $post_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postTitle', $post->post_title ),
				new EventMeta( 'postStatus', $post->post_status ),
				new EventMeta( 'postDate', $post->post_date ),
				new EventMeta( 'postUrl', get_permalink( $post->ID ) ),
			] );

		remove_action( 'save_post', [ $this, 'post_saved' ], 10, 3 );
	}

	public function post_deleted( $post_id ) {

		$post = get_post( $post_id );

		if ( $post->post_status === 'auto-draft' && $post->post_title = 'Auto Draft' ) {
			return;
		}

		if ( wp_is_post_revision( $post ) ) {
			return;
		}

		if ( in_array( $this->get_post_type( $post_id ), [ 'attachment' ] ) ) {
			return;
		}

		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::DELETED, EventCodes::POST_DELETED, self::$object_type, $post->post_type, $post_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postTitle', $post->post_title ),
				new EventMeta( 'postStatus', $post->post_status ),
				new EventMeta( 'postDate', $post->post_date ),
				new EventMeta( 'postUrl', get_permalink( $post->ID ) ),
			] );

	}

	public function post_stuck( $post_id ) {
		$post         = get_post( $post_id );
		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::MODIFIED, EventCodes::POST_STUCK, self::$object_type, $post->post_type, $post_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postTitle', get_the_title( $post_id ) ),
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postStatus', get_post_status( $post_id ) ),
			] );
	}

	public function post_unstuck( $post_id ) {
		$post         = get_post( $post_id );
		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::MODIFIED, EventCodes::POST_UNSTUCK, self::$object_type, $post->post_type, $post_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postTitle', get_the_title( $post_id ) ),
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postStatus', get_post_status( $post_id ) ),
			] );
	}

	public function event_meta_info( $output, $event_data, $meta_data ) {

		$message = EventCodes::desc( $event_data['event_code'] );
		$details = [];


		if ( post_type_exists( $event_data['object_subtype'] ) && get_post( $event_data['object_id'] ) ) {

			$actions = [
				[
					'href'   => get_edit_post_link( $event_data['object_id'] ),
					'target' => '_self',
					'label'  => __( 'Edit' ),
				],
				[
					'href'   => get_permalink( $event_data['object_id'] ),
					'target' => '_self',
					'label'  => __( 'View' ),
				],
			];

			if ( ! empty( $meta_data['postRevision'] ) ) {

				$actions[] = [
					'href'   => get_edit_post_link( $meta_data['postRevision'] ),
					'target' => '_self',
					'label'  => __( 'Differences' ),
				];

			}


		} else {
			$actions = [];
		}

		switch ( $event_data['event_code'] ) {
			case EventCodes::POST_OPENED:
				$data    = [
					new Label( ucfirst( $meta_data['postType'] ) ),
					new Label( $meta_data['postTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
				];
				break;
			case EventCodes::POST_UNLOCKED:
				$data    = [
					new Label( $meta_data['postTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Old user', LOGDASH_DOMAIN ), new Before( $meta_data['postOldUser'] ) ),
					new Specification( __( 'New user', LOGDASH_DOMAIN ), new After( $meta_data['postNewUser'] ) ),
				];
				break;
			case EventCodes::POST_UPDATED_CATEGORY:
			case EventCodes::POST_UPDATED_TAG:
			case EventCodes::POST_UPDATED_TERM:
				$data    = [
					new Before( $meta_data['postOldValues'] ),
					new After( $meta_data['postNewValues'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Term name', LOGDASH_DOMAIN ), new Before( $meta_data['postTermName'] ) ),
					new Specification( __( 'Old values', LOGDASH_DOMAIN ), new Before( $meta_data['postOldValues'] ) ),
					new Specification( __( 'New values', LOGDASH_DOMAIN ), new After( $meta_data['postNewValues'] ) ),
				];
				break;
			case EventCodes::POST_MOVED_TRASH:
			case EventCodes::POST_DELETED:
			case EventCodes::POST_RESTORED:
				$data    = [
					new Label( $meta_data['postTitle'] )
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Post date', LOGDASH_DOMAIN ), $meta_data['postDate'] ),
					new Specification( __( 'Post url', LOGDASH_DOMAIN ), $meta_data['postUrl'] ),
				];
				break;
			case EventCodes::POST_CREATED:
			case EventCodes::POST_STUCK:
			case EventCodes::POST_UNSTUCK:
			case EventCodes::POST_PUBLISHED:
			case EventCodes::POST_COMMENTS_ENABLED:
			case EventCodes::POST_COMMENTS_DISABLED:
			case EventCodes::POST_PINGS_ENABLED:
			case EventCodes::POST_PINGS_DISABLED:
			case EventCodes::POST_UPDATED_CONTENT:
				$data    = [
					new Label( $meta_data['postTitle'] )
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
				];
				break;
			case EventCodes::POST_UPDATED_STATUS:
				$data    = [
					new Label( $meta_data['postTitle'] ),
					new Label( $meta_data['postStatus'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
				];
				break;
			case EventCodes::POST_VISIBILITY_UPDATED:
				$data    = [
					new Label( $meta_data['postTitle'] ),
					new Before( $meta_data['postOldVisibility'] ),
					new After( $meta_data['postNewVisibility'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), new Before( $meta_data['postOldVisibility'] ) ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), new After( $meta_data['postNewVisibility'] ) ),
				];
				break;
			case EventCodes::POST_UPDATED_TITLE:
				$data    = [
					new Label( $meta_data['postTitle'] ),
					new Before( $meta_data['postOldTitle'] ),
					new After( $meta_data['postNewTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), new Before( $meta_data['postOldTitle'] ) ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), new After( $meta_data['postNewTitle'] ) ),
				];
				break;
			case EventCodes::POST_UPDATED_EXCERPT:
				$data    = [
					new Before( $meta_data['postOldExcerpt'] ),
					new After( $meta_data['postNewExcerpt'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Old excerpt', LOGDASH_DOMAIN ), new Before( $meta_data['postOldExcerpt'] ) ),
					new Specification( __( 'New excerpt', LOGDASH_DOMAIN ), new After( $meta_data['postNewExcerpt'] ) ),
				];
				break;
			case EventCodes::POST_UPDATED_SLUG:
				$data    = [
					new Before( $meta_data['postOldSlug'] ),
					new After( $meta_data['postNewSlug'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Old slug', LOGDASH_DOMAIN ), new Before( $meta_data['postOldSlug'] ) ),
					new Specification( __( 'New slug', LOGDASH_DOMAIN ), new After( $meta_data['postNewSlug'] ) ),
				];
				break;
			case EventCodes::POST_UPDATED_AUTHOR:
				$data      = [
					new Label( $meta_data['postNewAuthorLogin'] ),
					new Label( $meta_data['postOldAuthorLogin'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details   = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Old ID', LOGDASH_DOMAIN ), new Before( $meta_data['postOldAuthorID'] ) ),
					new Specification( __( 'New ID', LOGDASH_DOMAIN ), new After( $meta_data['postNewAuthorID'] ) ),
					new Specification( __( 'Old Author', LOGDASH_DOMAIN ), new Before( $meta_data['postOldAuthorLogin'] ) ),
					new Specification( __( 'New Author', LOGDASH_DOMAIN ), new After( $meta_data['postNewAuthorLogin'] ) ),
				];
				$actions[] = [
					'href'   => get_edit_user_link( $meta_data['postNewAuthorID'] ),
					'target' => '_self',
					'label'  => __( 'Edit author profile' )
				];
				break;
			case EventCodes::POST_THUMBNAIL_ADDED:
			case EventCodes::POST_UPDATED_THUMBNAIL:
			case EventCodes::POST_THUMBNAIL_REMOVED:
				$data    = [
					new Label( $meta_data['postNewThumbnail'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Old Thumbnail', LOGDASH_DOMAIN ), new Before( $meta_data['postOldThumbnail'] ) ),
					new Specification( __( 'New Thumbnail', LOGDASH_DOMAIN ), new After( $meta_data['postNewThumbnail'] ) ),
				];
				break;
			case EventCodes::POST_UPDATED_PUBLISH_DATE:
				$data    = [
					new Before( $meta_data['postOldDate'] ),
					new After( $meta_data['postNewDate'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Old Date', LOGDASH_DOMAIN ), new Before( $meta_data['postOldDate'] ) ),
					new Specification( __( 'New Date', LOGDASH_DOMAIN ), new After( $meta_data['postNewDate'] ) ),
				];
				break;
			case EventCodes::POST_SCHEDULED:
				$data    = [
					new Label( $meta_data['postTitle'] ),
					new Label( $meta_data['postPublishingDate'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Publishing Date', LOGDASH_DOMAIN ), $meta_data['postPublishingDate'] ),
				];
				break;
			case EventCodes::POST_UPDATED_PARENT:
				$data      = [
					new After( $meta_data['postNewParentTitle'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details   = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Old Parent Link', LOGDASH_DOMAIN ), new Before( $meta_data['postOldParentLink'] ) ),
					new Specification( __( 'New Parent Link', LOGDASH_DOMAIN ), new After( $meta_data['postNewParentLink'] ) ),
					new Specification( __( 'Old Parent Title', LOGDASH_DOMAIN ), new Before( $meta_data['postOldParentTitle'] ) ),
					new Specification( __( 'New Parent Title', LOGDASH_DOMAIN ), new After( $meta_data['postNewParentTitle'] ) ),
				];
				$actions[] = [
					'href'   => get_edit_post_link( $meta_data['postNewParentId'] ),
					'target' => '_self',
					'label'  => __( 'Edit parent' ),
				];
				break;
			case EventCodes::POST_TEMPLATE_UPDATED:
				$data    = [
					new Label( $meta_data['postTitle'] ),
					new Label( $meta_data['postNewTemplate'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Old Template', LOGDASH_DOMAIN ), new Before( $meta_data['postOldTemplate'] ) ),
					new Specification( __( 'New Template', LOGDASH_DOMAIN ), new After( $meta_data['postNewTemplate'] ) ),
				];
				break;
			case EventCodes::POST_UPDATED_MENU_ORDER:
				$data    = [
					new Before( $meta_data['postOldMenuOrder'] ),
					new After( $meta_data['postNewMenuOrder'] ),
					new Label( $meta_data['postTitle'] ),
				];
				$details = [
					new Specification( __( 'Post ID', LOGDASH_DOMAIN ), $event_data['object_id'] ),
					new Specification( __( 'Post title', LOGDASH_DOMAIN ), $meta_data['postTitle'] ),
					new Specification( __( 'Post status', LOGDASH_DOMAIN ), $meta_data['postStatus'] ),
					new Specification( __( 'Post type', LOGDASH_DOMAIN ), $meta_data['postType'] ),
					new Specification( __( 'Old Menu order', LOGDASH_DOMAIN ), new Before( $meta_data['postOldMenuOrder'] ) ),
					new Specification( __( 'New Menu order', LOGDASH_DOMAIN ), new After( $meta_data['postNewMenuOrder'] ) ),
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
			->actions( $actions )
			->details( $details );

		return $view->get();
	}

	private function get_post_type( $post ) {
		$post = get_post( $post );

		return \get_post_type( $post );
	}

	private function get_post_template( $post ): string {
		if ( ! isset( $post->ID ) ) {
			return '';
		}

		$template = get_post_meta( $post->ID, '_wp_page_template', true );

		return ( $template ) ? ucwords( str_replace( array( '-', '_' ), ' ', basename( $template ) ) ) : 'Default';
	}

	private function check_featured_image_change( $post_id, $meta_value ) {
		$previous_featured_image = ( isset( $this->prev_meta['_thumbnail_id'][0] ) ) ? wp_get_attachment_metadata( $this->prev_meta['_thumbnail_id'][0] ) : false;
		$new_featured_image      = wp_get_attachment_metadata( $meta_value );

		if ( empty( $new_featured_image['file'] ) && empty( $previous_featured_image['file'] ) ) {
			return;
		}

		$event_code = EventCodes::POST_UPDATED_THUMBNAIL;

		if ( empty( $previous_featured_image['file'] ) && ! empty( $new_featured_image['file'] ) ) {
			$event_code = EventCodes::POST_THUMBNAIL_ADDED;
		} elseif ( ! empty( $previous_featured_image['file'] ) && empty( $new_featured_image['file'] ) ) {
			$event_code = EventCodes::POST_THUMBNAIL_REMOVED;
		}

		$previous_image = is_array( $previous_featured_image ) && array_key_exists( 'file', $previous_featured_image ) ? $previous_featured_image['file'] : '';
		$new_image      = is_array( $new_featured_image ) && array_key_exists( 'file', $new_featured_image ) ? $new_featured_image['file'] : '';

		$post         = get_post( $post_id );
		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::MODIFIED, $event_code, self::$object_type, $post->post_type, $post_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'postTitle', get_the_title( $post_id ) ),
				new EventMeta( 'postType', $post->post_type ),
				new EventMeta( 'postStatus', get_post_status( $post_id ) ),
				new EventMeta( 'postOldThumbnail', $previous_image ),
				new EventMeta( 'postNewThumbnail', $new_image ),
			] );

	}

	private function check_template_change( $post_id, $meta_value ) {
		$post          = get_post( $post_id );
		$prev_template = ( $this->prev_template && 'page' !== basename( $this->prev_template, '.php' ) ) ? ucwords( str_replace( array(
			'-',
			'_'
		), ' ', basename( $this->prev_template, '.php' ) ) ) : 'Default';
		$new_template  = ( $meta_value ) ? ucwords( str_replace( array(
			'-',
			'_'
		), ' ', basename( $meta_value ) ) ) : 'Default';

		if ( $prev_template !== $new_template ) {

			$current_user = wp_get_current_user();

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_TEMPLATE_UPDATED, self::$object_type, $post->post_type, $post_id, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post_id ) ),
					new EventMeta( 'postType', $post->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post_id ) ),
					new EventMeta( 'postOldTemplate', $prev_template ),
					new EventMeta( 'postNewTemplate', $new_template ),
				] );
		}
	}

	private function create_post_event( $prev_post, $post ) {

		$current_user = wp_get_current_user();

		$meta = [
			new EventMeta( 'postType', $post->post_type ),
			new EventMeta( 'postTitle', $post->post_title ),
			new EventMeta( 'postStatus', $post->post_status ),
		];

		if ( $post->post_status === 'publish' ) {
			$event_code = EventCodes::POST_PUBLISHED;
		} elseif ( $post->post_status === 'future' ) {
			$event_code = EventCodes::POST_SCHEDULED;
			$meta[]     = new EventMeta( 'postPublishingDate', $post->post_date );
		} else {
			$event_code = EventCodes::POST_CREATED;
		}

		$this
			->event
			->insert( EventTypes::CREATED, $event_code, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
			->attachMany( $meta );

	}

	private function update_post_event( $prev_post, $post ) {

		$current_user = wp_get_current_user();

		$meta = [
			new EventMeta( 'postType', $post->post_type ),
			new EventMeta( 'postTitle', $post->post_title ),
			new EventMeta( 'postStatus', $post->post_status ),
		];

		// Post title update

		if ( $prev_post->post_title !== $post->post_title ) {

			$event_meta = array_merge( $meta, [
				new EventMeta( 'postOldTitle', $this->prev_post->post_title ),
				new EventMeta( 'postNewTitle', $post->post_title ),
			] );

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_UPDATED_TITLE, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( $event_meta );
		}

		// excerpt

		if ( $prev_post->post_excerpt !== $post->post_excerpt ) {

			$event_meta = array_merge( $meta, [
				new EventMeta( 'postOldExcerpt', $this->prev_post->post_excerpt ),
				new EventMeta( 'postNewExcerpt', $post->post_excerpt ),
			] );

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_UPDATED_EXCERPT, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( $event_meta );

		}

		// Post content update


		if ( $prev_post->post_content !== $post->post_content ) {

			$revisions = wp_get_post_revisions( $post->ID, [ 'posts_per_page' => 1 ] );

			$event_meta = array_merge( $meta, [
				new EventMeta( 'postRevision', array_key_first( $revisions ) )
			] );

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_UPDATED_CONTENT, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( $event_meta );

		}

		// Post status update

		if ( $this->prev_post->post_status !== $post->post_status ) {

			$event_meta = [];

			if ( $post->post_status === 'publish' ) {
				$event_code = EventCodes::POST_PUBLISHED;
			} elseif ( $post->post_status === 'draft' ) {
				$event_code = EventCodes::POST_UPDATED_STATUS;
			} elseif ( $post->post_status === 'pending' ) {
				$event_code = EventCodes::POST_UPDATED_STATUS;
			} elseif ( $post->post_status === 'future' ) {
				$event_code   = EventCodes::POST_SCHEDULED;
				$event_meta[] = new EventMeta( 'postPublishingDate', $post->post_date );
			} elseif ( $post->post_status === 'private' ) {
				$event_code = EventCodes::POST_UPDATED_STATUS;
			} else {
				$event_code = EventCodes::POST_UPDATED_STATUS;
			}

			$event_meta = array_merge( $meta, $event_meta );

			$this
				->event
				->insert( EventTypes::MODIFIED, $event_code, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( $event_meta );

		}

		// Post visibility update

		if ( $this->prev_post->post_password ) {
			$prev_visibility = esc_html__( 'Password Protected', LOGDASH_DOMAIN );
		} elseif ( 'private' === $this->prev_post->post_status ) {
			$prev_visibility = esc_html__( 'Private', LOGDASH_DOMAIN );
		} else {
			$prev_visibility = esc_html__( 'Public', LOGDASH_DOMAIN );
		}

		if ( $post->post_password ) {
			$new_visibility = esc_html__( 'Password Protected', LOGDASH_DOMAIN );
		} elseif ( 'private' === $post->post_status ) {
			$new_visibility = esc_html__( 'Private', LOGDASH_DOMAIN );
		} else {
			$new_visibility = esc_html__( 'Public', LOGDASH_DOMAIN );
		}

		if ( $prev_visibility !== $new_visibility ) {

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_VISIBILITY_UPDATED, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post->ID ) ),
					new EventMeta( 'postType', $post->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post->ID ) ),
					new EventMeta( 'postOldVisibility', $prev_visibility ),
					new EventMeta( 'postNewVisibility', $new_visibility ),
				] );
		}

		// author

		if ( $this->prev_post->post_author !== $post->post_author ) {

			$old_author = get_user_by( 'id', $this->prev_post->post_author );
			$new_author = get_user_by( 'id', $post->post_author );

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_UPDATED_AUTHOR, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post->ID ) ),
					new EventMeta( 'postType', $post->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post->ID ) ),
					new EventMeta( 'postOldAuthorID', $old_author->ID ),
					new EventMeta( 'postNewAuthorID', $new_author->ID ),
					new EventMeta( 'postOldAuthorLogin', $old_author->user_login ),
					new EventMeta( 'postNewAuthorLogin', $new_author->user_login ),
				] );


		}

		// published date

		if ( $this->prev_post->post_date_gmt !== $post->post_date_gmt ) {

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_UPDATED_PUBLISH_DATE, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post->ID ) ),
					new EventMeta( 'postType', $post->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post->ID ) ),
					new EventMeta( 'postOldDate', $this->prev_post->post_date_gmt ),
					new EventMeta( 'postNewDate', $post->post_date_gmt ),
				] );

		}

		// slug

		if ( $this->prev_post->post_name !== $post->post_name ) {

			$old_permalink = $this->prev_link;
			$new_permalink = get_permalink( $post->ID );

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_UPDATED_SLUG, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post->ID ) ),
					new EventMeta( 'postType', $post->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post->ID ) ),
					new EventMeta( 'postOldSlug', $old_permalink ),
					new EventMeta( 'postNewSlug', $new_permalink ),
				] );

		}

		// comments

		if ( $this->prev_post->comment_status !== $post->comment_status ) {

			if ( $post->comment_status === 'open' ) {
				$event_code = EventCodes::POST_COMMENTS_ENABLED;
			} else {
				$event_code = EventCodes::POST_COMMENTS_DISABLED;
			}

			$this
				->event
				->insert( EventTypes::MODIFIED, $event_code, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post->ID ) ),
					new EventMeta( 'postType', $post->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post->ID ) ),
				] );

		}

		// pingbacks

		if ( $this->prev_post->ping_status !== $post->ping_status ) {

			if ( $post->comment_status === 'open' ) {
				$event_code = EventCodes::POST_PINGS_ENABLED;
			} else {
				$event_code = EventCodes::POST_PINGS_DISABLED;
			}

			$this
				->event
				->insert( EventTypes::MODIFIED, $event_code, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post->ID ) ),
					new EventMeta( 'postType', $post->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post->ID ) ),
				] );

		}

		// post parent

		if ( $this->prev_post->post_parent !== $post->post_parent ) {

			$old_parent_link  = get_the_permalink( $this->prev_post->post_parent );
			$new_parent_link  = get_the_permalink( $post->post_parent );
			$old_parent_title = get_the_title( $this->prev_post->post_parent );
			$new_parent_title = get_the_title( $post->post_parent );
			$old_parent_id    = $this->prev_post->post_parent;
			$new_parent_id    = $post->post_parent;

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_UPDATED_PARENT, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post->ID ) ),
					new EventMeta( 'postType', $post->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post->ID ) ),
					new EventMeta( 'postOldParentLink', $old_parent_link ),
					new EventMeta( 'postNewParentLink', $new_parent_link ),
					new EventMeta( 'postOldParentTitle', $old_parent_title ),
					new EventMeta( 'postNewParentTitle', $new_parent_title ),
					new EventMeta( 'postOldParentId', $old_parent_id ),
					new EventMeta( 'postNewParentId', $new_parent_id ),
				] );

		}

		// post menu order

		if ( $this->prev_post->menu_order !== $post->menu_order ) {

			$this
				->event
				->insert( EventTypes::MODIFIED, EventCodes::POST_UPDATED_MENU_ORDER, self::$object_type, $post->post_type, $post->ID, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'postTitle', get_the_title( $post->ID ) ),
					new EventMeta( 'postType', $post->post_type ),
					new EventMeta( 'postStatus', get_post_status( $post->ID ) ),
					new EventMeta( 'postOldMenuOrder', $this->prev_post->menu_order ),
					new EventMeta( 'postNewMenuOrder', $post->menu_order ),
				] );

		}


	}

}