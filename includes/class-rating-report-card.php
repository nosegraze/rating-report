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
	 * @since  1.0
	 */
	public $ID = 0;

	/**
	 * Current post object
	 *
	 * @var array|null|WP_Post
	 * @access private
	 * @since  1.0
	 */
	private $post;

	/**
	 * Card type ('graph' or 'table')
	 *
	 * @var string
	 * @access private
	 * @since  1.0
	 */
	private $type;

	/**
	 * Ratings
	 *
	 * @var array
	 * @access private
	 * @since  1.0
	 */
	private $ratings;

	/**
	 * Average Rating
	 *
	 * @var int|float
	 * @access private
	 * @since  1.0
	 */
	private $average_rating;

	/**
	 * Rating Type (numbers, graphics, FontAwesome)
	 *
	 * @var string
	 * @access private
	 * @since  1.0
	 */
	private $rating_type;

	/**
	 * Rating Scale
	 *
	 * @var array
	 * @access private
	 * @since  1.0
	 */
	private $rating_scale;

	private $maximum_rating;

	/**
	 * Whether or not half stars are enabled
	 *
	 * @var bool
	 * @access private
	 * @since  1.0
	 */
	private $half_stars;

	/**
	 * Rating_Report_Card constructor.
	 *
	 * @param int    $post_id ID of the post to get rating values from
	 * @param string $type    Card type
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct( $post_id = 0, $type = '' ) {

		$this->ID   = $post_id;
		$this->post = get_post( $post_id );
		$this->set_type( $type );
		$this->rating_type    = rating_report_get_option( 'rating_type', 'numbers' );
		$this->rating_scale   = rating_report_get_rating_scale();
		$this->half_stars     = rating_report_get_option( 'half_stars', false );
		$this->maximum_rating = rating_report_get_option( 'max_rating', 5 );

	}

	/**
	 * Get Type
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_type() {

		return apply_filters( 'rating-report/card/get/type', $this->type );

	}

	/**
	 * Get Average Rating
	 *
	 * @access public
	 * @since  1.0
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
	 * @since  1.0
	 * @return string
	 */
	public function get_formatted_average_rating() {

		$average_rating = $this->get_average_rating();
		$display_type   = rating_report_get_option( 'rating_type_overall', 'numbers' );

		if ( $display_type == 'numbers' ) {
			$final_rating = $average_rating;
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
	 * @since  1.0
	 * @return string
	 */
	public function set_type( $type = '' ) {

		$type       = ! empty( $type ) ? $type : rating_report_get_option( 'display_type', 'table' );
		$this->type = apply_filters( 'rating-report/card/set-type', $type );

		return $this->type;

	}

	/**
	 * Get Rating
	 *
	 * Returns the display format for a single rating value.
	 *
	 * @param int|float $value Numerical rating value
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_rating( $value ) {

		if ( $this->rating_type == 'numbers' ) {
			$rating = $value;
		} else {
			$rating = call_user_func( array( $this, 'get_' . $this->rating_type . '_rating' ), $value );
		}

		return apply_filters( 'rating-report/card/get-rating', $rating, $this->rating_type, $this );

	}

	/**
	 * Get Graphical Stars
	 *
	 * @todo
	 *
	 * @param int|float $value Numerical rating value
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_images_rating( $value ) {
		//@todo
	}

	/**
	 * Get Font Awesome Stars
	 *
	 * @param int|float $value Numerical rating value
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function get_font_awesome_rating( $value ) {

		$final_rating = '';

		$i = $value;

		if ( $i >= 1 ) {
			for ( $i; $i >= 1; $i -- ) {

				$final_rating .= '<i class="' . apply_filters( 'rating-report/card/font-awesome-full-star-icon', 'fa fa-star' ) . '"></i>';

				//this is a decimal and we're on the last one, so display a half star
				if ( (float) $i == 1.5 ) {
					$final_rating .= '<i class="' . apply_filters( 'rating-report/card/font-awesome-half-star-icon', 'fa fa-star-half-full' ) . '"></i>';
				}
			}
		} elseif ( $i > 0 ) {
			$final_rating .= '<i class="' . apply_filters( 'rating-report/card/font-awesome-half-star-icon', 'fa fa-star-half-full' ) . '"></i>';
		}

		// Now add the empty stars to fill up to our max.
		if ( apply_filters( 'rating-report/card/font-awesome-fill-up-empty', true ) ) {
			$empty_stars_needed = $this->maximum_rating - $value;
			if ( $empty_stars_needed >= 1 ) {
				for ( $j = $empty_stars_needed; $j >= 1; $j -- ) {

					$final_rating .= '<i class="' . apply_filters( 'rating-report/card/font-awesome-empty-star-icon', 'fa fa-star-o' ) . '"></i>';
				}
			}
		}

		return apply_filters( 'rating-report/card/font-awesome-rating', $final_rating, $value, $this );

	}

	/**
	 * Set Ratings
	 *
	 * Leave the $ratings parameter blank to auto fetch the ratings from the post meta.
	 * This parameter exists so you can manually pass in an array of ratings for some reason.
	 * i.e. : $ratings = array( 1, 5, 2, 6 )
	 *
	 * @param null|array $ratings
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function set_ratings( $ratings = null ) {

		if ( empty( $ratings ) ) {
			$ratings = get_post_meta( $this->ID, 'rating_report', true );
			$ratings = is_array( $ratings ) ? $ratings : array();
		}

		$categories = rating_report_get_option( 'categories', rating_report_get_default_categories() );

		$final_array   = array();
		$total_ratings = 0;

		foreach ( $categories as $key => $name ) {
			if ( ! array_key_exists( $key, $ratings ) ) {
				continue;
			}

			$final_array[] = array(
				'category' => esc_html( $name ),
				'rating'   => $ratings[ $key ]
			);

			$total_ratings += $ratings[ $key ];
		}

		$this->ratings = apply_filters( 'rating-report/card/set-ratings', $final_array, $ratings, $categories );

		// Figure out the average rating.
		$average_rating       = $total_ratings / count( $this->ratings );
		$this->average_rating = apply_filters( 'rating-report/card/set-average-rating', round( $average_rating, 2 ), $total_ratings, $this->ratings, $this );

		return $this->ratings;

	}

	/**
	 * Render Rating Report
	 *
	 * @access public
	 * @since  1.0
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
	 * @since  1.0
	 * @return void
	 */
	public function render_table() {

		?>
		<table class="rating-report rating-report-table">
			<thead>
			<tr>
				<th colspan="2">
					<?php echo esc_html( rating_report_get_option( 'table_title', __( 'Rating Report', 'rating-report' ) ) ); ?>
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
						<?php echo esc_html( $value['category'] ); ?>
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
					<?php echo esc_html( rating_report_get_option( 'table_overall_label', __( 'Overall', 'rating-report' ) ) ); ?>
				</td>
				<td class="rating-report-overall-rating">
					<?php echo $this->get_formatted_average_rating(); ?>
				</td>
			</tr>
			</tfoot>
		</table>
		<?php

	}

	public function render_graph() {

	}

}