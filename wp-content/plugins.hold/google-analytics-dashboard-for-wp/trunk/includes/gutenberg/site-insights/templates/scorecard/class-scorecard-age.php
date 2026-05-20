<?php
/**
 * Class that handles the output for the Age Breakdown scorecard.
 *
 * Class ExactMetrics_SiteInsights_Template_Scorecard_Age
 */
class ExactMetrics_SiteInsights_Template_Scorecard_Age extends ExactMetrics_SiteInsights_Metric_Template {

	protected $metric = 'age';

	protected $type = 'scorecard';

	public function output(){
		$data = $this->get_options();

		if (empty($data)) {
			return false;
		}

		$content = $this->get_table_template(
			$data['headers'],
			$data['rows']
		);

		return sprintf(
			"<div class=\"exactmetrics-table-scorecard with-3-columns\">%s</div>",
			$content
		);
	}

	/**
	 * Returns data needed for this block.
	 *
	 * @return array|false
	 */
	protected function get_options() {
		if (empty($this->data['age'])) {
			return false;
		}

		$primaryColor = $this->attributes['primaryColor'];
		$secondaryColor = $this->attributes['secondaryColor'];

		$data = $this->data['age'];

		$rows = array();

		foreach ($data as $key => $item) {
			$rows[$key] = array(
				$item['age'],
				$item['sessions'],
				$item['percent'] . '%'
			);
		}

		return array(
			'rows' => $rows,
			'headers' => array(
				__( 'Age Range', 'google-analytics-dashboard-for-wp' ),
				__( 'Sessions', 'google-analytics-dashboard-for-wp' ),
				__( 'Percent', 'google-analytics-dashboard-for-wp' )
			),
		);
	}

	private function get_table_template( $headers, $rows ) {
		$headers_output = '';
		$countries_output = '';

		foreach ( $headers as $key => $head ) {
			$headers_output .= sprintf( '<div class="exactmetrics-scorecard-table-head">%s</div>', $head );
		}

		foreach ( $rows as $key => $row ) {
			$items = '';

			foreach ( $row as $i => $column ) {
				$items .= sprintf( '<div class="exactmetrics-scorecard-table-column">%s</div>', $column );
			}

			$countries_output .= sprintf( '<div class="exactmetrics-scorecard-table-row">%s</div>', $items );
		}

		$header = sprintf(
			'<div class="exactmetrics-scorecard-table-header">%s</div>',
			$headers_output
		);

		$content = sprintf(
			'<div class="exactmetrics-scorecard-table-rows">%s</div>',
			$countries_output
		);

		$format = '<div class="exactmetrics-scorecard-table">%s</div>';

		return sprintf(
			$format,
			$header . $content
		);
	}
}