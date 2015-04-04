<?php

namespace SwfHistoryMocker;
use SwfHistoryMocker\traits\ValidSwfEventTypes;

class DesiredEvent
{
	use ValidSwfEventTypes;
	private $eventAttributes = array();
	public function setEventAttributes($eventAttributes)
	{
		$this->eventAttributes = $eventAttributes;
		
		if (null !== $this->contextId
			&& null !== $eventAttributesContextId = $this->getContextId()
		) {
			if ($this->contextId !== $eventAttributesContextId) {
				throw new \InvalidArgumentException(
					"\"$eventAttributesContextId\" was provided as a"
					." contextId, but does not match \"$this->contextId\""
					." which was set via setContextId()");
			}
		}
		if (null !== $this->signalName
			&& null !== $eventAttributesSignalName
				= $this->getSignalName()
		) {
			if ($this->signalName !== $eventAttributesSignalName) {
				throw new \InvalidArgumentException(
					"\"$eventAttributesSignalName\" was provided as a"
					." signalName, but does not match "
					."\"$this->signalName\""
					." which was set via setSignalName()");
			}
		}
	}
	
	public function getEventAttributes()
	{
		return $this->eventAttributes;
	}
	
	private $eventType;
	public function setEventType($eventType)
	{
		if (!$this->isValidEventType($eventType)) {
			throw new \InvalidArgumentException();
		}
		$this->eventType = $eventType;
	}
	
	public function getEventType()
	{
		return $this->eventType;
	}
	
	private $contextId;
	
	/**
	 * Set workflowId, timerId, or activityId, as appropriate, to link
	 * this DesiredEvent to it's ancestor in the workflow history.
	 * @param string $contextId
	 */
	
	public function setContextId($contextId)
	{
		//TODO add check for valid values
		if (null === $this->contextId
			&& null !== $eventAttributeContextId = $this->getContextId()
		) {
			//this means we have an event attribute with the contextId
			if ($eventAttributeContextId !== $contextId) {
				throw new \InvalidArgumentException(
					"\"$contextId\" does not match value "
					."\"$eventAttributeContextId\" set in eventAttributes");
			}
		}
		
		$this->contextId = $contextId;
	}
	
	public function getContextId()
	{
		$eventAttributes = $this->getEventAttributes();
		$contextIdKey = null;
		switch ($this->getEventType()) {
			case 'ActivityTaskScheduled':
			case 'ScheduleActivityTaskFailed':
			case 'RequestCancelActivityTaskFailed':
			case 'ActivityTaskCancelRequested':
				$contextIdKey = 'activityId';
				break;
			case 'StartChildWorkflowExecutionInitiated':
			case 'StartChildWorkflowExecutionFailed':
			case 'SignalExternalWorkflowExecutionInitiated':
			case 'SignalExternalWorkflowExecutionFailed':
			case 'RequestCancelExternalWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionFailed':
				$contextIdKey = 'workflowId';
				break;
			case 'TimerStarted':
			case 'TimerFired':
			case 'TimerCanceled':
			case 'StartTimerFailed':
			case 'CancelTimerFailed':
				$contextIdKey = 'timerId';
				break;
		}
		if ($contextIdKey && isset($eventAttributes[$contextIdKey])) {
			return $eventAttributes[$contextIdKey];
		}
		return $this->contextId;
	}
	
	private $signalName;
	
	/**
	 * Events involving signals need more than just a contextId to 
	 * unambiguously identify them.
	 * @param string $contextSignalName
	 */
	
	public function setSignalName($contextSignalName)
	{
		//TODO add check for valid values
		
		if (null === $this->signalName
			&& null !== $eventAttributeSignalName
				= $this->getSignalName()
		) {
			//this means we have an event attribute with the contextId
			if ($eventAttributeSignalName !== $contextSignalName) {
				throw new \InvalidArgumentException(
					"\"$contextSignalName\" does not match value "
					."\"$eventAttributeSignalName\" set in eventAttributes");
			}
		}
		
		$this->signalName = $contextSignalName;
	}
	
	public function getSignalName()
	{
		$eventAttributes = $this->getEventAttributes();
		if (isset($eventAttributes['signalName'])) {
			return $eventAttributes['signalName'];
		}
		
		return $this->signalName;
	}
	
	private $unixTimestamp;
	
	public function setUnixTimestamp($unixTimestamp)
	{
		if (null === $unixTimestamp) {
			$this->unixTimestamp = null;
		} elseif (!is_numeric($unixTimestamp) || $unixTimestamp <= 0) {
			throw new \InvalidArgumentException();
		} else {
			$this->unixTimestamp = $unixTimestamp;
		}
	}
	
	public function getUnixTimestamp()
	{
		return $this->unixTimestamp;
	}
	
	private $secondsSinceLatestAncestor;
	
	public function setSecondsSinceLatestAncestor($secondsSinceLatestAncestor)
	{
		if (null === $secondsSinceLatestAncestor) {
			$this->secondsSinceLatestAncestor = null;
		} elseif (!is_numeric($secondsSinceLatestAncestor)
			|| $secondsSinceLatestAncestor <= 0
		) {
			throw new \InvalidArgumentException();
		} else {
			$this->secondsSinceLatestAncestor = $secondsSinceLatestAncestor;
		}
	}
	
	public function getSecondsSinceLatestAncestor()
	{
		return $this->secondsSinceLatestAncestor;
	}
	
	private $secondsSinceLastEvent;
	
	public function setSecondsSinceLastEvent($secondsSinceLastEvent)
	{
		if (null === $secondsSinceLastEvent) {
			$this->secondsSinceLastEvent = null;
		} elseif (!is_numeric($secondsSinceLastEvent)
			|| $secondsSinceLastEvent <= 0
		) {
			throw new \InvalidArgumentException();
		} else {
			$this->secondsSinceLastEvent = $secondsSinceLastEvent;
		}
	}
	
	public function getSecondsSinceLastEvent()
	{
		return $this->secondsSinceLastEvent;
	}
	
	private $dateTime;
	
	public function setDateTime($dateTime)
	{
		//TODO add check for valid values
		$this->dateTime = $dateTime;
	}
	
	public function getDateTime()
	{
		return $this->dateTime;
	}
}