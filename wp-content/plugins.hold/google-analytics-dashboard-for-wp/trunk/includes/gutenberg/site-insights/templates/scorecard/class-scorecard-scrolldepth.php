<?php
/**
 * Class that handles the output for the Scroll Depth scorecard.
 *
 * Class ExactMetrics_SiteInsights_Template_Scorecard_Scrolldepth
 */
class ExactMetrics_SiteInsights_Template_Scorecard_Scrolldepth extends ExactMetrics_SiteInsights_Metric_Template {

	protected $metric = 'scrolldepth';

	protected $type = 'scorecard';

	public function output(){
		$value = $this->get_options();

		if ( empty($value) ) {
			return null;
		}

		$format = '<div class="exactmetrics-scorecard-simple-card">
			<div class="exactmetrics-scorecard-simple-card-value">%2$s</div>
			<div class="exactmetrics-scorecard-simple-card-label">%1$s</div>
		</div>';

		return sprintf(
			$format,
			__( 'Average Scroll Depth', 'google-analytics-dashboard-for-wp' ),
			$value . '%'
		);
	}

	/**
	 * Returns data needed for this block.
	 *
	 * @return array|false
	 */
	protected function get_options() {
		if ( !isset($this->data['scroll']) || empty($this->data['scroll']['average'])) {
			return false;
		}

		$primaryColor = $this->attributes['primaryColor'];
		$secondaryColor = $this->attributes['secondaryColor'];

		$data = $this->data['scroll'];

		return $data['average'];
	}
}