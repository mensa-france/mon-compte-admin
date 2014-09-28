<?php

namespace MonCompte;

class StopWatch {
	private $startTime;

	public function start() {
		$this->startTime = microtime(true);
	}

	public function getElapsedTime($fullPrecision=false) {
		$endTime = microtime(true);

		$duration = $endTime - $this->startTime;

		if (!$fullPrecision)
			$duration = floor($duration*1000)/1000; // Only keep ms precision.

		return $duration;
	}
}
