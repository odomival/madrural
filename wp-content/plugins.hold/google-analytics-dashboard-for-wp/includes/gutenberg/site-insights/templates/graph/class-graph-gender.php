<?php
/**
 * Class that handles the output for the New vs Returning graph.
 *
 * Class ExactMetrics_SiteInsights_Template_Graph_Gender
 */
class ExactMetrics_SiteInsights_Template_Graph_Gender extends ExactMetrics_SiteInsights_Metric_Template {

	protected $metric = 'gender';

	protected $type = 'graph';

	public function output(){
		$json_data = $this->get_json_data();

		if (empty($json_data)) {
			return false;
		}

		return "<div class='exactmetrics-graph-item exactmetrics-donut-chart exactmetrics-graph-{$this->metric}'>
			<script type='application/json'>{$json_data}</script>
		</div>";
	}

	protected function get_options() {
		if (empty($this->data['gender'])) {
			return false;
		}

		$primaryColor = $this->attributes['primaryColor'];
		$secondaryColor = $this->attributes['secondaryColor'];
		$textColor = $this->attributes['textColor'];
		$title = __( 'Gender Breakdown', 'google-analytics-dashboard-for-wp' );

		$data = $this->data['gender'];
		$series = array_column($data, 'percent');
		$labels = array_column($data, 'gender');

		$options = array(
			'series' => $series,
			'chart' => array(
				'height' => 'auto',
				'type' => 'donut',
			),
			'colors' => array( '#ebebeb', $primaryColor, $secondaryColor ),
			'title' => array(
				'text' => $title,
				'align' => 'left',
				'style' => array(
					'color' => $textColor,
					'fontSize' => '20px'
				)
			),
			'labels' => $labels,
			'legend' => array(
				'position' => 'right',
				'horizontalAlign' => 'center',
				'floating' => false,
				'fontSize' => '17px',
				'height' => '100%',
				'markers' => array(
					'width' => 30,
					'height' => 30,
					'radius' => 30,
				),
				'formatter' => array(
					'args' => 'seriesName, opts',
					'body' => 'return [seriesName, "<strong> " + opts.w.globals.series[opts.seriesIndex] + "%</strong>"];'
				)
			),
			'dataLabels' => array(
				'enabled' => false
			),
			'responsive' => array(
				0 => array(
					'breakpoint' => 767,
					'options' => array(
						'legend' => array(
							'show' => false
						)
					)
				)
			)
		);

		return $options;
	}
}