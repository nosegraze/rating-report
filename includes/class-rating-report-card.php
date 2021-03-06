<?php

/**
 * Displays the Rating Report in a post
 *
 * @package   rating-report
 * @copyright Copyright (c) 2016, Nose Graze Ltd.
 * @license   GPL2+
 */
class Rating_Report_Card {

	/**
	 * The post ID - this is the post we fetch rating values from.
	 *
	 * @var int
	 * @access public
	 * @since  2.0
	 */
	public $ID = 0;

	/**
	 * Current post object
	 *
	 * @var array|null|WP_Post
	 * @access private
	 * @since  2.0
	 */
	private $post;

	/**
	 * Rating Report Settings
	 *
	 * @var array
	 * @access private
	 * @since  2.0
	 */
	private $settings;

	/**
	 * Card type ('graph' or 'table')
	 *
	 * @var string
	 * @access private
	 * @since  2.0
	 */
	private $type;

	/**
	 * Array of Categories
	 *
	 * @var array
	 * @access private
	 * @since  2.0
	 */
	private $categories;

	/**
	 * Ratings
	 *
	 * Compiled multi-dimensional array of category names, ratings, and descriptions.
	 *
	 * @var array
	 * @access private
	 * @since  2.0
	 */
	private $ratings;

	/**
	 * Numbers
	 *
	 * Array of rating numbers.
	 *
	 * @var array
	 * @access private
	 * @since  2.0
	 */
	private $numbers;

	/**
	 * Descriptions
	 *
	 * @var array
	 * @access private
	 * @since  2.0
	 */
	private $descriptions;

	/**
	 * Average Rating
	 *
	 * @var int|float
	 * @access private
	 * @since  2.0
	 */
	private $average_rating;

	/**
	 * Rating Type (numbers, graphics, FontAwesome)
	 *
	 * @var string
	 * @access private
	 * @since  2.0
	 */
	private $rating_type;

	/**
	 * Rating Scale
	 *
	 * @var array
	 * @access private
	 * @since  2.0
	 */
	private $rating_scale;

	/**
	 * Maximum Rating
	 *
	 * @var int
	 * @access private
	 * @since  2.0
	 */
	private $maximum_rating;

	/**
	 * Whether or not half stars are enabled
	 *
	 * @var bool
	 * @access private
	 * @since  2.0
	 */
	private $half_stars;

	/**
	 * Rating_Report_Card constructor.
	 *
	 * @param int    $post_id ID of the post to get rating values from
	 * @param string $type    Card type
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function __construct( $post_id = 0, $type = '' ) {

		$this->ID       = $post_id;
		$this->post     = get_post( $post_id );
		$this->settings = rating_report_get_settings();
		$this->set_type( $type );
		$this->set_categories( $this->get_setting( 'categories', rating_report_get_default_categories() ) );
		$this->rating_type    = $this->get_setting( 'rating_type', 'numbers' );
		$this->rating_scale   = rating_report_get_rating_scale();
		$this->half_stars     = $this->get_setting( 'half_stars', false );
		$this->maximum_rating = $this->get_setting( 'max_rating', 5 );

	}

	/**
	 * Get Setting
	 *
	 * Retrieves an option from the Rating Report settings.
	 *
	 * @param string $key
	 * @param bool   $default
	 *
	 * @access private
	 * @since  2.0
	 * @return bool|mixed
	 */
	private function get_setting( $key = '', $default = false ) {

		if ( ! is_array( $this->settings ) ) {
			return $default;
		}

		if ( ! array_key_exists( $key, $this->settings ) || empty( $this->settings[ $key ] ) ) {
			return $default;
		}

		return $this->settings[ $key ];

	}

	/**
	 * Get Type
	 *
	 * @access public
	 * @since  2.0
	 * @return string
	 */
	public function get_type() {

		return apply_filters( 'rating-report/card/get/type', $this->type );

	}

	/**
	 * Get Average Rating
	 *
	 * @access public
	 * @since  2.0
	 * @return int|float
	 */
	public function get_average_rating() {

		return apply_filters( 'rating-report/card/get/average_rating', $this->average_rating );

	}

	/**
	 * Get Formatted Average Rating
	 *
	 * Formats the average rating according to the chosen display type and returns that
	 * formatted HTML (or number). In the case of graphics and Font Awesome stars, the
	 * average rating needs to be rounded to the nearest whole number or half value (if
	 * half ratings are supported).
	 *
	 * @uses   get_average_rating()
	 *
	 * @access public
	 * @since  2.0
	 * @return string
	 */
	public function get_formatted_average_rating() {

		$average_rating = $this->get_average_rating();
		$display_type   = $this->get_setting( 'rating_type_overall', 'numbers' );

		if ( $display_type == 'numbers' ) {
			$rating_template = $this->get_setting( 'table_category_rating_template', __( '%s stars', 'rating-report' ) );
			$final_rating    = sprintf( $rating_template, $average_rating );
		} else {
			// First we need to round the number.
			$rounded_value = $this->half_stars ? ( floor( $average_rating * 2 ) / 2 ) : round( $average_rating );

			// Now we can get the rating.
			$final_rating = call_user_func( array( $this, 'get_' . $display_type . '_rating' ), $rounded_value );
		}

		return apply_filters( 'rating-report/card/get/formatted_average_rating', $final_rating );

	}

	/**
	 * Set Type
	 *
	 * If no type is entered then we use the default specified in the settings panel.
	 *
	 * @param string $type
	 *
	 * @access public
	 * @since  2.0
	 * @return string
	 */
	public function set_type( $type = '' ) {

		$type       = ! empty( $type ) ? $type : $this->get_setting( 'display_type', 'table' );
		$this->type = apply_filters( 'rating-report/card/set-type', $type );

		return $this->type;

	}

	/**
	 * Set Categories
	 *
	 * @param array $categories
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function set_categories( $categories = array() ) {

		$this->categories = $categories;

	}

	/**
	 * Get Rating
	 *
	 * Returns the display format for a single rating value.
	 *
	 * @param int|float $value Numerical rating value
	 *
	 * @access public
	 * @since  2.0
	 * @return string
	 */
	public function get_rating( $value ) {

		if ( $this->rating_type == 'numbers' ) {
			$rating_template = $this->get_setting( 'table_category_rating_template', __( '%s stars', 'rating-report' ) );
			$rating          = sprintf( $rating_template, $value );
		} else {
			$rating = call_user_func( array( $this, 'get_' . $this->rating_type . '_rating' ), $value );
		}

		return apply_filters( 'rating-report/card/get-rating', $rating, $this->rating_type, $this );

	}

	/**
	 * Get Graphical Stars
	 *
	 * @uses   Rating_Report_Card::create_star_rating()
	 *
	 * @param int|float $value Numerical rating value
	 *
	 * @access public
	 * @since  2.0
	 * @return string|bool False on failure
	 */
	public function get_images_rating( $value ) {

		$empty_star_id = $this->get_setting( 'empty_star' );
		$half_star_id  = $this->get_setting( 'half_star' );
		$full_star_id  = $this->get_setting( 'full_star' );

		if ( empty( $full_star_id ) ) {
			return false;
		}

		$empty_star_html = ! empty( $empty_star_id ) ? wp_get_attachment_image( $empty_star_id, 'full' ) : '';
		$half_star_html  = ! empty( $half_star_id ) ? wp_get_attachment_image( $half_star_id, 'full' ) : '';
		$full_star_html  = ! empty( $full_star_id ) ? wp_get_attachment_image( $full_star_id, 'full' ) : '';

		$final_rating = $this->create_star_rating( $value, $full_star_html, $half_star_html, $empty_star_html );

		return apply_filters( 'rating-report/card/image-rating', $final_rating, $value, $this );

	}

	/**
	 * Get Font Awesome Stars
	 *
	 * @uses   Rating_Report_Card::create_star_rating()
	 *
	 * @param int|float $value Numerical rating value
	 *
	 * @access public
	 * @since  2.0
	 * @return string
	 */
	public function get_font_awesome_rating( $value ) {

		$full_star_html  = '<i class="' . apply_filters( 'rating-report/card/font-awesome-full-star-icon', 'fa fa-star' ) . '"></i>';
		$half_star_html  = '<i class="' . apply_filters( 'rating-report/card/font-awesome-half-star-icon', 'fa fa-star-half-full' ) . '"></i>';
		$empty_star_html = apply_filters( 'rating-report/card/font-awesome-fill-up-empty', true ) ? '<i class="' . apply_filters( 'rating-report/card/font-awesome-empty-star-icon', 'fa fa-star-o' ) . '"></i>' : '';

		$final_rating = $this->create_star_rating( $value, $full_star_html, $half_star_html, $empty_star_html );

		return apply_filters( 'rating-report/card/font-awesome-rating', $final_rating, $value, $this );

	}

	/**
	 * Create Star Rating
	 *
	 * Repeats star strings a certain number of times based on the given rating.
	 *
	 * @see    wp_star_rating() Based on this function.
	 *
	 * @param int    $rating          Star rating in numerical form
	 * @param string $full_star_html  HTML to repeat for all full stars
	 * @param string $half_star_html  HTML to repeat for all half stars
	 * @param string $empty_star_html HTML to repeat until we reach maximum rating
	 *
	 * @access public
	 * @since  2.0
	 * @return string
	 */
	public function create_star_rating( $rating = 5, $full_star_html = '', $half_star_html = '', $empty_star_html = '' ) {
		// Calculate the number of each type of star needed
		$full_stars  = floor( $rating );
		$half_stars  = ceil( $rating - $full_stars );
		$empty_stars = 5 - $full_stars - $half_stars;

		// Create screen reader text.
		$output = '<span class="screen-reader-text">' . sprintf( __( '%s star rating', 'rating-report' ), $rating ) . '</span>';

		$output .= str_repeat( $full_star_html, $full_stars );
		$output .= str_repeat( $half_star_html, $half_stars );
		$output .= str_repeat( $empty_star_html, $empty_stars );

		return $output;
	}

	/**
	 * Set Numbers
	 *
	 * @param array $numbers
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function set_numbers( $numbers = array() ) {
		$this->numbers = $numbers;
	}

	/**
	 * Set Descriptions
	 *
	 * @param array $descriptions
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function set_descriptions( $descriptions = array() ) {
		$this->descriptions = $descriptions;
	}

	/**
	 * Set Ratings
	 *
	 * Leave the $ratings parameter blank to auto fetch the ratings from the post meta.
	 * This parameter exists so you can manually pass in an array of ratings for some reason.
	 * i.e. : $ratings = array( 1, 5, 2, 6 )
	 *
	 * @access public
	 * @since  2.0
	 * @return array
	 */
	public function set_ratings() {

		if ( ! isset( $this->numbers ) || ! is_array( $this->numbers ) ) {
			$numbers       = get_post_meta( $this->ID, 'rating_report', true );
			$numbers       = is_array( $numbers ) ? $numbers : array();
			$this->numbers = apply_filters( 'rating-report/card/numbers', $numbers, $this );
		}

		if ( ! isset( $this->descriptions ) || ! is_array( $this->descriptions ) ) {
			$descriptions       = get_post_meta( $this->ID, 'rating_report_descriptions', true );
			$descriptions       = is_array( $descriptions ) ? $descriptions : array();
			$this->descriptions = apply_filters( 'rating-report/card/descriptions', $descriptions, $this );
		}

		$final_array   = array();
		$total_ratings = 0;

		foreach ( $this->categories as $key => $name ) {
			if ( ! array_key_exists( $key, $this->numbers ) ) {
				continue;
			}

			$final_array[] = array(
				'category'    => esc_html( $name ),
				'rating'      => $this->numbers[ $key ],
				'description' => array_key_exists( $key, $this->descriptions ) ? $this->descriptions[ $key ] : ''
			);

			$total_ratings += $this->numbers[ $key ];
		}

		$this->ratings = apply_filters( 'rating-report/card/set-ratings', $final_array, $this->numbers, $this->categories );

		// Figure out the average rating.
		$average_rating       = $total_ratings / count( $this->ratings );
		$this->average_rating = apply_filters( 'rating-report/card/set-average-rating', round( $average_rating, 2 ), $total_ratings, $this->ratings, $this );

		return $this->ratings;

	}

	/**
	 * Render Rating Report
	 *
	 * @access public
	 * @since  2.0
	 * @return string
	 */
	public function render() {

		ob_start();
		call_user_func( array( $this, 'render_' . $this->type ) );

		return apply_filters( 'rating-report/card/render', ob_get_clean(), $this->type, $this );

	}

	/**
	 * Render Table Report
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function render_table() {

		?>
		<table class="rating-report rating-report-table">
			<thead>
			<tr>
				<th colspan="2">
					<?php echo esc_html( $this->get_setting( 'table_title', __( 'Rating Report', 'rating-report' ) ) ); ?>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->ratings as $key => $value ) : ?>
				<?php
				$rating_class = str_replace( '.', '-', $value['rating'] );
				?>
				<tr>
					<td class="rating-report-category">
						<div class="rating-report-category-name">
							<?php echo esc_html( $value['category'] ); ?>
						</div>
						<?php if ( ! empty( $value['description'] ) ) : ?>
							<div class="rating-report-category-description">
								<?php echo wpautop( $value['description'] ); ?>
							</div>
						<?php endif; ?>
					</td>
					<td class="rating-report-rating rating-report-rating-<?php echo sanitize_html_class( $rating_class ); ?>">
						<?php echo $this->get_rating( $value['rating'] ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr class="rating-report-overall">
				<td class="rating-report-overall-label">
					<?php echo esc_html( $this->get_setting( 'table_overall_label', __( 'Overall', 'rating-report' ) ) ); ?>
				</td>
				<td class="rating-report-overall-rating">
					<?php echo $this->get_formatted_average_rating(); ?>
				</td>
			</tr>
			</tfoot>
		</table>
		<?php

	}

	/**
	 * Render Horzontal Graph
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function render_horizontal_graph() {

		$show_numbers = $this->get_setting( 'show_numbers', false );
		?>
		<div class="rating-report rating-report-horizontal-graph">
			<?php foreach ( $this->ratings as $key => $value ) :
				$percentage = round( ( $value['rating'] / $this->maximum_rating ) * 100 );
				?>
				<div class="rating-report-category rating-report-category-<?php echo strtolower( sanitize_html_class( $value['category'] ) ); ?>">
					<div class="rating-report-category-heading">
						<div class="rating-report-category-name">
							<?php echo esc_html( sprintf( '%s - %s/%s', $value['category'], $value['rating'], $this->maximum_rating ) ); ?>
						</div>

						<?php if ( ! empty( $value['description'] ) ) : ?>
							<div class="rating-report-category-description">
								<?php echo wpautop( $value['description'] ); ?>
							</div>
						<?php endif; ?>
					</div>
					<div class="rating-report-bar-wrap">
						<div class="rating-report-bar" style="width: <?php echo absint( $percentage ); ?>%;"></div>
					</div>
					<?php if ( $show_numbers ) : ?>
						<span class="rating-report-bar-number"><?php echo esc_html( $value['rating'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php

	}

	/**
	 * Render Vertical Graph
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function render_vertical_graph() {

		$show_numbers = $this->get_setting( 'show_numbers', false );
		?>
		<div class="rating-report rating-report-vertical-graph rating-report-graph-values">
			<?php foreach ( $this->ratings as $key => $value ) :
				$percentage = round( ( $value['rating'] / $this->maximum_rating ) * 100 );
				?>
				<div class="rating-report-category rating-report-category-<?php echo strtolower( sanitize_html_class( $value['category'] ) ); ?>">
					<div class="rating-report-bar" style="height: <?php echo absint( $percentage ); ?>%;"></div>
					<?php if ( $show_numbers ) : ?>
						<span class="rating-report-bar-number"><?php echo esc_html( $value['rating'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="rating-report rating-report-vertical-graph rating-report-graph-labels">
			<?php foreach ( $this->ratings as $key => $value ) : ?>
				<div class="rating-report-category rating-report-category-<?php echo strtolower( sanitize_html_class( $value['category'] ) ); ?>">
					<div class="rating-report-category-name">
						<?php echo esc_html( $value['category'] ); ?>
					</div>

					<?php if ( ! empty( $value['description'] ) ) : ?>
						<div class="rating-report-category-description">
							<?php echo wpautop( $value['description'] ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php

	}

	/**
	 * Render Graph Report
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function render_graph() {

		$show_numbers = $this->get_setting( 'show_numbers', false );
		?>
		<div class="rating-report rating-report-graph rating-report-graph-values">
			<?php foreach ( $this->ratings as $key => $value ) :
				$percentage = round( ( $value['rating'] / $this->maximum_rating ) * 100 );
				?>
				<div class="rating-report-category rating-report-category-<?php echo strtolower( sanitize_html_class( $value['category'] ) ); ?>">
					<div class="rating-report-bar" style="height: <?php echo absint( $percentage ); ?>%;"></div>
					<?php if ( $show_numbers ) : ?>
						<span class="rating-report-bar-number"><?php echo esc_html( $value['rating'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="rating-report rating-report-graph rating-report-graph-labels">
			<?php foreach ( $this->ratings as $key => $value ) : ?>
				<div class="rating-report-category rating-report-category-<?php echo strtolower( sanitize_html_class( $value['category'] ) ); ?>">
					<div class="rating-report-category-name">
						<?php echo esc_html( $value['category'] ); ?>
					</div>

					<?php if ( ! empty( $value['description'] ) ) : ?>
						<div class="rating-report-category-description">
							<?php echo wpautop( $value['description'] ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php

	}

}