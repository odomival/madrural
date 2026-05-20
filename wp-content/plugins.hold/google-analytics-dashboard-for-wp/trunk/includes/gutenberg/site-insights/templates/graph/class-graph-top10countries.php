<?php
/**
 * Class that handles the output for the Top 10 Countries graph.
 *
 * Class ExactMetrics_SiteInsights_Template_Graph_Top10countries
 */
class ExactMetrics_SiteInsights_Template_Graph_Top10countries extends ExactMetrics_SiteInsights_Metric_Template {

	protected $metric = 'top10countries';

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
		if (empty($this->data['countries'])) {
			return false;
		}

		$primaryColor = $this->attributes['primaryColor'];
		$secondaryColor = $this->attributes['secondaryColor'];
		$textColor = $this->attributes['textColor'];

		$data = $this->data['countries'];

		$title = __( 'Top 10 countries', 'google-analytics-dashboard-for-wp' );
		$series = array();
		$labels = array_column($data, 'name');

		foreach ($data as $key => $country) {
			$series[$key] = (int) $country['sessions'];
		}

		$options = array(
			'series' => array(
				array(
					'name' => $title,
					'data' => $series,
				)
			),
			'chart' => array(
				'height' => 430,
				'type' => 'bar',
				'zoom' => array( 'enabled' => false ),
				'toolbar' => array( 'show' => false )
			),
			'dataLabels' => array(
				'enabled' => true,
				'style' => array(
					'fontSize' => '12px',
					'colors' => array( $textColor )
				)
			),
			'colors' => array( $primaryColor, $secondaryColor ),
			'title' => array(
				'text' => $title,
				'align' => 'left',
				'style' => array(
					'color' => $textColor,
					'fontSize' => '20px'
				)
			),
			'plotOptions' => array(
				'bar' => array(
					'horizontal' => true,
					'borderRadius' => 5,
					'borderRadiusApplication' => 'end',
					'dataLabels' => array(
						'position' => 'center',
					)
				)
			),
			'xaxis' => array(
				'categories' => $labels,
			)
		);

		return $options;
	}
}