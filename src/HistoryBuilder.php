<?php

namespace SwfHistoryMocker;

use SwfHistoryMocker\traits\ValidSwfEventTypes;
use SwfHistoryMocker\traits\EventToEventReferences;
class HistoryBuilder
{
	use ValidSwfEventTypes;
	use EventToEventReferences;
	/**
	 * @param DesiredEvent $DesiredEvent
	 */
	public function addDesiredEvent($DesiredEvent)
	{
		if (null === $DesiredEvent->getEventType()) {
			throw new \InvalidArgumentException('Must set an eventType.');
		}
		
		$this->assertRequiredAttributesAreSet($DesiredEvent);
		
		$eventType = $DesiredEvent->getEventType();
				
		
		//check the contextId's validity
		if ($contextId = $DesiredEvent->getContextId()) {
			if ($this->eventTypeUsesActivityId($eventType)) {
				if (!$this
					->eventHistoryContainsMinimumForEventTypeAndActivityId(
						$eventType,$contextId)
				) {
					$requiredEventType
						= $this->eventTypeMinimallyRequiredForEventType(
							$eventType);
					throw new \InvalidArgumentException(
						"$eventType requires that $requiredEventType be "
						."present in the event history for activityId "
						."\"$contextId\".");
				}
			} elseif ($this->eventTypeUsesTimerId($eventType)
				&& !$this->eventTypeIsSourceOfTimerId($eventType)
				&& !$this->timerIdIsKnown($contextId)
			) {
				throw new \InvalidArgumentException(
					"$eventType was specifed to refer to a timerId "
					." of \"$contextId\", but no previous events use"
					." that timerId."
				);
			} elseif ($this->eventTypeUsesWorkflowId($eventType)
				&& !$this->eventTypeUsesWorkflowIdAndSignalName($eventType)
			) {
				
				if (!$this
					->eventHistoryContainsMinimumForEventTypeAndWorkflowId(
					$eventType, $contextId)
				) {
					throw new \InvalidArgumentException(
						"$eventType was specifed to refer to a workflowId "
						." of \"$contextId\", but no previous events use"
						." that workflowId. Event History:\n"
						.print_r($this->eventHistory,true)
						."\nLatest WorkflowId events:\n"
						.print_r($this->latestWorkflowIdEvents,true)
					);
				}
			}
			$signalName = $DesiredEvent->getSignalName();
			if ($signalName
				&& $this->eventTypeUsesWorkflowIdAndSignalName($eventType)
				&& !$this
				->eventHistoryContainsMinimumForEventTypeWorkflowAndSignal(
					$eventType, $contextId, $signalName)
			) {
				throw new \InvalidArgumentException(
					"$eventType was specifed to refer to a workflowId "
					." of \"$contextId\" and signalName of \"$signalName\""
					.", but no previous events use"
					." that workflowId and signalName. Event History:\n"
						.print_r($this->eventHistory,true)
						."\nLatest workflowId and signalName events:\n"
						.print_r(
							$this->latestWorkflowIdAndSignalNameEvents,
							true
						)
					);
			}
		}
		
		if (!$this->eventHistoryContainsMinimumForEventType($eventType)) {
			$requiredEventType
				= $this->eventTypeMinimallyRequiredForEventType($eventType);
			throw new \InvalidArgumentException(
				"$eventType requires that $requiredEventType be present"
				." in the event history.");
		}
		
		//start building the event
		$eventId = count($this->eventHistory)+1;
		
		$eventAttributesKey = $this->getEventAttributesKey($eventType);
		$event = [
			'eventTimestamp'=>$this->getDesiredEventTimestamp($DesiredEvent),
			'eventId'=>$eventId,
			'eventType'=>$eventType,
			$eventAttributesKey=>$DesiredEvent->getEventAttributes(),
		];
		
		//add scheduledEventId
		if ($scheduledEventIdReferencedEventType
			= $this->eventTypeReferenceForScheduledEventId($eventType)
		) {
			//check for a contextId
			if ($contextId) {
				if ($this->eventTypeUsesActivityId($eventType)) {
					$event[$eventAttributesKey]['scheduledEventId']
						= $this->getLatestEventIdForActivityIdEventType(
							$contextId, $scheduledEventIdReferencedEventType);
				}
			} elseif ($this->latestEventIdForEventTypeIsKnown(
				$scheduledEventIdReferencedEventType)
			) {
				//otherwise just use the latest event
				$event[$eventAttributesKey]['scheduledEventId']=
					$this->getLatestEventIdForEventType(
						$scheduledEventIdReferencedEventType);
			}
		}
		
		//add startedEventId
		if ($startedEventIdReferencedEventType =
			$this->eventTypeReferenceForStartedEventId($eventType)
		) {
			if ($contextId) {
				if ($this->eventTypeUsesActivityId($eventType)) {
					$event[$eventAttributesKey]['startedEventId']
						= $this->getLatestEventIdForActivityIdEventType(
							$contextId, $startedEventIdReferencedEventType);
				} elseif ($this->eventTypeUsesTimerId($eventType)) {
					$event[$eventAttributesKey]['startedEventId']
						= $this->getLatestEventIdForTimerIdEventType(
							$contextId,$startedEventIdReferencedEventType);
				} elseif ($this->eventTypeUsesWorkflowId($eventType)) {
					$event[$eventAttributesKey]['startedEventId']
						= $this->getLatestEventIdForWorkflowIdEventType(
							$contextId,$startedEventIdReferencedEventType);
				}
			} elseif ($this->latestEventIdForEventTypeIsKnown(
				$startedEventIdReferencedEventType)
			) {
				$event[$eventAttributesKey]['startedEventId']
					= $this->getLatestEventIdForEventType(
						$startedEventIdReferencedEventType);
			}
		}
		
		//add initiated eventId
		if ($initiatedEventIdReferencedEventType
			= $this->eventTypeReferenceForInitiatedEventId($eventType)
		) {
			if ($contextId) {
				if ($this->eventTypeUsesWorkflowId($eventType)) {
					$event[$eventAttributesKey]['initiatedEventId']
						= $this->getLatestEventIdForWorkflowIdEventType(
							$contextId,$initiatedEventIdReferencedEventType);
				}
			} elseif ($this->latestEventIdForEventTypeIsKnown(
				$initiatedEventIdReferencedEventType)
			) {
				$event[$eventAttributesKey]['initiatedEventId']
					= $this->getLatestEventIdForEventType(
						$initiatedEventIdReferencedEventType);
			}
		}
		
		//add decisionTaskCompletedEventId
		if ($this->eventTypeRequiresDecisionTaskCompletedEventId($eventType)
		) {
			$event[$eventAttributesKey]['decisionTaskCompletedEventId']
				= $this->getLatestEventIdForEventType('DecisionTaskCompleted');
		}
		
		//use contextId to set activityId, if present
		if ($contextId = $DesiredEvent->getContextId()) {
			$contextKey = $this->getContextIdKeyForEventType($eventType);
			if ($contextKey) {
				$event[$eventAttributesKey][$contextKey]=$contextId;
			}
		}
		if ($signalName = $DesiredEvent->getSignalName()) {
			$event[$eventAttributesKey]['signalName']=$signalName;
		}
		
		$this->verifyAndAddEventToReferenceHistory($event);
		
		$this->eventHistory[] = $event;
	}
	
	/**
	 * @return NULL|array
	 */
	
	public function getLatestEvent()
	{
		$numEvents = count($this->eventHistory);
		if (!$numEvents) {
			return null;
		}
		
		return $this->eventHistory[$numEvents-1];
	}
	
	/**
	 * 
	 * @param DesiredEvent $DesiredEvent
	 * @return number
	 */
	
	private function getDesiredEventTimestamp($DesiredEvent)
	{
		if (null !== $unixTimestamp = $DesiredEvent->getUnixTimestamp()) {
			$eventTimestamp = $unixTimestamp;
			return $eventTimestamp;
		} elseif (null !== $secondsSinceLastEvent
			= $DesiredEvent->getSecondsSinceLastEvent()
		) {
			$latestEvent = $this->getLatestEvent();
			$eventTimestamp = $latestEvent['eventTimestamp']
				+ $secondsSinceLastEvent;
			
			return $eventTimestamp;
		} elseif (null !== $secondsSinceLatestAncestor
			= $DesiredEvent->getSecondsSinceLatestAncestor()
		) {
			$eventType = $DesiredEvent->getEventType();
			$previousEventType
				= $this->eventTypeMinimallyRequiredForEventType($eventType);
			$eventHistory = $this->eventHistory;
			if ($this->eventTypeUsesActivityId($eventType)
				&& !$this->eventTypeIsSourceOfActivityId($eventType)
			) {
				$previousEventId = $this
					->getLatestEventIdForActivityIdEventType(
						$DesiredEvent->getContextId(),$previousEventType);
				$previousEvent = $eventHistory[$previousEventId-1];
			} elseif ($this->eventTypeUsesTimerId($eventType)
				&& !$this->eventTypeIsSourceOfTimerId($eventType)
			) {
				$previousEventId = $this
					->getLatestEventIdForTimerIdEventType(
						$DesiredEvent->getContextId(),$previousEventType);
				$previousEvent = $eventHistory[$previousEventId-1];
			} elseif ($this->eventTypeUsesWorkflowIdButNotSignalName(
					$eventType)
				&& !$this->eventTypeIsSourceOfWorkflowIdButNotSignalName(
					$eventType)
			) {
				$previousEventId = $this
					->getLatestEventIdForWorkflowIdEventType(
						$DesiredEvent->getContextId(),$previousEventType);
				$previousEvent = $eventHistory[$previousEventId-1];
			} elseif ($this->eventTypeUsesWorkflowIdAndSignalName($eventType)
				&& !$this->eventTypeIsSourceOfWorkflowIdAndSignalName(
					$eventType)
			) {
				$previousEventId = $this
					->getLatestEventIdForWorkflowIdAndSignalNameEventType(
						$DesiredEvent->getContextId(),
						$DesiredEvent->getSignalName(),
						$previousEventType
					);
				$previousEvent = $eventHistory[$previousEventId-1];
			} else {
				$previousEventId = $this
					->getLatestEventIdForEventType($previousEventType);
				$previousEvent = $eventHistory[$previousEventId-1];
			}
			
			if (!empty($previousEvent)) {
				$eventTimestamp = $previousEvent['eventTimestamp']
					+ $secondsSinceLatestAncestor;
				
				return $eventTimestamp;
			}
		} elseif ('TimerFired'==$DesiredEvent->getEventType()) {
			$timerId = $DesiredEvent->getContextId();
			$timerStartedEventId = $this
				->getLatestEventIdForTimerIdEventType($timerId,
					'TimerStarted');
			
			$eventHistory = $this->getEventHistory();
			$timerStartedEvent = $eventHistory[$timerStartedEventId-1];
			$timerStartedEventAttributeKey
				= $this->getEventAttributesKey('TimerStarted');
			$startToFireTimeout = $timerStartedEvent
				[$timerStartedEventAttributeKey]['startToFireTimeout'];
			$timerStartedEventTimestamp
				= $timerStartedEvent['eventTimestamp'];
			
			$unixTimestamp = $startToFireTimeout
				+$timerStartedEventTimestamp;
			
			return $unixTimestamp;
		} else {
			$numEvents = count($this->eventHistory);
			if (!$numEvents) {
				return 1;
			}
			$latestTimestamp
				= $this->eventHistory[$numEvents-1]['eventTimestamp'];
			
			$eventTimestamp = $latestTimestamp;
			
			return $eventTimestamp;
		}
	}
	
	private function assertRequiredAttributesAreSet($DesiredEvent)
	{
		$eventType = $DesiredEvent->getEventType();
		$eventAttributes = $DesiredEvent->getEventAttributes();
		switch ($eventType) {
			case 'TimerStarted':
				if (!isset($eventAttributes['startToFireTimeout'])) {
					throw new \InvalidArgumentException();
				}
				break;
		}
	}
	
	private function eventTypeRequiresContextId($eventType)
	{
		return (bool) $this->getContextIdKeyForEventType($eventType);
	}
	
	private function getContextIdKeyForEventType($eventType)
	{
		switch ($eventType) {
			case 'ActivityTaskScheduled':
				$contextKey = 'activityId';
				break;
			case 'StartChildWorkflowExecutionInitiated':
			case 'SignalExternalWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionInitiated':
				$contextKey = 'workflowId';
				break;
			case 'TimerStarted':
			case 'TimerFired':
			case 'TimerCanceled':
			case 'StartTimerFailed';
			case 'CancelTimerFailed':
				$contextKey = 'timerId';
				break;
			default:
				$contextKey = false;
		}
		
		return $contextKey;
	}
	
	private function eventTypeUsesActivityId($eventType)
	{
		switch ($eventType) {
			case 'ActivityTaskScheduled':
			case 'ActivityTaskCompleted':
			case 'ActivityTaskStarted':
			case 'ActivityTaskTimedOut':
			case 'ActivityTaskCanceled':
			case 'ActivityTaskFailed':
				return true;
			default:
				return false;
		}
	}
	
	private function eventTypeIsSourceOfActivityId($eventType)
	{
		return 'ActivityTaskScheduled'==$eventType;
	}
	
	private function eventTypeUsesTimerId($eventType)
	{
		switch ($eventType) {
			case 'TimerCanceled':
			case 'TimerFired':
			case 'TimerStarted':
				return true;
			default:
				return false;
		}
	}
	
	private function eventTypeIsSourceOfTimerId($eventType)
	{
		return 'TimerStarted'==$eventType;
	}
	
	private function eventTypeIsSourceOfWorkflowIdButNotSignalName($eventType)
	{
		switch ($eventType) {
			case 'StartChildWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionInitiated':
				return true;
			default:
				return false;
		}
	}
	
	private function eventTypeIsSourceOfWorkflowIdAndSignalName($eventType)
	{
		switch ($eventType) {
			case 'SignalExternalWorkflowExecutionInitiated':
				return true;
			default:
				return false;
		}
	}
	
	private function eventTypeUsesWorkflowId($eventType)
	{
		switch ($eventType) {
			case 'ChildWorkflowExecutionCompleted':
			case 'ChildWorkflowExecutionFailed':
			case 'ChildWorkflowExecutionTimedOut':
			case 'ChildWorkflowExecutionCanceled':
			case 'ChildWorkflowExecutionTerminated':
			case 'ChildWorkflowExecutionStarted':
			case 'StartChildWorkflowExecutionFailed':
			case 'StartChildWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionFailed':
			case 'ExternalWorkflowExecutionCancelRequested':
			case 'SignalExternalWorkflowExecutionInitiated':
			case 'ExternalWorkflowExecutionSignaled':
			case 'SignalExternalWorkflowExecutionFailed':
				return true;
			default:
				return false;
		}
	}
	
	public function eventTypeUsesWorkflowIdButNotSignalName($eventType)
	{
		switch ($eventType) {
			case 'ChildWorkflowExecutionCompleted':
			case 'ChildWorkflowExecutionFailed':
			case 'ChildWorkflowExecutionTimedOut':
			case 'ChildWorkflowExecutionCanceled':
			case 'ChildWorkflowExecutionTerminated':
			case 'ChildWorkflowExecutionStarted':
			case 'StartChildWorkflowExecutionFailed':
			case 'StartChildWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionFailed':
			case 'ExternalWorkflowExecutionCancelRequested':
				return true;
			default:
				return false;
		}
	}
	
	private function eventTypeUsesWorkflowIdAndSignalName($eventType)
	{
		switch ($eventType) {
			case 'SignalExternalWorkflowExecutionInitiated':
			case 'ExternalWorkflowExecutionSignaled':
			case 'SignalExternalWorkflowExecutionFailed':
				return true;
			default:
				return false;
		}
	}
	
	private function eventHistoryContainsMinimumForEventType($eventType)
	{
		$requiredEventType = $this->eventTypeMinimallyRequiredForEventType(
			$eventType);
		
		if (!$requiredEventType) {
			return true;
		}
		
		return $this->latestEventIdForEventTypeIsKnown($requiredEventType);
	}
	
	
	private function eventHistoryContainsMinimumForEventTypeAndActivityId(
		$eventType,$activityId)
	{
		//scheduled only requires that a decision task was completed
		if ('ActivityTaskScheduled'==$eventType) {
			return $this->latestEventIdForEventTypeIsKnown(
				'DecisionTaskCompleted');
		}
		
		if (!$this->eventTypeIsSourceOfActivityId($eventType)
			&& !$this->activityIdIsKnown($activityId)
		) {
			return false;
		}
		
		$requiredEventType = $this->eventTypeMinimallyRequiredForEventType(
			$eventType);
		
		if ('ActivityTaskStarted'==$requiredEventType) {
			if (!isset($this->latestActivityIdEvents[$activityId]
				['ActivityTaskStarted'])
			) {
				return false;
			} elseif ($this->latestActivityIdEvents[$activityId]
					['ActivityTaskStarted'] <
				$this->latestActivityIdEvents[$activityId]
					['ActivityTaskScheduled']
				//TODO cannot assume Scheduled is known due to partial history
			) {
				return false;
			}
		}
		
		return true;
	}
	
	private function eventHistoryContainsMinimumForEventTypeAndWorkflowId(
		$eventType,$workflowId)
	{
		if ('StartChildWorkflowExecutionInitiated'==$eventType
			|| 'RequestCancelExternalWorkflowExecutionInitiated'==$eventType
			|| 'SignalExternalWorkflowExecutionInitiated'==$eventType
		) {
			return $this->latestEventIdForEventTypeIsKnown(
				'DecisionTaskCompleted');
		}
		
		if (!$this->workflowIdIsKnown($workflowId)) {
			return false;
		}
		
		$requiredEventType = $this->eventTypeMinimallyRequiredForEventType(
			$eventType);
		
		if (!isset($this->latestWorkflowIdEvents[$workflowId]
			[$requiredEventType])
		) {
			return false;
		}
		
		if ('ChildWorkflowExecutionStarted'==$requiredEventType) {
			//started event must exist latest than initiated event
			if ($this->latestWorkflowIdEvents[$workflowId]
					['ChildWorkflowExecutionStarted'] <
				$this->latestWorkflowIdEvents[$workflowId]
				['StartChildWorkflowExecutionInitiated']
			) {
				return false;	
			}
		}
		
		return true;
	}
	
	private function eventHistoryContainsMinimumForEventTypeWorkflowAndSignal(
		$eventType,$workflowId,$signalName)
	{
		if ('SignalExternalWorkflowExecutionInitiated'==$eventType) {
			return $this->latestEventIdForEventTypeIsKnown(
				'DecisionTaskCompleted'); 
		}
		
		if (!$this->workflowIdIsKnown($workflowId)) {
			return false;
		}
		
		if (!isset($this->latestWorkflowIdAndSignalNameEvents[$workflowId]
			[$signalName])
		) {
			return false;
		}
		
		//if we have the signal started but none ever completed, then it is
		//safe
		if (!isset($this->latestWorkflowIdAndSignalNameEvents[$workflowId]
			[$signalName][$eventType])
		) {
			return true;
		}
		
		//done making sure the keys are set
		//now make sure if it is set, that it is later than a terminated event
		if ($this->latestWorkflowIdAndSignalNameEvents[$workflowId]
			[$signalName][$eventType] > 
			$this->latestWorkflowIdAndSignalNameEvents[$workflowId]
				[$signalName]['SignalExternalWorkflowExecutionInitiated']
		) {
			return false;
		}
		
		return true;
	}
	private function eventTypeReferenceForScheduledEventId($eventType)
	{
		switch ($eventType) {
			case 'DecisionTaskStarted':
			case 'DecisionTaskTimedOut':
			case 'DecisionTaskCompleted':
				return 'DecisionTaskScheduled';
				break;
			case 'ActivityTaskStarted':
			case 'ActivityTaskCompleted':
			case 'ActivityTaskFailed':
			case 'ActivityTaskTimedOut':
			case 'ActivityTaskCanceled':
				return 'ActivityTaskScheduled';
				break;
		}
		
		return false;
	}
	
	private function eventTypeReferenceForStartedEventId($eventType)
	{
		switch ($eventType) {
			case 'DecisionTaskTimedOut':
			case 'DecisionTaskCompleted':
				return 'DecisionTaskStarted';
				break;
			case 'ActivityTaskCompleted':
			case 'ActivityTaskFailed':
			case 'ActivityTaskTimedOut':
			case 'ActivityTaskCanceled':
				return 'ActivityTaskStarted';
				break;
			case 'TimerFired':
			case 'TimerCanceled':
				return 'TimerStarted';
				break;
			case 'ChildWorkflowExecutionCompleted':
			case 'ChildWorkflowExecutionFailed':
			case 'ChildWorkflowExecutionTimedOut':
			case 'ChildWorkflowExecutionCanceled':
			case 'ChildWorkflowExecutionTerminated':
				return 'ChildWorkflowExecutionStarted';
				break;
		}
		
		return false;
	}
	
	private function eventTypeReferenceForInitiatedEventId($eventType)
	{
		switch ($eventType) {
			case 'ChildWorkflowExecutionStarted':
			case 'ChildWorkflowExecutionCompleted':
			case 'ChildWorkflowExecutionFailed':
			case 'ChildWorkflowExecutionTimedOut':
			case 'ChildWorkflowExecutionCanceled':
			case 'ChildWorkflowExecutionTerminated':
			case 'StartChildWorkflowExecutionFailed':
				return 'StartChildWorkflowExecutionInitiated';
				break;
			case 'ExternalWorkflowExecutionCancelRequested':
			case 'RequestCancelExternalWorkflowExecutionFailed':
				return 'RequestCancelExternalWorkflowExecutionInitiated';
				break;
			case 'ExternalWorkflowExecutionSignaled':
			case 'SignalExternalWorkflowExecutionFailed':
				return 'SignalExternalWorkflowExecutionInitiated';
				break;
		}
		
		return false;
	}
	
	private function eventTypeRequiresDecisionTaskCompletedEventId(
		$eventType)
	{
		switch ($eventType) {
			case 'WorkflowExecutionCompleted':
			case 'CompleteWorkflowExecutionFailed':
			case 'WorkflowExecutionFailed':
			case 'FailWorkflowExecutionFailed':
			case 'WorkflowExecutionCanceled':
			case 'CancelWorkflowExecutionFailed':
			case 'WorkflowExecutionContinuedAsNew':
			case 'ContinueAsNewWorkflowExecutionFailed':
			case 'ActivityTaskScheduled':
			case 'ActivityTaskCancelRequested':
			case 'MarkerRecorded':
			case 'RecordMarkerFailed':
			case 'TimerStarted':
			case 'TimerCanceled':
			case 'SignalExternalWorkflowExecutionInitiated':
			case 'SignalExternalWorkflowExecutionFailed':
			case 'RequestCancelExternalWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionFailed':
			case 'ScheduleActivityTaskFailed':
			case 'RequestCancelActivityTaskFailed':
			case 'StartTimerFailed':
			case 'CancelTimerFailed':
			case 'StartChildWorkflowExecutionFailed':
			case 'StartChildWorkflowExecutionInitiated':
				return true;
			default:
				return false;
		}
	}
	
	private $eventHistory = array();
	
	public function getEventHistory()
	{
		return $this->eventHistory;
	}
	
	public function setEventHistory($value)
	{
		$this->eventHistory = [];
		
		$this->resetReferenceHistory();
		foreach ($value as $event) {
			$this->verifyAndAddEventToReferenceHistory($event);
			$this->eventHistory[] = $event;
		}
	}
	
	private function resetReferenceHistory()
	{
		$this->latestEvents = array();
		$this->latestActivityIdEvents = array();
		$this->latestTimerIdEvents = [];
		$this->latestWorkflowIdAndSignalNameEvents = [];
		$this->latestWorkflowIdEvents = [];
	}
	
	private function verifyAndAddEventToReferenceHistory($event)
	{
		if ('TimerFired'==$event['eventType']) {
			$this->assertTimerEventDoesNotFireTooSoon($event);
		}
		$this->assertContextIdIsPresentIfRequired($event);
		$this->assertSignalNameIsPresentIfRequired($event);
		$this->assertEventContextIdIsNotAlreadyActive($event);
		$this->assertEventTimestampIsValid($event);
		$this->addEventToReferenceHistory($event);
	}
	
	private function assertEventTimestampIsValid($event)
	{
		$eventTimestamp = $event['eventTimestamp'];
		$latestEvent = $this->getLatestEvent();
		if (null === $latestEvent) {
			return;
		}
		
		$latestEventTimestamp = $latestEvent['eventTimestamp'];
		if ($latestEventTimestamp>$eventTimestamp) {
			throw new \InvalidArgumentException(
				"Event has timestamp of $eventTimestamp which is earlier"
				." the current latest event's timestamp of"
				." $latestEventTimestamp.");
		}
	}
	
	private function assertTimerEventDoesNotFireTooSoon($event)
	{
		$timerFiredMinEventTimestamp
			= $this->getMinEventTimestampForTimerFiredEvent($event);
		
		if ($event['eventTimestamp']<$timerFiredMinEventTimestamp) {
			throw new \InvalidArgumentException(
				"TimerFired event contains eventTimestamp that is less"
				." than the sum of the TimerStarted event's eventTimestamp"
				." and the startToFireTimeout value."
				);
		}
	}
	
	private function getMinEventTimestampForTimerFiredEvent($event)
	{
		$eventType = 'TimerFired';
		$eventAttributesKey = $this->getEventAttributesKey($eventType);
		$timerId = $event[$eventAttributesKey]['timerId'];
		$timerStartedEventId = $this->getLatestEventIdForTimerIdEventType(
			$timerId,'TimerStarted');
		
		$eventHistory = $this->getEventHistory();
		$timerStartedEvent = $eventHistory[$timerStartedEventId-1];
		$timerStartedEventAttributeKey
			= $this->getEventAttributesKey('TimerStarted');
		$startToFireTimeout = $timerStartedEvent
			[$timerStartedEventAttributeKey]['startToFireTimeout'];
		$timerStartedEventTimestamp = $timerStartedEvent['eventTimestamp'];
		
		$timerFiredMinEventTimestamp = $startToFireTimeout
			+$timerStartedEventTimestamp;
		
		return $timerFiredMinEventTimestamp;
	}
	
	private function assertContextIdIsPresentIfRequired($event)
	{
		$eventType = $event['eventType'];
		if (!$this->eventTypeRequiresContextId($eventType)) {
			return;
		}
		
		$eventAttributesKey = $this->getEventAttributesKey($eventType);
		$contextIdKey = $this->getContextIdKeyForEventType($eventType);
		if (!isset($event[$eventAttributesKey])
			|| !isset($event[$eventAttributesKey][$contextIdKey])
		) {
			throw new \InvalidArgumentException(
				"$eventType requires a $contextIdKey, but none was provided."
			);
		}
	}
	
	private function assertEventContextIdIsNotAlreadyActive($event)
	{
		$eventType = $event['eventType'];
		$eventAttributes = $event[$this->getEventAttributesKey($eventType)];
		
		//verify no duplicates
		if ('ActivityTaskScheduled'==$eventType
			&& isset($eventAttributes['activityId'])
		) {
			$activityId = $eventAttributes['activityId'];
			if ($this->activityIdIsKnown($activityId)) {
				$previousActivityTaskStillActive = true;
				foreach ($this->latestActivityIdEvents[$activityId] as
					$latestEventType => $latestEventId
				) {
					//MUST have the activity task ended
					switch ($latestEventType) {
						case 'ActivityTaskCompleted':
						case 'ActivityTaskTimedOut':
						case 'ActivityTaskFailed':
						case 'ActivityTaskCanceled':
							$previousActivityTaskStillActive = false;
							break 2;
					}
				}
				
				if ($previousActivityTaskStillActive) {
					throw new \InvalidArgumentException(
						"Failed to add ActivityTaskScheduled event to history"
						." since a previous event with activityId of"
						." $activityId has not ended. Event History:\n"
						.print_r($this->eventHistory,true)
					);
				}
			}
		} elseif ('TimerStarted'==$eventType
			&& isset($eventAttributes['timerId'])
		) {
			$timerId = $eventAttributes['timerId'];
			if ($this->timerIdIsKnown($timerId)) {
				$previousTimerStillActive = true;
				foreach ($this->latestTimerIdEvents[$timerId] as
					$latestEventType => $latestEventId
				) {
					//must have the timer ended
					switch ($latestEventType) {
						case 'TimerFired':
						case 'TimerCanceled':
							$previousTimerStillActive = false;
							break 2;
					}
				}
				
				if ($previousTimerStillActive) {
					throw new \InvalidArgumentException(
						"Failed to add TimerStarted event to history"
						." since a previous event with timerId of"
						." \"$timerId\" has not ended. Event History:\n"
						.print_r($this->eventHistory,true)
					);
				}
			}
		} elseif ('StartChildWorkflowExecutionInitiated'==$eventType
			&& isset($eventAttributes['workflowId'])
		) {
			$workflowId = $eventAttributes['workflowId'];
			if ($this->workflowIdIsKnown($workflowId)) {
				$previousChildWorkflowStillActive = true;
				foreach ($this->latestWorkflowIdEvents[$workflowId] as
					$latestEventType => $latestEventId
				) {
					$terminatingEventTypes = $this
						->eventTypesThatReferenceChildWorkflowStartedEventId();
					$terminatingEventTypes[]
						= 'StartChildWorkflowExecutionFailed';
					if (in_array($latestEventType,$terminatingEventTypes)) {
						$previousChildWorkflowStillActive = false;
						break;
					}
				}
			
				if ($previousChildWorkflowStillActive) {
					throw new \InvalidArgumentException(
						"Failed to add StartChildWorkflowExecutionInitiated"
						." event to history"
						." since a previous event with workflowId of"
						." \"$workflowId\" and eventId of"
						." \"$latestEventId\""
						." has not ended. Event History:\n"
						.print_r($this->eventHistory,true)
					);
				}
			}
		} elseif ('RequestCancelExternalWorkflowExecutionInitiated'==$eventType
			&& isset($eventAttributes['workflowId'])
		) {
			$workflowId = $eventAttributes['workflowId'];
			if ($this->workflowIdIsKnown($workflowId)) {
				$previousRequestCancelStillActive = true;
				$terminatingEventTypes = [
					'RequestCancelExternalWorkflowExecutionFailed',
					'ExternalWorkflowExecutionCancelRequested'
				];
				foreach ($this->latestWorkflowIdEvents[$workflowId] as
					$latestEventType => $latestEventId
				) {
					if (in_array($latestEventType,$terminatingEventTypes)) {
						$previousRequestCancelStillActive = false;
						break;
					}
				}
				
				if ($previousRequestCancelStillActive) {
					throw new \InvalidArgumentException(
						"Failed to add"
						." RequestCancelExternalWorkflowExecutionInitiated"
						." event to history since a previous event with"
						." workflowId of \"$workflowId\" and eventId"
						." \"$latestEventId\" has not ended."
						." Event History:\n"
						.print_r($this->eventHistory,true)
					);
				}
			}
		} elseif ('SignalExternalWorkflowExecutionInitiated'==$eventType) {
			$workflowId = $eventAttributes['workflowId'];
			$signalName = $eventAttributes['signalName'];
			if ($this->workflowIdIsKnown($workflowId)
				&& isset($this->latestWorkflowIdAndSignalNameEvents[$workflowId]
					[$signalName])
			) {
				$previousSignalExternalStillActive = true;
				$terminatingEventTypes = [
					'SignalExternalWorkflowExecutionFailed',
					'ExternalWorkflowExecutionSignaled',
				];
				foreach ($this->latestWorkflowIdAndSignalNameEvents
					[$workflowId][$signalName] as
					$latestEventType => $latestEventId
				) {
					if (in_array($latestEventType,$terminatingEventTypes)) {
						$previousSignalExternalStillActive = false;
						break;
					}
				}
				if ($previousSignalExternalStillActive) {
					throw new \InvalidArgumentException(
						"Failed to add "
						." SignalExternalWorkflowExecutionInitiated"
						." event to history since a previous event with"
						." workflowId of \"$workflowId\", signalName"
						." \"$signalName\" and eventId"
						." \"$latestEventId\" has not ended."
						." Event History:\n"
						.print_r($this->eventHistory,true)
						." Latest events for workflowId \"$workflowId\"\n"
						.print_r(
							$this->latestWorkflowIdEvents[$workflowId],
							true
						)
					);
				}
			}
		}
	}
	
	private $latestWorkflowIdAndSignalNameEvents = array();
	
	private function assertSignalNameIsPresentIfRequired($event)
	{
		$eventType = $event['eventType'];
		if ('SignalExternalWorkflowExecutionInitiated'!=$eventType) {
			return;
		}
		
		$eventAttributesKey = $this->getEventAttributesKey($eventType);
		
		if (!isset($event[$eventAttributesKey]['signalName'])) {
			throw new \InvalidArgumentException(
				"SignalExternalWorkflowExecutionInitiated requires a"
				." signalName in addition to a workflowId, but none was"
				." provided with event."
			);
		}
	}
	
	private function addEventToReferenceHistory($event)
	{
		$this->addEventToLatestEvents($event);
		$this->addEventToLatestContextIdEvents($event);
	}
	
	private function addEventToLatestEvents($event)
	{
		$this->latestEvents[$event['eventType']]=$event['eventId'];
	}
	
	private $latestEvents = array();
	
	private $latestActivityIdEvents = array();
	
	private function addEventToLatestActivityIdEvents($event,$activityId)
	{
		$this->latestActivityIdEvents[$activityId][$event['eventType']]
			= $event['eventId'];
	}
	
	private $latestTimerIdEvents = array();
	
	private function addEventToLatestTimerIdEvents($event,$timerId)
	{
		$this->latestTimerIdEvents[$timerId][$event['eventType']]
			= $event['eventId'];
	}
	
	private $latestWorkflowIdEvents = array();
	
	private function addEventToLatestWorkflowIdEvents($event,$workflowId)
	{
		$this->latestWorkflowIdEvents[$workflowId][$event['eventType']]
			= $event['eventId'];
	}
	
	private function addEventToLatestWorkflowIdAndSignalNameEvents($event,
		$workflowId,$signalName)
	{
		$this->latestWorkflowIdAndSignalNameEvents[$workflowId][$signalName]
			[$event['eventType']]
			= $event['eventId'];
	}
	
	/**
	 * In order to create an unbroken chain of events for a given contextId,
	 * we need to record that each event is part of the contextId, even if
	 * the event itself doesn't contain any information about it.
	 * @param array $event
	 */
	
	private function addEventToLatestContextIdEvents($event)
	{
		$eventType = $event['eventType'];
		$eventAttributes = $event[$this->getEventAttributesKey($eventType)];
		
		if (isset($eventAttributes['activityId'])) {
			//this only catches ActivityTaskScheduled
			$this->addEventToLatestActivityIdEvents(
				$event, $eventAttributes['activityId']);
		}
		
		//for other events, we need to find the scheduled event
		if (isset($eventAttributes['scheduledEventId'])) {
			$scheduledEventId = $eventAttributes['scheduledEventId'];
			if ('ActivityTaskScheduled'
				== $this->eventTypeReferenceForScheduledEventId($eventType)
			) {
				foreach ($this->latestActivityIdEvents as
					$activityId => $reference
				) {
					//find the eventId that matches the scheduled eventId
					//if we find it, we know we have the right activityId
					foreach ($reference as $eventId) {
						if ($eventId == $scheduledEventId) {
							$this->addEventToLatestActivityIdEvents(
								$event, $activityId);
							break;
						}
					}
				}
			}
		}
		
		if (isset($eventAttributes['timerId'])) {
			//this only catches TimerStarted
			$this->addEventToLatestTimerIdEvents(
				$event, $eventAttributes['timerId']);
		}
		
		//for events that reference TimerStarted, we need to find the event
		if (isset($eventAttributes['startedEventId'])) {
			$startedEventId = $eventAttributes['startedEventId'];
			if ('TimerStarted'
				== $this->eventTypeReferenceForStartedEventId($eventType)
			) {
				foreach ($this->latestTimerIdEvents as
					$timerId => $reference
				) {
					//find the eventId that matches the started eventId
					//if we find it, we know we have the right timerId
					foreach ($reference as $eventId) {
						if ($eventId == $startedEventId) {
							$this->addEventToLatestTimerIdEvents(
								$event, $timerId);
							break;
						}
					}
				}
			}
		}
		
		if (isset($eventAttributes['workflowId'])) {
			//this only catches StartChildWorkflowExecutionInitiated
			$this->addEventToLatestWorkflowIdEvents($event,
				$eventAttributes['workflowId']);
			
			if (isset($eventAttributes['signalName'])) {
				$this->addEventToLatestWorkflowIdAndSignalNameEvents(
					$event,
					$eventAttributes['workflowId'],
					$eventAttributes['signalName']
				);
			}
		}
		
		
		if (isset($eventAttributes['initiatedEventId'])) {
			$initiatedEventId = $eventAttributes['initiatedEventId'];
			$referencedEventType
				= $this->eventTypeReferenceForInitiatedEventId($eventType);
			if ('StartChildWorkflowExecutionInitiated'
				== $referencedEventType
				|| 'RequestCancelExternalWorkflowExecutionInitiated'
				== $referencedEventType
				|| 'SignalExternalWorkflowExecutionInitiated'
				== $referencedEventType
			) {
				foreach ($this->latestWorkflowIdEvents as
					$workflowId => $reference
				) {
					//find the eventId that matches the initiated eventId
					//if we find it, we know we have the right workflowId
					foreach ($reference as $eventId) {
						if ($eventId == $initiatedEventId) {
							$this->addEventToLatestWorkflowIdEvents(
								$event, $workflowId);
							break;
						}
					}
				}
			}
			//signal names
			if ('SignalExternalWorkflowExecutionInitiated'
				== $referencedEventType
			) {
				foreach ($this->latestWorkflowIdAndSignalNameEvents as
					$workflowId => $signalNameEvents
				) {
					foreach ($signalNameEvents
						as $signalName => $eventReference
					) {
						foreach ($eventReference as $eventId) {
							if ($eventId == $initiatedEventId) {
								$this->addEventToLatestWorkflowIdAndSignalNameEvents(
									$event,$workflowId,$signalName);
							}
						}
					}
				}
			}
		}
	}
	
	private function getEventAttributesKey($eventType)
	{
		return lcfirst($eventType).'EventAttributes';
	}
	
	private function activityIdIsKnown($activityId)
	{
		return isset($this->latestActivityIdEvents[$activityId]);
	}
	
	private function timerIdIsKnown($timerId)
	{
		return isset($this->latestTimerIdEvents[$timerId]);
	}
	
	private function workflowIdIsKnown($workflowId)
	{
		return isset($this->latestWorkflowIdEvents[$workflowId]);
	}
	
	private function latestEventIdForEventTypeIsKnown($eventType)
	{
		return isset($this->latestEvents[$eventType]);
	}
	
	private function getLatestEventIdForEventType($eventType)
	{
		return $this->latestEvents[$eventType];
	}
	
	private function getLatestEventIdForActivityIdEventType($activityId,
		$eventType)
	{
		return $this->latestActivityIdEvents[$activityId][$eventType];
	}
	
	private function getLatestEventIdForTimerIdEventType($timerId,
		$eventType)
	{
		return $this->latestTimerIdEvents[$timerId][$eventType];
	}
	
	private function getLatestEventIdForWorkflowIdEventType($workflowId,
		$eventType)
	{
		return $this->latestWorkflowIdEvents[$workflowId][$eventType];
	}
	
	private function getLatestEventIdForWorkflowIdAndSignalNameEventType(
		$workflowId,$signalName,$eventType)
	{
		return $this->latestWorkflowIdAndSignalNameEvents[$workflowId]
			[$signalName][$eventType];
	}
}