<?php

// A PHP implementation of PCSG - the ProCon Schedule Generation algorithm
// Written by Nat Budin

// For more information about PCSG, see:
// http://wiki.aegames.org/index.php/ProCon_schedule_generation_algorithm

class ScheduleBlock {
  public $startHour;
  public $endHour;
  public $interval;
  
  function __construct($startHour, $endHour) {
	$this->startHour = $startHour;
	$this->endHour = $endHour;
	$this->interval = 1;
  }
  
  function getHours() {
	return $this->endHour - $this->startHour;
  }
}

function pcsg_get_default_blocks() {
  return array(
	new ScheduleBlock(17, 24, "Fri"),
	new ScheduleBlock(10, 24, "Sat"),
	new ScheduleBlock(9, 13, "Sun")
  );
}

class EventRun {
  public $startHour;
  public $hours;
  public $id;
  
  function __construct($startHour, $hours, $id) {
    $this->startHour = $startHour;
    $this->hours = $hours;
    $this->id = $id;
  }
  
  function getStartHour() {
	return $this->startHour;
  }
  
  function getEndHour() {
	return $this->startHour + $this->hours;
  }
  
  function getHours() {
	return $this->hours;
  }
}

class RunDimensions {
  public $left;
  public $width;
  public $top;
  public $height;
  public $run;
  
  function __construct($run, $left, $width, $top, $height) {
	$this->run = $run;
	$this->left = $left;
	$this->width = $width;
	$this->top = $top;
	$this->height = $height;
  }
}

function pcsg_get_run_dimensions($block, $runs) {
  $grabbedColumns = array();
  $runDimensions = array();

  $upcomingRuns = array();
  foreach ($runs as $run) {
    $upcomingRuns[$run->id] = $run;
  }
  
  // loop through each hour
  $now = $block->startHour;
  while ($now < $block->endHour) {
	
	// which runs are happening now?
	$currentRuns = array();
	foreach ($runs as $run) {
	  if ($now >= $run->getStartHour() && $now < $run->getEndHour() ) {
		array_push($currentRuns, $run);
	  }
	}
	
	// have any of our column reservations expired?
	foreach (array_keys($grabbedColumns) as $colnum) {
	  if ($grabbedColumns[$colnum] <= $now) {
		unset($grabbedColumns[$colnum]);
	  }
	}
	
	// do we need to make any future column reservations?
	foreach ($currentRuns as $currentRun) {
	  $grabbedRunIds = array();
	  
	  foreach ($upcomingRuns as $upcomingRun) {
	    if ($upcomingRun->getStartHour() > $currentRun->getStartHour() &&
			$upcomingRun->getEndHour() < $currentRun->getEndHour()) {
		  
		  // find a free column number to grab
		  $colnumToGrab = 1;
		  while (array_key_exists($colnumToGrab, $grabbedColumns)) {
			$colnumToGrab += 1;
		  }
		  
		  // grab the column
		  $grabbedColumns[$colnumToGrab] = $upcomingRun->getStartHour();
		  array_push($grabbedRunIds, $upcomingRun->id);
		}
	  }
	  
	  // remove any runs we made reservations for from upcomingRuns
      foreach ($grabbedRunIds as $id) {
        unset($upcomingRuns[$id]);
      }
	}
	
	$eventSize = (100.0 / (count($currentRuns) + count($grabbedColumns)));
	$colnum = 0;
	foreach ($currentRuns as $currentRun) {
	  if (!(array_key_exists($currentRun->id, $runDimensions))) {
		while (array_key_exists($colnum, $grabbedColumns)) {
		  $colnum += 1;
		}
		
		$runDimensions[$currentRun->id] = new RunDimensions(
		  $currentRun,
		  ($eventSize * $colnum),
		  $eventSize,
		  (($run->startHour - $block->startHour) / $block->getHours()) * 100.0,
		  ($run->getHours() / $block->getHours()) * 100.0
		);
		
		$grabbedColumns[$colnum] = $run->getEndHour();
		$colnum += 1;
	  }
	}
	
	$now += $block->interval;
  }
  
  return $runDimensions;
}

?>