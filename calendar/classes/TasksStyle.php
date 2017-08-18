<?php

/**
* Get css for tasks position
* @param $tasks - array with day tasks
* @param $mode day / week
*/
class Calendar_TasksStyle
{	
    public static $_tasksInLine;
    public static $_line = array();
	public static $_bottom = 0;
    public static $_tasks;
    public static $_mode;

	public static function add($tasks, $mode = 'day')
	{
		if (isset($tasks) && !empty($tasks)) {
    		self::$_tasks = $tasks;
    		self::$_mode = $mode;

    		foreach (self::$_tasks as $task_id => &$t) {

				$_date = new Zend_Date($t['start_date'], 'yyy-MM-dd HH:mm:ss');
		        $top = $_date->toString('HH') * 60 + $_date->toString('mm');

		        $_date->set($t['end_date']);
		        $bottom = $_date->toString('HH') * 60 + $_date->toString('mm');

		        
		        if ($top > self::$_bottom) {
		        	$_date->set($t['end_date']);
		            self::$_bottom = $_date->toString('HH') * 60 + $_date->toString('mm');
		            self::getLeftWidth();
		        } 

		        self::$_line[] = $task_id;

		        $t['position'] = array(
						'top'        => $top,
	                    'min-height' => $bottom - $top,
		        	);
		    }
		    if (count(self::$_line) > 0) {
		    	self::getLeftWidth();
		    }
		    return self::$_tasks;
		}		
	}

	protected function getLeftWidth()
	{
		self::$_tasksInLine = count(self::$_line);

		foreach (self::$_line as $i => $l) {
			if (self::$_mode == 'day') {
				$left = $i > 0 ? 100 * $i / self::$_tasksInLine : 0;
				$width = self::$_tasksInLine > 1 ? 100 / self::$_tasksInLine : 100;
			} else {
				$left = $i > 0 ? 100 * $i / self::$_tasksInLine * 0.5 : 0;
				$width = self::$_tasksInLine > 1 ? 80 : 100;
			}
			self::$_tasks[$l]['position']['left'] = $left;
			self::$_tasks[$l]['position']['width'] = $width;
			self::$_tasks[$l]['position']['bg'] = self::getBackground();
		}

		self::$_line = array();
	}

	public function getBackground() 
	{
		$colors = array('#ffb9b9','#e4ffb9','#b9dcff','#bcb9ff','#e7b9ff','#c0ffb9','#ffe1b9','#ffefb9');
		return $colors[mt_rand(0, 7)];
	}

	public static function getFullDayTable($fullDayTasks) 
	{
		if (isset($fullDayTasks) && !empty($fullDayTasks)) {
    		self::$_tasks = $fullDayTasks;

    		foreach (self::$_tasks as $day => $tasks) {
    			foreach ($tasks as $task_id => $t) {
	    			$_start_date = new Zend_Date($t['start_date'], 'yyy-MM-dd HH:mm:ss');
					$_end_date = new Zend_Date($t['end_date'], 'yyy-MM-dd HH:mm:ss');

					$sub = $_end_date->toString('eee') - $_start_date->toString('eee');

					self::FindFullDayTaskPosition($_start_date->toString('eee'), ++$sub, $task_id);
    			}
    		}

    		foreach (self::$_line as $line => &$weekdays) {
    			for ($i = 1; $i <= 7; $i++) { 
    				if (!isset($weekdays[$i])) {
    					$countColwSpan = self::countColSpan($i, $line);
    					if ($countColwSpan > 1) {
    						$weekdays[$i] = array('colspan' => $countColwSpan);
    					}
    				}
    			}
    			ksort($weekdays);
    		}
    		unset($t, $weekdays);
			return self::$_line;
    	}
	}

	protected function FindFullDayTaskPosition($weekday, $cellQty, $task_id, $line = 1) 
	{
		if (isset(self::$_line[$line][$weekday])) {
			self::FindFullDayTaskPosition($weekday, $cellQty, $task_id, ++$line);
		} else {
			self::$_line[$line][$weekday] = array(
					'task_id' => $task_id,
					'colspan' => $cellQty,
					'bg'	  => self::getBackground()
				);
			for ($i = 1; $i < $cellQty; $i++) { 
				self::$_line[$line][++$weekday] = '-';
			}
			return $line;
		}
	}

	protected function countColSpan($weekday, $line, $qty = 0)
	{
		if ($weekday <= 7) {
			if (!isset(self::$_line[$line][$weekday])) {
				self::$_line[$line][$weekday] = '-';
				$qty = self::countColSpan(++$weekday, $line, ++$qty);
			} 
		} 
		return $qty;
	}
}