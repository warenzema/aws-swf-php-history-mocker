<?php

return array (
	'events'=>
	array (
		0=>
		array (
			'eventId'=>1,
			'eventTimestamp'=>1427393547.1470001,
			'eventType'=>'WorkflowExecutionStarted',
			'workflowExecutionStartedEventAttributes'=>
			array (
				'childPolicy'=>'TERMINATE',
				'executionStartToCloseTimeout'=>'30',
				'input'=>'{"json":"i am json"}',
				'parentInitiatedEventId'=>0,
				'taskList'=>
				array (
					'name'=>'test',
				),
				'taskStartToCloseTimeout'=>'30',
				'workflowType'=>
				array (
					'name'=>'TestDecisionTriggers',
					'version'=>'1',
				),
			),
		),
		1=>
		array (
			'decisionTaskScheduledEventAttributes'=>
			array (
				'startToCloseTimeout'=>'30',
				'taskList'=>
				array (
					'name'=>'test',
				),
			),
			'eventId'=>2,
			'eventTimestamp'=>1427393547.1470001,
			'eventType'=>'DecisionTaskScheduled',
		),
		2=>
		array (
			'eventId'=>3,
			'eventTimestamp'=>1427393577.1530001,
			'eventType'=>'WorkflowExecutionTimedOut',
			'workflowExecutionTimedOutEventAttributes'=>
			array (
				'childPolicy'=>'TERMINATE',
				'timeoutType'=>'START_TO_CLOSE',
			),
		),
	),
);