<?php
/**
 * Class that handles the output for the Scroll Depth graph.
 *
 * Class ExactMetrics_SiteInsights_Template_Graph_Scrolldepth
 */
class ExactMetrics_SiteInsights_Template_Graph_Scrolldepth extends ExactMetrics_SiteInsights_Metric_Template {

	protected $metric = 'scrolldepth';

	protected $type = 'graph';

	public function output(){
		$json_data = $this->get_json_data();

		if (empty($json_data)) {
			return false;
		}

		return "<div class='exactmetrics-graph-item exactmetrics-graph-{$this->metric}'>
			<script type='application/json'>{$json_data}</script>
		</div>";
	}

	protected function get_options() {
		if (!isset($this->data['scroll']) || empty($this->data['scroll']['average'])) {
			return false;
		}

		$primaryColor = $this->attributes['primaryColor'];
		$secondaryColor = $this->attributes['secondaryColor'];

		$value = $this->data['scroll']['average'];

		$title = __( 'Average Scroll Depth', 'google-analytics-dashboard-for-wp' );

		$options = array(
			'series' => array( $value ),
			'chart' => array(
				'height' => 350,
				'type' => 'radialBar',
			),
			'plotOptions' => array(
				'radialBar' => array(
					'size' => $value . '%',
				)
			),
			'colors' => array( $primaryColor, $secondaryColor ),
			'labels' => array( $title ),
		);

		return $options;
	}
}