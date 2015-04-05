<?php

use SwfHistoryMocker\HistoryBuilder;
use SwfHistoryMockerTests\Unit\SwfUnitTestCase;
use SwfHistoryMocker\DesiredEvent;
use SwfHistoryMocker\traits\ValidSwfEventTypes;
use SwfHistoryMocker\traits\EventToEventReferences;

class HistoryBuilderTest extends SwfUnitTestCase
{
	use ValidSwfEventTypes;
	use EventToEventReferences;
	
	/**
	 * @group setEventHistory()
	 * @group getEventHistory()
	 * @testdox setEventHistory() sets history from SWF array
	 */
	
	public function testCanSetAndGetInitialEventHistory()
	{
		$HistoryBuilder = new HistoryBuilder();
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskScheduled');
		$this->assertGetReturnsSet($HistoryBuilder, 'eventHistory', $history);
	}
	
	/**
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>ex if eventType not set
	 */
	
	public function testToUseDesiredEventMustHaveEventTypeSet()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$HistoryBuilder = new HistoryBuilder();
		$DesiredEvent = new DesiredEvent();
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
	}
	
	/**
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent() adds desired event to empty history
	 */
	
	public function testToUseCanAddDesiredEventToEmptyEventHistory()
	{
		$HistoryBuilder = new HistoryBuilder();
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('WorkflowExecutionStarted');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
	}
	
	/**
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent() adds desired event to existing history
	 */
	
	public function testOriginalHistoryIsUnchangedIfNewDesiredEventAdded()
	{
		$HistoryBuilder = new HistoryBuilder();
		$originalHistory = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskScheduled');
		$HistoryBuilder->setEventHistory($originalHistory);
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('DecisionTaskStarted');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		$newHistory = $HistoryBuilder->getEventHistory();
		
		array_pop($newHistory);
		//old history unchanged
		$this->assertEquals($originalHistory,$newHistory);
	}
	
	/**
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent() does not alter preceding history
	 */
	
	public function testCanAddDesiredEventToExistingHistory()
	{
		$HistoryBuilder = new HistoryBuilder();
		$originalHistory = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskScheduled');
		$HistoryBuilder->setEventHistory($originalHistory);
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('DecisionTaskStarted');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
	}
	
	private $eventTypesThatReferenceDecisionTaskCompletedEventId = [
		'WorkflowExecutionCompleted',
		'CompleteWorkflowExecutionFailed',
		'WorkflowExecutionFailed',
		'FailWorkflowExecutionFailed',
		'WorkflowExecutionCanceled',
		'CancelWorkflowExecutionFailed',
		'WorkflowExecutionContinuedAsNew',
		'ContinueAsNewWorkflowExecutionFailed',
		'ActivityTaskScheduled',
		'ActivityTaskCancelRequested',
		'MarkerRecorded',
		'RecordMarkerFailed',
		'TimerStarted',
		'TimerCanceled',
		'SignalExternalWorkflowExecutionInitiated',
		'SignalExternalWorkflowExecutionFailed',
		'RequestCancelExternalWorkflowExecutionInitiated',
		'RequestCancelExternalWorkflowExecutionFailed',
		'ScheduleActivityTaskFailed',
		'RequestCancelActivityTaskFailed',
		'StartTimerFailed',
		'CancelTimerFailed',
		'StartChildWorkflowExecutionFailed',
	];
	
	public function providerEventsThatReferenceOnlyDecisionTaskCompleted()
	{
		$historyAndAssertableEvents = array();
	
		foreach ($this->eventsThatRequireOnlyDecisionTaskCompleted() as
			$eventType
		) {
			$originalHistory = $this->returnMinimumHistoryWithEventType(
				$eventType,'any-id');
			//remove event we want to test
			array_pop($originalHistory);
	
			$historyAndAssertableEvents[] = [
				$originalHistory,
				[
					[
						'eventType'=>$eventType,
						'contextId'=>'any-id',
						'eventAttributes'=>['signalName'=>'anything'],
						'assertEventAttributes'=>[
							'decisionTaskCompletedEventId'=>4
						]
					]
				]
			];
		}
	
		//add another single test for when multiple decision tasks complete
		$originalHistory = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
	
		//5
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('DecisionTaskScheduled');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
	
		//6
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('DecisionTaskStarted');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
	
		//7 <-- want to reference this one
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('DecisionTaskCompleted');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$history = $HistoryBuilder->getEventHistory();
		$latestEvent = array_pop($history);
		$this->assertEquals(7,$latestEvent['eventId']);
	
		$historyAndAssertableEvents[] = [
			$HistoryBuilder->getEventHistory(),
			[
				[
					'eventType'=>'ActivityTaskScheduled',
					'contextId'=>'any-id',
					'assertEventAttributes'
						=>['decisionTaskCompletedEventId'=>7]
				]
			]
		];
			
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerEventsThatReferenceOnlyDecisionTaskCompleted
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent() sets decisionTaskCompleted ID for events
	 */
	
	public function testEventsReferenceDecisionTaskCompletedEvent(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate,true);
		}
	}
	
	public function providerEventsThatReferenceDTCAndAnotherEvent()
	{
		$eventTypes = [
			'StartChildWorkflowExecutionFailed'
				=>'StartChildWorkflowExecutionInitiated',
			'SignalExternalWorkflowExecutionFailed'
				=>'SignalExternalWorkflowExecutionInitiated',
			'RequestCancelExternalWorkflowExecutionFailed'
				=>'RequestCancelExternalWorkflowExecutionInitiated',
		];
		
		$contextId1 = 'id1';
		$signalName = 'signal1';
		$DecisionTaskCompletedHistory
			= $this->returnMinimumHistoryWithEventType(
				'DecisionTaskCompleted');
		
		//must have contextId
		$historyAndAssertableEvents = array();
		
		foreach ($eventTypes as $failingEventType => $parentEventType) {
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				[
					[
						'eventType'=>$parentEventType,
						'contextId'=>$contextId1,
						'eventAttributes'=>['signalName'=>$signalName],
						'assertEventId'=>5,
						'assertEventAttributes'=>[
							'decisionTaskCompletedEventId'=>4,
						]
					],
					[
						'eventType'=>$failingEventType,
						'contextId'=>$contextId1,
						'eventAttributes'=>['signalName'=>$signalName],
						'assertEventAttributes'=>[
							'initiatedEventId'=>5,
							'decisionTaskCompletedEventId'=>4,
						]
					]
				]
			];
		}
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerEventsThatReferenceDTCAndAnotherEvent
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent() sets decisionTaskCompleted ID for events
	 */
	
	public function testEventsReferenceDecisionTaskCompletedAndAnotherEvent(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate,true);
		}
	}
	
	public function providerEventsReferencingDTCButNotHavingDTC()
	{
		$history = $this->returnHistoryWithOnlyFirstEvent();
		$historyAndAssertableEvents = [];
		//verify that events without a decision first throw an exception
		foreach ($this
			->eventsThatRequireOnlyDecisionTaskCompleted() as
			$eventType
		) {
			$historyAndAssertableEvents[] = [
				$history,
				[
					[
						'eventType'=>$eventType,
						'contextId'=>'any-id',
						'eventAttributes'=>['signalName'=>'anything'],
						'expectedException'=>'\InvalidArgumentException',
					]
				]
			];
		}
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerEventsReferencingDTCButNotHavingDTC
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>ex if event requires DTC but no DTC exists
	 */
	
	public function testEventsReferenceDecisionTaskCompletedEventButDontHave(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate,true);
		}
	}
	
	public function providerDecisionTaskEventReferences()
	{
		$historyAndAssertableEvents = array();
		//reference the scheduled event
		$historyAndAssertableEvents[] = [
			$this->returnMinimumHistoryWithEventType('DecisionTaskScheduled'),
			[
				[
					'eventType'=>'DecisionTaskStarted',
					'assertEventAttributes'
						=>array('scheduledEventId'=>2),
				]
			]
		];
		$historyAndAssertableEvents[] = [
			$this->returnMinimumHistoryWithEventType('DecisionTaskStarted'),
			[
				[
					'eventType'=>'DecisionTaskTimedOut',
					'assertEventAttributes'
						=>array('scheduledEventId'=>2),
				]
			]
		];
		$historyAndAssertableEvents[] = [
			$this->returnSampleHistoryWithTimedOutDecisionTask(),
			[
				[
					'eventType'=>'DecisionTaskCompleted',
					'assertEventAttributes'
						=>array('scheduledEventId'=>5),
					//1:start, 2:schedule, 3:start, 4:time out, 5:schedule
				]
			]
		];
		
		//reference the start event
		$historyAndAssertableEvents[] = [
			$this->returnMinimumHistoryWithEventType('DecisionTaskStarted'),
			[
				[
					'eventType'=>'DecisionTaskCompleted',
					'assertEventAttributes'
						=>array('startedEventId'=>3),
				]
			]
		];
		$historyAndAssertableEvents[] = [
			$this->returnMinimumHistoryWithEventType('DecisionTaskStarted'),
			[
				[
					'eventType'=>'DecisionTaskTimedOut',
					'assertEventAttributes'
						=>array('startedEventId'=>3),
				]
			]
		];
	
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerDecisionTaskEventReferences
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent() sets scheduled,started ID for DecisionTask
	 */
	
	public function testDecisionTaskEventsReferenceStartedEvent(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	private function multiStageEventsMustContainUnusedContextId($contextIdKey,
		$firstEventType)
	{
		$contextId1 = 'id1';
		$DecisionTaskCompletedHistory
			= $this->returnMinimumHistoryWithEventType(
				'DecisionTaskCompleted');
		$historyAndAssertableEvents = [];
		//must have contextId
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>$firstEventType,
					'signalName'=>'any-name',
					'expectedException'=>'\InvalidArgumentException',
				]
			]
		];
		
		//cannot have same contextId twice
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>$firstEventType,
					'contextId'=>$contextId1,
					'signalName'=>'any-name',
				],
				[
					'eventType'=>$firstEventType,
					'contextId'=>$contextId1,
					'signalName'=>'any-name',
					'expectedException'=>'\InvalidArgumentException',
				]
			]
		];
	
		return $historyAndAssertableEvents;
	}
	
	private function threeStageEventsContextIdsMustMatchFirstEventContextId(
		$contextIdKey,$firstEventType,$referenceKeyToFirstEventId,
		$secondEventType,$referenceKeyToSecondEventId,
		$firstEventFailedEventType,$terminatingEvents)
	{
		$contextId1 = uniqid();
		$contextId2 = uniqid();
		$DecisionTaskCompletedHistory
			= $this->returnMinimumHistoryWithEventType(
				'DecisionTaskCompleted');
		$historyAndAssertableEvents = [];
		
		//assert second event with contextId needs to
		//match first event's contextId
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>$firstEventType,
					'contextId'=>$contextId1,
				],
				[
					'eventType'=>$secondEventType,
					'contextId'=>$contextId2,
					'expectedException'=>'\InvalidArgumentException',
				]
			]
		];
		
		//exceptions for terminating events with missing second event
		foreach ($terminatingEvents as $terminatingEventType) {
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				[
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId1,
					],
					[
						'eventType'=>$terminatingEventType,
						'contextId'=>$contextId1,
						'expectedException'=>'\InvalidArgumentException',
					]
				]
			];
		}
		
		//make sure wrong contextId cant let a terminating event
		//pass checks when making sure a previous event exists in history
		foreach ($terminatingEvents as $terminatingEventType) {
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				[
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId1,
					],
					[
						'eventType'=>$secondEventType,
						'contextId'=>$contextId1,
					],
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId2,
					],
					[
						'eventType'=>$terminatingEventType,
						'contextId'=>$contextId2,
						'expectedException'=>'\InvalidArgumentException'
					]
				]
			];
		}
		
		//make sure a previous cycle for a contextId cannot
		//allow a terminating event to be allowed without a second event
		$arbitraryTerminatingEventType = $terminatingEvents[0];
		foreach ($terminatingEvents as $terminatingEventType) {
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				[
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId1,
					],
					[
						'eventType'=>$secondEventType,
						'contextId'=>$contextId1,
					],
					[
						'eventType'=>$arbitraryTerminatingEventType,
						'contextId'=>$contextId1,
					],
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId1,//sameId
					],
					[
						'eventType'=>$terminatingEventType,
						'contextId'=>$contextId1,//sameId
						//missing secondEventType for this id
						'expectedException'=>'\InvalidArgumentException'
					]
				]
			];
		}
		
		return array_merge(
			$historyAndAssertableEvents,
			$this->multiStageEventsMustContainUnusedContextId($contextIdKey,
				$firstEventType)
		);
	}
	
	private function threeStageEventsMustReferenceParentsBasedOnContextId(
		$contextIdKey,$firstEventType,$referenceKeyToFirstEventId,
		$secondEventType,$referenceKeyToSecondEventId,
		$firstEventFailedEventType,$terminatingEvents)
	{
		$contextId1 = uniqid();
		$contextId2 = uniqid();
		$DecisionTaskCompletedHistory
			= $this->returnMinimumHistoryWithEventType(
				'DecisionTaskCompleted');
		$historyAndAssertableEvents = [];
		
		
		//events should reference parent events
		foreach ($terminatingEvents as $terminatingEventType) {
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				[
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId1,
						'assertEventId'=>5,
					],
					[
						'eventType'=>$secondEventType,
						'contextId'=>$contextId1,
						'assertEventAttributes'=>[
							$referenceKeyToFirstEventId=>5
						]
					],
					[
						'eventType'=>$terminatingEventType,
						'contextId'=>$contextId1,
						'assertEventAttributes'=>[
							$referenceKeyToFirstEventId=>5,
							$referenceKeyToSecondEventId=>6,
						]
					],
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId1,
						'assertEventId'=>8
					],
					[
						'eventType'=>$secondEventType,
						'contextId'=>$contextId1,
						'assertEventAttributes'=>[
							$referenceKeyToFirstEventId=>8
						]
					]
				]
			];
		}
		
		//verify that events reference the right parent even if not latest,
		//for both the first and second event 
		foreach ($terminatingEvents as $terminatingEventType
		) {
			$events = [
				[
					//eventId 5
					'eventType'=>$firstEventType,
					'eventAttributes'=>[$contextIdKey=>$contextId1],
					//'contextId'=>$contextId1,
					'assertEventId'=>5,
				],
				[
					//eventId 6
					'eventType'=>$firstEventType,
					'eventAttributes'=>[$contextIdKey=>$contextId2],
					//'contextId'=>$contextId2,
				],
				[
					//eventId 7
					'eventType'=>$secondEventType,
					'contextId'=>$contextId2,
					'assertEventId'=>7,
				],
				[
					//eventId 8
					'eventType'=>$secondEventType,
					'contextId'=>$contextId1,
				],
				[
					//activity1
					'eventType'=>$terminatingEventType,
					'assertEventAttributes'=>[
						$referenceKeyToSecondEventId=>8,
						$referenceKeyToFirstEventId=>5
					],
					'contextId'=>$contextId1,
				],
				[
					//activity2
					'eventType'=>$terminatingEventType,
					'assertEventAttributes'=>[
						$referenceKeyToSecondEventId=>7,
						$referenceKeyToFirstEventId=>6
					],
					'contextId'=>$contextId2,
				]
			];
				
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				$events
			];
		}
		
		//if subsequent events do not have a contextId, then they still
		//refer to the latest first event
		//TODO should we allow this no-contextId situation?
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>$firstEventType,
					'contextId'=>$contextId1
				],
				[
					'eventType'=>$secondEventType,
					'assertEventAttributes'=>[$referenceKeyToFirstEventId=>5]
				]
			]
		];
		
		return $historyAndAssertableEvents;
	}
	
	private function twoStageEventsMustReferenceParentsCorrectly($contextIdKey,
		$firstEventType,$referenceKeyToFirstEventId,
		$terminatingEvents)
	{
		$contextId1 = uniqid();
		$contextId2 = uniqid();
		$DecisionTaskCompletedHistory
			= $this->returnMinimumHistoryWithEventType(
				'DecisionTaskCompleted');
		
		$historyAndAssertableEvents = [];
		//verify contextId events point to correct parents
		foreach ($terminatingEvents as $terminatingEventType) {
			$events = [
				[
					'eventType'=>$firstEventType,
					'contextId'=>$contextId1,
				],
				[
					'eventType'=>$firstEventType,
					'contextId'=>$contextId2,
				],
				[
					//id2
					'eventType'=>$terminatingEventType,
					'assertEventAttributes'=>[
						$referenceKeyToFirstEventId=>6
					],
					'contextId'=>$contextId2,
				],
				[
					//id1
					'eventType'=>$terminatingEventType,
					'assertEventAttributes'=>[
						$referenceKeyToFirstEventId=>5
					],
					'contextId'=>$contextId1,
				]
			];
			
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				$events
			];
		}
		
		return $historyAndAssertableEvents;
	}

	private function twoStageEventsSecondEventContextIdMustMatchFirst(
		$contextIdKey,$firstEventType,$referenceKeyToFirstEventId,
		$terminatingEvents)
	{
		$contextId1 = uniqid();
		$contextId2 = uniqid();
		$DecisionTaskCompletedHistory
			= $this->returnMinimumHistoryWithEventType(
				'DecisionTaskCompleted');
		
		$historyAndAssertableEvents = [];
		
		//verify that cannot have the same first event twice in a row
		//without the first termining before the second starts
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>$firstEventType,
					'contextId'=>$contextId1,
				],
				[
					'eventType'=>$firstEventType,
					'contextId'=>$contextId1,
					'expectedException'=>'\InvalidArgumentException',
				]
			]
		];
		
		//its fine if the first ended
		foreach ($terminatingEvents as $terminatingEventType) {
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				[
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId1,
					],
					[
						'eventType'=>$terminatingEventType,
						'contextId'=>$contextId1,
					],
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId1,
					]
				]
			];
		}
		
		//context id must match
		foreach ($terminatingEvents as $terminatingEventType) {
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				[
					[
						'eventType'=>$firstEventType,
						'contextId'=>$contextId1,
					],
					[
						'eventType'=>$terminatingEventType,
						'contextId'=>$contextId2,
						'expectedException'=>'\InvalidArgumentException',
					],
				]
			];
		}
		
		//cannot start with termininating event type
		foreach ($terminatingEvents as $terminatingEventType) {
			$historyAndAssertableEvents[] = [
				$DecisionTaskCompletedHistory,
				[
					[
						'eventType'=>$terminatingEventType,
						'contextId'=>$contextId1,
						'expectedException'=>'\InvalidArgumentException',
					],
				]
			];
		}
		
		return $historyAndAssertableEvents;
	}
	
	public function providerActivityEventsContextIdsAndExceptionExpections()
	{
		$terminatingEvents = $this
			->eventTypesThatReferenceActivityTaskStarted();
		$historyAndAssertableEvents = $this
			->threeStageEventsContextIdsMustMatchFirstEventContextId(
				'activityId',
			'ActivityTaskScheduled','scheduledEventId',
			'ActivityTaskStarted','startedEventId',
			'ScheduleActivityTaskFailed',$terminatingEvents);
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerActivityEventsContextIdsAndExceptionExpections
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>exception for activity events w/o contextId
	 */
	
	public function testActivityTaskExceptionIfMissingContextIds(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerActivityTaskEventsReferenceParentCorrectly()
	{
		$terminatingEvents = $this
			->eventTypesThatReferenceActivityTaskStarted();
		$historyAndAssertableEvents = $this
			->threeStageEventsMustReferenceParentsBasedOnContextId(
				'activityId',
			'ActivityTaskScheduled','scheduledEventId',
			'ActivityTaskStarted','startedEventId',
			'ScheduleActivityTaskFailed',$terminatingEvents);
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerActivityTaskEventsReferenceParentCorrectly
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>contextId=>scheduled,started ID (Activity)
	 */
	
	public function testActivityTaskEventsSetParentEventIdsCorrectly(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerTimerEventsWithContextIdRestrictions()
	{
		$historyAndAssertableEvents
			= $this->twoStageEventsSecondEventContextIdMustMatchFirst(
				'timerId','TimerStarted', 'startedEventId',
				['TimerCanceled','TimerFired']
			);
		
		$historyAndAssertableEvents = array_merge(
			$historyAndAssertableEvents,
			$this->multiStageEventsMustContainUnusedContextId(
				'timerId', 'TimerStarted')
		);
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerTimerEventsWithContextIdRestrictions
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>exception for Timer events w/o contextId
	 */
	
	public function testTimerEventsExceptionIfMissingContextIdOrIsInUse(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			//set required attributes
			$eventToValidate['eventAttributes']['startToFireTimeout']=1;
			
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerTimerEventsReferencingParents()
	{
		$historyAndAssertableEvents
			= $this->twoStageEventsMustReferenceParentsCorrectly('timerId',
			'TimerStarted', 'startedEventId', ['TimerCanceled','TimerFired']);
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerTimerEventsReferencingParents
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>contextId=>started ID (Timer)
	 */
	
	public function testTimerEventsSetStartedEventIdCorrectly(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			//set required attributes
			$eventToValidate['eventAttributes']['startToFireTimeout']=1;
			
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	/**
	 * Populates a DesiredEvent with various generalized attributes (from
	 * array format), and then asserts various results.
	 * @param HistoryBuilder $HistoryBuilder
	 * @param array $eventToValidate valid attributes include eventType,
	 * contextId, eventAttributes, expectedException, assertEventId,
	 * assertEventAttributes
	 */
	
	private function validateEvent($HistoryBuilder,$eventToValidate,
		$setDefaultRequiredAttributes=false)
	{
		$eventType = $eventToValidate['eventType'];
		$contextId = isset($eventToValidate['contextId'])?
			$eventToValidate['contextId']:
			null;
		$eventAttributes = isset($eventToValidate['eventAttributes'])?
			$eventToValidate['eventAttributes']:
			array();
		
		if (isset($eventToValidate['expectedException'])) {
			$this->setExpectedException($eventToValidate['expectedException']);
		}
	
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setContextId($contextId);
		$DesiredEvent->setEventAttributes($eventAttributes);
		
		if ($setDefaultRequiredAttributes) {
			$this->setDefaultRequiredAttributesForEventType($DesiredEvent);
		}
		
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		if (isset($eventToValidate['assertEventId'])) {
			$this->assertLatestEventHasEventId(
				$HistoryBuilder, $eventToValidate['assertEventId']);
		}
		
		if (isset($eventToValidate['assertEventAttributes'])) {
			$attributes = $eventToValidate['assertEventAttributes'];
			$this->assertLatestEventContainsExpectedEventTypeAndAttributes(
				$HistoryBuilder, $eventType, $attributes);
		}
	}
	
	public function providerStartChildEventsContextIdsAndExceptionExpections()
	{
		$terminatingEvents = $this
			->eventTypesThatReferenceChildWorkflowStartedEventId();
		$historyAndAssertableEvents = $this
			->threeStageEventsContextIdsMustMatchFirstEventContextId(
				'workflowId',
			'StartChildWorkflowExecutionInitiated', 'initiatedEventId',
			'ChildWorkflowExecutionStarted', 'startedEventId',
			'StartChildWorkflowExecutionFailed', $terminatingEvents);
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerStartChildEventsContextIdsAndExceptionExpections
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>ex for StartChild events w/o contextId
	 */
	
	public function testStartChildExceptionIfMissingContextIds(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerStartChildEventsReferenceParentCorrectly()
	{
		$terminatingEvents = $this
			->eventTypesThatReferenceChildWorkflowStartedEventId();
		$historyAndAssertableEvents = $this
			->threeStageEventsMustReferenceParentsBasedOnContextId('workflowId',
			'StartChildWorkflowExecutionInitiated', 'initiatedEventId',
			'ChildWorkflowExecutionStarted', 'startedEventId',
			'StartChildWorkflowExecutionFailed', $terminatingEvents);
					
		$contextId1 = uniqid();
		$DecisionTaskCompletedHistory
			= $this->returnMinimumHistoryWithEventType(
				'DecisionTaskCompleted');
		
		
		//can start new child workflow if old one fails to start
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>'StartChildWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
				],
				[
					'eventType'=>'StartChildWorkflowExecutionFailed',
					'contextId'=>$contextId1,
				],
				[
					'eventType'=>'StartChildWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
				]
			]
		];
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerStartChildEventsReferenceParentCorrectly
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>contextId=>initiated,started ID (StartChild)
	 */
	
	public function testStartChildEventsSetParentEventIdsCorrectly(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerRequestCancelEventsWithContextIdRestrictions()
	{
		$terminatingEvents = [
			'ExternalWorkflowExecutionCancelRequested',
			'RequestCancelExternalWorkflowExecutionFailed'
		];
		$historyAndAssertableEvents = $this
			->twoStageEventsSecondEventContextIdMustMatchFirst(
			'workflowId', 'RequestCancelExternalWorkflowExecutionInitiated',
			'initiatedEventId', $terminatingEvents);
		
		$historyAndAssertableEvents = array_merge(
			$historyAndAssertableEvents,
			$this->multiStageEventsMustContainUnusedContextId(
				'workflowId','RequestCancelExternalWorkflowExecutionInitiated')
		);
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerRequestCancelEventsWithContextIdRestrictions
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>ex for RequestCancel events w/o contextId
	 */
	
	public function testRequestCancelExceptionIfMissingContextIdOrIsInUse(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerRequestCancelEventsReferencingParents()
	{
		$terminatingEvents = [
			'ExternalWorkflowExecutionCancelRequested',
			'RequestCancelExternalWorkflowExecutionFailed'
		];
		$historyAndAssertableEvents = $this
			->twoStageEventsMustReferenceParentsCorrectly(
			'workflowId', 'RequestCancelExternalWorkflowExecutionInitiated',
			'initiatedEventId', $terminatingEvents);
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerRequestCancelEventsReferencingParents
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>contextId sets initiated ID (RequestCancel)
	 */
	
	public function testRequestCancelEventsSetInitiatedEventIdCorrectly(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerSignalExternalEventsWithSignalNameRestrictions()
	{
		$historyAndAssertableEvents = [];
		$contextId1 = uniqid();
		$contextId2 = uniqid();
		$signalName1 = uniqid();
		$signalName2 = uniqid();
		$DecisionTaskCompletedHistory = $this
			->returnMinimumHistoryWithEventType('DecisionTaskCompleted');
		
		//throws exception without signalName
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'expectedException'=>'\InvalidArgumentException',
				]
			]
		];
		
		//cannot have the same signal twice with same workflowId
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
					'expectedException'=>'\InvalidArgumentException',
				]
			]
		];
		
		//unless that first signal has completed
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'ExternalWorkflowExecutionSignaled',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				]
			]
		];
		
		//can have different signal with same workflow
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName2],
				]
			]
		];
		//second event must have matching signal
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'ExternalWorkflowExecutionSignaled',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName2],
					'expectedException'=>'\InvalidArgumentException',
				]
			]
		];
		
		
		//can have same signal with different workflowId
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId2,
					'eventAttributes'=>['signalName'=>$signalName1],
				]
			]
		];

		//verify that previous completed signal cannot allow same signal
		//without initiated event
		$historyAndAssertableEvents[] = [
			$DecisionTaskCompletedHistory,
			[
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'ExternalWorkflowExecutionSignaled',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'ExternalWorkflowExecutionSignaled',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
					'expectedException'=>'\InvalidArgumentException',
				]
			]
		];
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerSignalExternalEventsWithSignalNameRestrictions
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>ex for SignalExternal events w/o signalName
	 */
	
	public function testSignalExternalExceptionIfSignalNotSetCorrectly(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerSignalExternalEventsWithContextIdRestrictions()
	{
		$terminatingEvents = [
			'SignalExternalWorkflowExecutionFailed',
			'ExternalWorkflowExecutionSignaled'
		];
		$historyAndAssertableEvents = $this
			->twoStageEventsSecondEventContextIdMustMatchFirst(
			'workflowId', 'SignalExternalWorkflowExecutionInitiated',
			'initiatedEventId', $terminatingEvents);
		
		$historyAndAssertableEvents = array_merge(
			$historyAndAssertableEvents,
			$this->multiStageEventsMustContainUnusedContextId(
				'workflowId','SignalExternalWorkflowExecutionInitiated')
		);
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerSignalExternalEventsWithContextIdRestrictions
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>ex for SignalExternal events w/o contextId
	 */
	
	public function testSignalExternalExceptionIfMissingContextIdOrIsInUse(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		$signalName = uniqid();
		foreach ($eventsToValidate as $eventToValidate) {
			//set required attributes
			$eventToValidate['eventAttributes']['signalName']=$signalName;
			
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerSignalExternalEventsReferencingParents()
	{
		$terminatingEvents = [
			'SignalExternalWorkflowExecutionFailed',
			'ExternalWorkflowExecutionSignaled'
		];
		$historyAndAssertableEvents = $this
			->twoStageEventsMustReferenceParentsCorrectly(
			'workflowId', 'SignalExternalWorkflowExecutionInitiated',
			'initiatedEventId', $terminatingEvents);
		
		//signals can be reused if completed
		//TODO move to separate test?
		$contextId1 = uniqid();
		$signalName1 = uniqid();
		$historyAndAssertableEvents[] = [
			$this->returnMinimumHistoryWithEventType('DecisionTaskCompleted'),
			[
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'ExternalWorkflowExecutionSignaled',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'SignalExternalWorkflowExecutionInitiated',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				],
				[
					'eventType'=>'ExternalWorkflowExecutionSignaled',
					'contextId'=>$contextId1,
					'eventAttributes'=>['signalName'=>$signalName1],
				]
			]
		];
		
		return $historyAndAssertableEvents;
	}
	
	/**
	 * @dataProvider providerSignalExternalEventsReferencingParents
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>contextId sets initiated ID (SignalExternal)
	 */
	
	public function testSignalExternalEventsSetInitiatedEventIdCorrectly(
		$originalHistory,$eventsToValidate)
	{
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		$signalName = uniqid();
		foreach ($eventsToValidate as $eventToValidate) {
			//set required attributes
			$eventToValidate['eventAttributes']['signalName']=$signalName;
			
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	/**
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent() doesn't mixup contextId for diff. event types
	 */
	
	public function testEventsUsingWorkflowIdDoNotCrossPolinate()
	{
		$originalHistory = $this
			->returnMinimumHistoryWithEventType('DecisionTaskCompleted');
			
		$eventsToValidate = [
			[
				'eventType'
					=>'RequestCancelExternalWorkflowExecutionInitiated',
				'contextId'=>'sameId',
			],
			[
				'eventType'=>'ChildWorkflowExecutionStarted',
				'contextId'=>'sameId',
				'expectedException'=>'\InvalidArgumentException',
			]
		];
			
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($originalHistory);
		
		foreach ($eventsToValidate as $eventToValidate) {
			$this->validateEvent($HistoryBuilder, $eventToValidate);
		}
	}
	
	public function providerEventsAndContextIds()
	{
		return [
			['ActivityTaskScheduled','123','activityId'],
			['StartChildWorkflowExecutionInitiated','abc','workflowId'],
			['SignalExternalWorkflowExecutionInitiated','xyz','workflowId',
				'signal'],
			['RequestCancelExternalWorkflowExecutionInitiated','234',
				'workflowId'],
			['TimerStarted','890','timerId',null,['startToFireTimeout'=>3]]
		];
	}
	
	/**
	 * @dataProvider providerEventsAndContextIds
	 * 
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent() contextId populates eventAttributes ID
	 */
	
	public function testCanSetContextIdOnEventAttributesThroughDesiredEvent(
		$eventType,$contextId,$attributeName,$signalName=null,
		$additionalAttributes=[])
	{
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setContextId($contextId);
		if (null !== $signalName) {
			$DesiredEvent->setSignalName($signalName);
		}
		//explicitly don't set the eventAttribute for contextId
		$DesiredEvent->setEventAttributes($additionalAttributes);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$history = $HistoryBuilder->getEventHistory();
		$event = array_pop($history);
		
		$this->assertEventAttributeEqualsExpectedValue(
			$event,
			$attributeName,
			$contextId
		);
	}
	
	/**
	 * @group setEventHistory()
	 * @testdox setEventHistory()=>ex for SignalExternal events w/o signalName
	 */
	
	public function testSignalWorkflowThrowsExForSetHistoryWithoutSignalName()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType(
			'SignalExternalWorkflowExecutionInitiated');
		$DesiredEvent->setContextId('any-id');
		$DesiredEvent->setSignalName('my-signal-name');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		//pop the event off, remove the signalName, then put it back
		$history = $HistoryBuilder->getEventHistory();
		$event = array_pop($history);
		$this->assertArrayHasKey(
			'signalExternalWorkflowExecutionInitiatedEventAttributes',
			$event
		);
		$this->assertArrayHasKey(
			'signalName',
			$event['signalExternalWorkflowExecutionInitiatedEventAttributes']
		);
		unset($event
			['signalExternalWorkflowExecutionInitiatedEventAttributes']
			['signalName']);
		
		
		$history[] = $event;
		$HistoryBuilder->setEventHistory($history);
	}
	
	/**
	 * @group getEventHistory()
	 * @testdox getEventHistory()=>(n+1)['eventTimestamp']>=n['eventTimestamp']
	 */
	
	public function testEventsHaveEqualOrIncreasingTimestamps()
	{
		$history = $this->returnMinimumHistoryWithEventType(
			'ActivityTaskCompleted');
		
		$previousTimestamp = 0;
		foreach ($history as $event) {
			$this->assertArrayHasKey('eventTimestamp',$event);
			$this->assertTrue($event['eventTimestamp']>=$previousTimestamp);
			$previousTimestamp = $event['eventTimestamp'];
		}
	}
	
	/**
	 * @group addDesiredEvent()
	 * @testdox addDesiredEvent()=>ex for timer w/o startToFireTimeout set
	 */
	
	public function testTimerStartedThrowsExceptionWithoutStartToFireAttr()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('TimerStarted');
		$DesiredEvent->setContextId(uniqid());
		
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
	}
		
	public function testCanSetUnixTimestamp()
	{
		$unixTimestamp = 1000;
		$eventType = 'TimerStarted';
		
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		$contextId=uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setContextId($contextId);
		$DesiredEvent->setUnixTimestamp($unixTimestamp);
		
		$this->setDefaultRequiredAttributesForEventType($DesiredEvent);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$eventHistory = $HistoryBuilder->getEventHistory();
		$event = array_pop($eventHistory);
		
		$this->assertArrayHasKey('eventTimestamp',$event);
		$this->assertEquals($unixTimestamp,$event['eventTimestamp']);
	}
	
	public function testCanSetSecondsSinceLastEvent()
	{
		$secondsSinceLastEvent = 2.5;
		$eventType = 'ActivityTaskScheduled';
		
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		$contextId=uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setContextId($contextId);
		$DesiredEvent->setSecondsSinceLastEvent($secondsSinceLastEvent);
		
		$this->setDefaultRequiredAttributesForEventType($DesiredEvent);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$eventHistory = $HistoryBuilder->getEventHistory();
		$testedEvent = array_pop($eventHistory);
		$DTCEvent = array_pop($eventHistory);
		
		$this->assertArrayHasKey('eventTimestamp',$DTCEvent);
		
		$this->assertArrayHasKey('eventTimestamp',$testedEvent);
		$this->assertEquals(
			$DTCEvent['eventTimestamp']+$secondsSinceLastEvent,
			$testedEvent['eventTimestamp']
		);
	}
	
	public function testCanSetSecondsSinceParentEvent()
	{
		$secondsSinceLatestAncestor = 2.5;
		
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		$activityId=uniqid();
		$timerId = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('ActivityTaskScheduled');
		$DesiredEvent->setContextId($activityId);
		$DesiredEvent->setUnixTimestamp(1000);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		//put another event in between
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('TimerStarted');
		$DesiredEvent->setContextId($timerId);
		$DesiredEvent->setUnixTimestamp(1001);
		$this->setDefaultRequiredAttributesForEventType($DesiredEvent);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		//now the event we want to look at
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('ActivityTaskStarted');
		$DesiredEvent->setContextId($activityId);
		$DesiredEvent->setSecondsSinceLatestAncestor(
			$secondsSinceLatestAncestor);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$eventHistory = $HistoryBuilder->getEventHistory();
		$testedEvent = array_pop($eventHistory);
		array_pop($eventHistory);
		$scheduledEvent = array_pop($eventHistory);
		
		$this->assertArrayHasKey('eventTimestamp',$scheduledEvent);
		
		$this->assertArrayHasKey('eventTimestamp',$testedEvent);
		$this->assertEquals(
			$scheduledEvent['eventTimestamp']+$secondsSinceLatestAncestor,
			$testedEvent['eventTimestamp']
		);
	}
	
	public function providerTimerEventsAndNeedingTimerStarted()
	{
		return [
			[true,'TimerFired'],
			[true,'TimerCanceled'],
			[false,'StartTimerFailed'],
			[false,'CancelTimerFailed'],
		];
	}
	
	/**
	 * @dataProvider providerTimerEventsAndNeedingTimerStarted
	 */
	
	public function testOtherTimerEventsAlsoContainTimerId(
		$createTimerStarted,$eventType)
	{
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		$contextId=uniqid();
		//start timer
		if ($createTimerStarted) {
			$DesiredEvent = new DesiredEvent();
			$DesiredEvent->setEventType('TimerStarted');
			$DesiredEvent->setContextId($contextId);
			$DesiredEvent->setEventAttributes([
				'startToFireTimeout'=>5
			]);
			$HistoryBuilder->addDesiredEvent($DesiredEvent);
		}
		
		//add next event, and verify it has the timerId
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setContextId($contextId);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$newHistory = $HistoryBuilder->getEventHistory();
		$timerEvent = array_pop($newHistory);
		$this->assertEventAttributeEqualsExpectedValue(
			$timerEvent, 'timerId', $contextId);
	}
	
	public function testTimerFiredDefaultSetsEventTimestampBasedOnStartTimer()
	{
		$startToFireTimeout = 5;
		$startUnixTimestamp = 1000.01;
		$fireUnixTimestamp = $startUnixTimestamp+$startToFireTimeout;
		
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		//start timer
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('TimerStarted');
		$DesiredEvent->setContextId($contextId=uniqid());
		$DesiredEvent->setEventAttributes([
			'startToFireTimeout'=>$startToFireTimeout
		]);
		$DesiredEvent->setUnixTimestamp($startUnixTimestamp);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		//end timer
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('TimerFired');
		$DesiredEvent->setContextId($contextId);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$newHistory = $HistoryBuilder->getEventHistory();
		$newEvent = array_pop($newHistory);
		
		$this->assertArrayHasKey('eventTimestamp',$newEvent);
		$this->assertEquals($fireUnixTimestamp,$newEvent['eventTimestamp']);
	}
	
	public function providerTimestampsAndStartToFireTimeoutsAndValidOrNot()
	{
		return [
			//exactly is valid
			[5,1000.01,1005.01,true],
			//more than amount is valid (swf may have been down)
			[5,1000.01,1010.01,true],
			//firing early is not valid
			[5,1000.01,1004,false],
		];
	}
	
	/**
	 * @dataProvider providerTimestampsAndStartToFireTimeoutsAndValidOrNot
	 */
	
	public function testTimerFiredRequiresGreaterThanOrEqualToStartPlusFire(
		$startToFireTimeout,$startUnixTimestamp,$fireUnixTimestamp,$valid)
	{
		if (!$valid) {
			$this->setExpectedException('\InvalidArgumentException');
		}
		
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		//start timer
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('TimerStarted');
		$DesiredEvent->setContextId($contextId=uniqid());
		$DesiredEvent->setEventAttributes([
			'startToFireTimeout'=>$startToFireTimeout
		]);
		$DesiredEvent->setUnixTimestamp($startUnixTimestamp);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		//end timer
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('TimerFired');
		$DesiredEvent->setContextId($contextId);
		$DesiredEvent->setUnixTimestamp($fireUnixTimestamp);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
	}
	
	/**
	 * @dataProvider providerTimestampsAndStartToFireTimeoutsAndValidOrNot
	 */
	
	public function testTimerFiredNeedsGreaterEqualThanStartToFire4SetHistory(
		$startToFireTimeout,$startUnixTimestamp,$fireUnixTimestamp,$valid)
	{
		if (!$valid) {
			$this->setExpectedException('\InvalidArgumentException');
		}
		
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		//start timer
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('TimerStarted');
		$DesiredEvent->setContextId($contextId=uniqid());
		$DesiredEvent->setEventAttributes([
			'startToFireTimeout'=>$startToFireTimeout
		]);
		$DesiredEvent->setUnixTimestamp($startUnixTimestamp);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$eventHistory = $HistoryBuilder->getEventHistory();
		
		//end timer
		$eventHistory[] = [
			'eventType'=>'TimerFired',
			'eventTimestamp'=>$fireUnixTimestamp,
			'eventId'=>count($eventHistory)+1,
			'timerFiredEventAttributes'=>[
				'timerId'=>$contextId
			]
		];
		
		$HistoryBuilder->setEventHistory($eventHistory);
	}
	
	private function assertEventType($expectedEventType,$event)
	{
		$this->assertArrayHasKey('eventType',$event);
		$this->assertEquals($expectedEventType,$event['eventType']);
	}
	
	/**
	 * 
	 * @param HistoryBuilder $HistoryBuilder
	 * @param string $eventType
	 * @param array $attributes
	 */
	
	private function assertLatestEventContainsExpectedEventTypeAndAttributes(
		$HistoryBuilder,$eventType,$attributes)
	{
		$newHistory = $HistoryBuilder->getEventHistory();
		$newEvent = array_pop($newHistory);
		
		$this->assertEventType($eventType,$newEvent);
		
		foreach ($attributes as $expectedValueKey => $expectedValue) {
			$this->assertEventAttributeEqualsExpectedValue(
				$newEvent,
				$expectedValueKey,
				$expectedValue
			);
		}
	}
	
	private function assertLatestEventHasEventId($HistoryBuilder,$eventId)
	{
		$newHistory = $HistoryBuilder->getEventHistory();
		$newEvent = array_pop($newHistory);
		
		$this->assertArrayHasKey('eventId',$newEvent);
		$this->assertEquals($eventId,$newEvent['eventId']);
	}
	
	private function assertEventAttributeEqualsExpectedValue($event,
		$expectedValueKey,$expectedValue)
	{		
		$this->assertArrayHasKey('eventType',$event);
		$eventType = $event['eventType'];
		
		$eventAttributesKey = lcfirst($eventType).'EventAttributes';
		
		$this->assertArrayHasKey($eventAttributesKey,$event);
		$eventAttributes = $event[$eventAttributesKey];
		$this->assertInternalType('array',$eventAttributes);
		
		$this->assertArrayHasKey(
			$expectedValueKey,
			$eventAttributes,
			print_r($event,true)
		);
		$this->assertEquals(
			$expectedValue,
			$eventAttributes[$expectedValueKey]
		);
	}
	
	/**
	 * Recursively adds all preceding event types required for specified
	 * eventType; If the eventType is WorkflowExecutionStarted then a
	 * history with just one event is returned with minimal attributes,
	 * otherwise a more fleshed out initial event is provided.
	 * @param string $eventType
	 * @return multitype:
	 */
	
	private function returnMinimumHistoryWithEventType($eventType,
		$contextId='default',$signalName='default')
	{
		$requiredEventType
			= $this->eventTypeMinimallyRequiredForEventType($eventType);
		
		if ($requiredEventType) {
			$history = $this->returnMinimumHistoryWithEventType(
				$requiredEventType);
		} elseif ('WorkflowExecutionStarted' != $eventType) {
			$history = $this->returnHistoryWithOnlyFirstEvent();
		} else {
			$history = array();
		}
		
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setContextId($contextId);
		$DesiredEvent->setSignalName($signalName);
		//set things we arent testing for
		$this->setDefaultRequiredAttributesForEventType($DesiredEvent);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		return $HistoryBuilder->getEventHistory();
	}
	
	/**
	 * 
	 * @param DesiredEvent $DesiredEvent
	 */
	
	private function setDefaultRequiredAttributesForEventType($DesiredEvent)
	{
		$eventAttributes = $DesiredEvent->getEventAttributes();
		if (null === $eventAttributes) {
			$eventAttributes = [];
		}
		switch ($DesiredEvent->getEventType()) {
			case 'TimerStarted':
				if (!isset($eventAttributes['startToFireTimeout'])) {
					$eventAttributes['startToFireTimeout']=1;
				}
				break;
		}
		$DesiredEvent->setEventAttributes($eventAttributes);
	}
	
	private function returnHistoryWithOnlyFirstEvent()
	{
		return [
			[
				'eventTimestamp'=>1,
				'eventType'=>'WorkflowExecutionStarted',
				'eventId'=>1,
				'workflowExecutionStartedEventAttributes' => [
					'input' => '',
					'executionStartToCloseTimeout' => '900',
					'taskStartToCloseTimeout' => '900',
					'childPolicy' => 'ABANDON',
					'taskList' => [
						'name' => 'general',
					],
					'workflowType' => [
						'name' => 'test-workflow',
						'version' => '1',
					],
				],
			],
		];
	}
	
	private function returnSampleHistoryWithTimedOutDecisionTask()
	{
		//start with a decision task started
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskStarted');
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		//time it out
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('DecisionTaskTimedOut');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		//schedule and start a new one
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('DecisionTaskScheduled');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('DecisionTaskStarted');
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		return $HistoryBuilder->getEventHistory();
	}
	
	/**
	 * Returns history with eventId of 5 for ScheduledActivityTask
	 * @param string $activityId
	 * @return multitype:
	 */
	
	private function returnSampleHistoryWithScheduledActivityTaskWithId(
		$activityId)
	{
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('ActivityTaskScheduled');
		$DesiredEvent->setEventAttributes(array('activityId'=>$activityId));
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		return $HistoryBuilder->getEventHistory();
	}
	
	/**
	 * Provides history with minimum events to schedule two activity tasks,
	 * where the ScheduleActivityTask eventIds are 5 and 6, respectively.
	 * @param unknown $activityId1
	 * @param unknown $activityId2
	 * @return multitype:
	 */
	
	private function returnHistoryWithTwoSimultaneousActivityTasksScheduled(
		$activityId1,$activityId2)
	{
		$history = $this->returnMinimumHistoryWithEventType(
			'DecisionTaskCompleted');
		
		//two activity tasks
		$HistoryBuilder = new HistoryBuilder();
		$HistoryBuilder->setEventHistory($history);
		
		//eventId 5
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('ActivityTaskScheduled');
		$DesiredEvent->setEventAttributes(array('activityId'=>$activityId1));
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		//eventId 6
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('ActivityTaskScheduled');
		$DesiredEvent->setEventAttributes(array('activityId'=>$activityId2));
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		return $HistoryBuilder->getEventHistory();
	}
}