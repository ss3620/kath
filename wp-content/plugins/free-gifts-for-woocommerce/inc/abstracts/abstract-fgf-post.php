<?php

/**
 * Abstract - Post.
 * 
 * @since 1.0.0
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Post')) {

	/**
	 * Class.
	 * 
	 * @since 1.0.0
	 * */
	abstract class FGF_Post {

		/**
		 * ID.
		 * 
		 * @since 1.0.0
		 * @var string/int
		 * */
		protected $id = '';

		/**
		 * Post type.
		 * 
		 * @since 1.0.0
		 * @var string
		 * */
		protected $post_type = '';

		/**
		 * Post.
		 * 
		 * @since 1.0.0
		 * @var object
		 * */
		protected $post;

		/**
		 * Default post keys.
		 * 
		 * @since 11.1.0
		 * @var array.
		 * */
		protected $default_post_keys = array(
			'name' => 'post_title',
			'status' => 'post_status',
			'created_date' => 'post_date_gmt',
			'modified_date' => 'post_modified_gmt',
		);

		/**
		 * Post keys.
		 * 
		 * @since 11.1.0
		 * @var array.
		 * */
		protected $post_keys = array();

		/**
		 * Default post data.
		 * 
		 * @since 11.1.0
		 * @var array
		 * */
		protected $default_post_data = array();

		/**
		 * Current post data.
		 * 
		 * @since 11.1.0
		 * @var array
		 * */
		protected $current_post_data = array();

		/**
		 * Meta data keys.
		 * 
		 * @since 1.0.0
		 * @var array.
		 * */
		protected $meta_data_keys = array();

		/**
		 * Default meta data.
		 * 
		 * @since 1.0.0
		 * @var array
		 * */
		protected $default_meta_data = array();

		/**
		 * Current meta data.
		 * 
		 * @since 1.0.0
		 * @var array
		 * */
		protected $current_meta_data = array();

		/**
		 * Duplicate meta keys.
		 * 
		 * @since 1.0.0
		 * @var array
		 * */
		protected $duplicate_meta_keys = array();

		/**
		 * Compatibility meta data keys.
		 * 
		 * @since 9.4.0
		 * @var array.
		 * */
		protected $compatibility_meta_data_keys = array();

		/**
		 * Class initialization.
		 * 
		 * @since 1.0.0
		 * @param int $_id
		 */
		public function __construct( $_id = '' ) {
			$this->id = $_id;

			if ($_id) {
				$this->populate_data();
			}
		}

		/**
		 * Get the ID.
		 * 
		 * @since 1.0.0
		 * @return string/int
		 * */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Get the post type.
		 * 
		 * @since 11.1.0
		 * @return string
		 * */
		public function get_post_type() {
			return $this->post_type;
		}

		/**
		 * Get the status.
		 * 
		 * @since 1.0.0
		 * @return string
		 * */
		public function get_status() {
			return $this->get_post_prop('status');
		}

		/**
		 * Get the name.
		 * 
		 * @since 11.1.0
		 * @return string
		 * */
		public function get_name() {
			return $this->get_post_prop('name');
		}

		/**
		 * Get the created date.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_created_date() {
			return $this->get_post_prop('created_date');
		}

		/**
		 * Get the modified date.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_modified_date() {
			return $this->get_post_prop('modified_date');
		}

		/**
		 * Get the post.
		 * 
		 * @since 11.1.0
		 * @return object
		 * */
		public function get_post() {
			return $this->post;
		}

		/**
		 * Set name.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_name( $value ) {
			$this->set_post_prop('name', $value);
		}

		/**
		 * Set status.
		 * 
		 * @since 11.1.0
		 * @param string $value
		 */
		public function set_status( $value ) {
			$this->set_post_prop('status', $value);
		}

		/**
		 * Set created date.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_created_date( $value ) {
			$this->set_post_prop('created_date', $value);
		}

		/**
		 * Set modified date.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_modified_date( $value ) {
			$this->set_post_prop('modified_date', $value);
		}

		/**
		 * Is a valid post?.
		 * 
		 * @since 1.0.0
		 * @return boolean
		 * */
		public function exists() {
			return isset($this->post->post_type) && $this->post->post_type == $this->get_post_type();
		}

		/**
		 * Get the status label.
		 * 
		 * @since 11.1.0
		 * @param boolean $html
		 * @return string
		 * */
		public function get_status_label( $html = false ) {
			return fgf_get_status_label($this->get_status(), $html);
		}

		/**
		 * Get the post keys.
		 * 
		 * @since 11.1.0
		 * @return array
		 */
		protected function get_post_keys() {
			return $this->default_post_keys + $this->post_keys;
		}

		/**
		 * Get the meta data keys.
		 * 
		 * @since 9.4.0
		 * @return array
		 */
		protected function get_meta_data_keys() {
			return $this->meta_data_keys + $this->compatibility_meta_data_keys;
		}

		/**
		 * Get the duplicate meta keys.
		 * 
		 * @since 11.1.0
		 * @return array
		 */
		protected function get_duplicate_meta_keys() {
			return $this->duplicate_meta_keys + array_keys($this->compatibility_meta_data_keys);
		}

		/**
		 * Has a status?
		 * 
		 * @since 1.0.0
		 * @param string/array $statuses
		 * @return boolean
		 * */
		public function has_status( $statuses ) {
			if (is_array($statuses) && in_array($this->get_status(), $statuses)) {
				return true;
			}

			if ($this->get_status() == $statuses) {
				return true;
			}

			return false;
		}

		/**
		 * Update a status.
		 * 
		 * @since 1.0.0
		 * @return boolean/WP_Error
		 * */
		public function update_status( $status ) {
			$post_args = array(
				'ID' => $this->get_id(),
				'post_type' => $this->get_post_type(),
				'post_status' => $status,
			);

			return wp_update_post($post_args);
		}

		/**
		 * Populate the data for current post.
		 * 
		 * @since 1.0.0
		 * @return null
		 */
		protected function populate_data() {
			if ('auto-draft' == $this->get_status()) {
				return;
			}

			$this->load_postdata();
			$this->load_metadata();
			$this->load_extra_postdata();
		}

		/**
		 * Load the post data.
		 * 
		 * @since 1.0.0
		 * @return null
		 */
		protected function load_postdata() {
			$this->post = get_post($this->get_id());
			if (!$this->post) {
				return;
			}

			$post_keys = $this->get_post_keys();
			foreach ($post_keys as $object_key => $post_key) {
				$this->default_post_data [$object_key] = $this->get_post()->$post_key;
			}
		}

		/**
		 * Load the extra post data.
		 * 
		 * @since 1.0.0
		 * */
		protected function load_extra_postdata() {
		}

		/**
		 * Load the post meta data.
		 * 
		 * @since 1.0.0
		 * @return null
		 */
		protected function load_metadata() {
			$meta_data_array = get_post_meta($this->get_id());
			if (!fgf_check_is_array($meta_data_array)) {
				return;
			}

			$meta_data_keys = $this->get_meta_data_keys();
			foreach ($meta_data_keys as $key => $value) {

				if (!isset($meta_data_array[$key][0])) {
					continue;
				}

				$meta_data = ( is_serialized($meta_data_array[$key][0]) ) ? @unserialize($meta_data_array[$key][0]) : $meta_data_array[$key][0];
				$this->default_meta_data [$key] = $meta_data;
			}
		}

		/**
		 * Get a post property.
		 * 
		 * @since 11.1.0
		 * @param string $key
		 * @return mixed
		 */
		protected function get_post_prop( $key ) {
			if (isset($this->current_post_data[$key])) {
				// Current object meta value.
				return $this->current_post_data[$key];
			} elseif (isset($this->default_post_data[$key])) {
				// Database meta value.
				return $this->default_post_data[$key];
			}

			return '';
		}

		/**
		 * Get a meta property.
		 * 
		 * @since 1.0.0
		 * @param string $key
		 * @return mixed
		 */
		protected function get_meta_prop( $key ) {
			if (isset($this->current_meta_data[$key])) {
				// Current object meta value.
				return $this->current_meta_data[$key];
			} elseif (isset($this->default_meta_data[$key])) {
				// Database meta value.
				return $this->default_meta_data[$key];
			} elseif ($this->meta_key_exists($key)) {
				// Default meta value.
				return $this->meta_data_keys[$key];
			}

			return '';
		}

		/**
		 * Set a properties.
		 * 
		 * @since 1.0.0
		 * @param Array $data
		 */
		public function set_props( $data ) {
			foreach ($data as $key => $value) {
				// Set the current properties value.
				$this->set_post_prop($key, $value);
				$this->set_meta_prop($key, $value);
			}
		}

		/**
		 * Set a post property.
		 * 
		 * @since 11.1.0
		 * @param string $key
		 * @param mixed $value
		 */
		protected function set_post_prop( $key, $value ) {
			if ($this->post_key_exists($key)) {
				// Set the current post meta value.
				$this->current_post_data[$key] = $value;
			}
		}

		/**
		 * Set a meta property.
		 * 
		 * @since 1.0.0
		 * @param string $key
		 * @param mixed $value
		 */
		protected function set_meta_prop( $key, $value ) {
			if ($this->meta_key_exists($key)) {
				// Set the current object meta value.
				$this->current_meta_data[$key] = $value;
			}
		}

		/**
		 * Get post changes.
		 * 
		 * @since 11.1.0
		 * @return array
		 */
		public function get_post_changes() {
			return $this->current_post_data;
		}

		/**
		 * Get post meta changes.
		 * 
		 * @since 11.1.0
		 * @return array
		 */
		public function get_meta_changes() {
			return $this->current_meta_data;
		}

		/**
		 * Post key exists.
		 * 
		 * @since 11.1.0
		 * @param string $key
		 * @return boolean
		 */
		public function post_key_exists( $key ) {
			return array_key_exists($key, $this->get_post_keys());
		}

		/**
		 * Meta key exists.
		 * 
		 * @since 11.1.0
		 * @param string $key
		 * @return boolean
		 */
		public function meta_key_exists( $key ) {
			return array_key_exists($key, $this->get_meta_data_keys());
		}

		/**
		 * Get post data to update.
		 * 
		 * @since 11.1.0
		 * @return array
		 */
		protected function get_post_data_to_update() {
			$post_data = array();
			$post_keys = $this->get_post_keys();
			foreach ($this->get_post_changes() as $key => $value) {
				if (!$this->post_key_exists($key)) {
					continue;
				}

				$post_data[$post_keys[$key]] = $value;
			}

			return $post_data;
		}

		/**
		 * Get meta data to update.
		 * 
		 * @since 11.1.0
		 * @return array
		 */
		protected function get_meta_data_to_update() {
			$meta_data = array();
			foreach ($this->get_meta_changes() as $key => $value) {
				if (!$this->meta_key_exists($key)) {
					continue;
				}

				$meta_data[$key] = $value;
			}

			return $meta_data;
		}

		/**
		 * Save a post.
		 * 
		 * @since 11.1.0
		 */
		public function save() {
			if ($this->get_id()) {
				$this->update($this->get_meta_data_to_update(), $this->get_post_data_to_update());
			} else {
				$this->create($this->get_meta_data_to_update(), $this->get_post_data_to_update());
			}
		}

		/**
		 * Create post.
		 * 
		 * @since 1.0.0
		 * @param array   $meta_data
		 * @param array   $post_args
		 * @param boolean $duplicate
		 * @return int
		 */
		public function create( $meta_data, $post_args = array(), $duplicate = false ) {
			$default_post_args = array(
				'post_type' => $this->get_post_type(),
				'post_status' => $this->post_status,
			);

			$this->id = wp_insert_post(wp_parse_args($post_args, $default_post_args));

			$this->update_metas($meta_data);

			if (!$duplicate) {
				$this->populate_data();
			}

			return $this->get_id();
		}

		/**
		 * Update post.
		 * 
		 * @since 1.0.0
		 * @param array $meta_data
		 * @param array $post_args
		 * @return boolean/int
		 */
		public function update( $meta_data, $post_args = array() ) {
			if (!$this->get_id()) {
				return false;
			}

			$default_post_args = array(
				'ID' => $this->get_id(),
				'post_type' => $this->get_post_type(),
				'post_status' => $this->get_status(),
			);

			wp_update_post(wp_parse_args($post_args, $default_post_args));

			$this->update_metas($meta_data);

			$this->populate_data();

			return $this->get_id();
		}

		/**
		 * Duplicate
		 * 
		 * @since 1.0.0
		 * @return int/boolean
		 */
		public function duplicate() {
			if (!fgf_check_is_array($this->duplicate_meta_keys)) {
				return false;
			}

			$meta_data = array();
			$duplicate_keys = $this->get_duplicate_meta_keys();
			foreach ($duplicate_keys as $key) {
				if (!isset($this->default_meta_data[$key])) {
					continue;
				}

				$meta_data[$key] = $this->default_meta_data[$key];
			}

			$post_data = array(
				'post_title' => $this->get_post()->post_title . '- Duplicate',
				'post_content' => $this->get_post()->post_content,
				'post_status' => $this->status,
			);

			$current_id = $this->id;
			$duplicate_id = $this->create($meta_data, $post_data, true);
			$this->id = $current_id;

			return $duplicate_id;
		}

		/**
		 * Update the post meta data.
		 * 
		 * @since 1.0.0
		 * @param array $meta_data
		 * @return null
		 */
		public function update_metas( $meta_data ) {
			if (!$this->get_id()) {
				return;
			}

			$meta_data_keys = $this->get_meta_data_keys();
			foreach ($meta_data_keys as $meta_key => $default) {
				if (!isset($meta_data[$meta_key])) {
					continue;
				}

				update_post_meta($this->get_id(), sanitize_key($meta_key), $meta_data[$meta_key]);
			}
		}

		/**
		 * Update a post meta.
		 * 
		 * @param string $meta_key
		 * @param mixed $value
		 * @param boolean $set_prop
		 * @return boolean
		 */
		public function update_meta( $meta_key, $value, $set_prop = false ) {
			if (!$this->get_id()) {
				return false;
			}

			update_post_meta($this->get_id(), sanitize_key($meta_key), $value);

			if ($set_prop) {
				$this->set_meta_prop($meta_key, $value);
			}
		}

		/**
		 * Get the post name with edit link.
		 * 
		 * @since 1.0.0
		 * @param false/string $name
		 * @return string
		 */
		public function get_post_name_with_edit_link( $name = false ) {
			if (!$name) {
				$name = $this->get_name();
			}

			return '<a href="' . $this->get_edit_post_link() . '" >' . $name . '</a>';
		}

		/**
		 * Get the edit post link.
		 * 
		 * @since 1.0.0
		 * @return string
		 */
		public function get_edit_post_link() {
			return get_edit_post_link($this->get_post());
		}
	}

}
