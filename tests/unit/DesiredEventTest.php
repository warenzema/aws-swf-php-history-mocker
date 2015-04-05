<?php

use SwfHistoryMocker\DesiredEvent;
use SwfHistoryMockerTests\Unit\SwfUnitTestCase;
use SwfHistoryMocker\traits\ValidSwfEventTypes;
class DesiredEventTest extends SwfUnitTestCase
{
	use ValidSwfEventTypes;
	public function providerGetAndSetMethods()
	{
		return array(
			array('eventAttributes',array('input'=>true)),
			array('eventType','ActivityTaskStarted'),
			array('eventType','WorkflowExecutionSignaled'),
			array('contextId','my-workflow-id'),
			array('signalName','whatever'),
			array('unixTimestamp','1234567'),
			array('secondsSinceLatestAncestor','3.5'),
			array('secondsSinceLastEvent','1.2'),
			array(
				'dateTime',
				DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2014-01-01 00:00:00'
				)
			),
		);
	}
	
	/**
	 * @dataProvider providerGetAndSetMethods
	 */
	
	public function testGetReturnsSet($commonMethodNamePart,$value)
	{
		$object = new DesiredEvent;
		$this->assertGetReturnsSet($object, $commonMethodNamePart, $value);
	}
	
	public function testThrowsExceptionForInvalidEventType()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$eventType = 'blah';
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
	}
	
	public function testByDefaultEventAttributesAreAnEmptyArray()
	{
		$DesiredEvent = new DesiredEvent();
		$eventAttributes = $DesiredEvent->getEventAttributes();
		$this->assertEquals(array(),$eventAttributes);
	}
	
	public function providerValidAndInvalidSecondsSince()
	{
		return [
			//null and non-zero positive numbers are valid
			[1,true],
			[null,true],
			[0.13,true],
			//non-null non-numbers or 0 or less numbers are invalid
			[0,false],
			[false,false],
			[-1,false],
			[new \stdClass(),false],
		];
	}
	
	/**
	 * @dataProvider providerValidAndInvalidSecondsSince
	 */
	
	public function testSecondsSinceLatestAncesterMustBeValidValue(
		$secondsSince,$valid)
	{
		if (!$valid) {
			$this->setExpectedException('\InvalidArgumentException');
		}
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setSecondsSinceLatestAncestor($secondsSince);
	}
	
	/**
	 * @dataProvider providerValidAndInvalidSecondsSince
	 */
	
	public function testSecondsSinceLatestEventMustBeValidValue($secondsSince,
		$valid)
	{
		if (!$valid) {
			$this->setExpectedException('\InvalidArgumentException');
		}
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setSecondsSinceLastEvent($secondsSince);
	}
	
	public function providerValidAndInvalidUnixTimestamps()
	{
		return [
			//null and non-zero positive numbers are valid
			[1,true],
			[null,true],
			[0.13,true],
			//non-null non-numbers or 0 or less numbers are invalid
			[0,false],
			[false,false],
			[-1,false],
			[new \stdClass(),false],
		];
	}
	
	/**
	 * @dataProvider providerValidAndInvalidUnixTimestamps
	 */
	
	public function testUnixTimestampMustBeValidValue($timestamp,$valid)
	{
		if (!$valid) {
			$this->setExpectedException('\InvalidArgumentException');
		}
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setUnixTimestamp($timestamp);
	}
	
	public function providerEventTypesThatCanSetContextIdFromEventAttributes()
	{
		return [
			['ActivityTaskScheduled','activityId'],
			['ScheduleActivityTaskFailed','activityId'],
			['RequestCancelActivityTaskFailed','activityId'],
			['ActivityTaskCancelRequested','activityId'],
		
			['StartChildWorkflowExecutionInitiated','workflowId'],
			['StartChildWorkflowExecutionFailed','workflowId'],
			['SignalExternalWorkflowExecutionInitiated','workflowId'],
			['SignalExternalWorkflowExecutionFailed','workflowId'],
			['RequestCancelExternalWorkflowExecutionInitiated','workflowId'],
			['RequestCancelExternalWorkflowExecutionFailed','workflowId'],
		
			['TimerStarted','timerId'],
			['TimerFired','timerId'],
			['TimerCanceled','timerId'],
			['StartTimerFailed','timerId'],
			['CancelTimerFailed','timerId'],
		];
	}
	
	/**
	 * @dataProvider providerEventTypesThatCanSetContextIdFromEventAttributes
	 * 
	 * @group setEventAttributes()
	 * @group setEventType()
	 * @group getContextId()
	 * @testdox setEventAttributes(contextId=>v)&setEventType=>getContextId()==v
	 */
	
	public function testSetEventAttributesContextIdSetsObjectContextId(
		$eventType,$contextIdKey)
	{
		$contextId = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setEventAttributes([
			$contextIdKey=>$contextId
		]);
		$this->assertEquals($contextId,$DesiredEvent->getContextId());
	}
	
	
	/**
	 * @dataProvider providerEventTypesThatCanSetContextIdFromEventAttributes
	 * 
	 * @group getEventAttributes()
	 * @group setEventType()
	 * @group getContextId()
	 * @testdox setContextId()&setEventType() does not affect getEventAttributes
	 */
	
	public function testContextIdDoesNotAutoSetEventAttributes($eventType)
	{
		$contextId = uniqid();
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setContextId($contextId);
		
		$this->assertEquals([],$DesiredEvent->getEventAttributes());
	}
	
	/**
	 * @dataProvider providerEventTypesThatCanSetContextIdFromEventAttributes
	 * 
	 * @group setEventAttributes()
	 * @group setEventType()
	 * @group setContextId()
	 * @testdox setEventAttributes([contextId=>v])&&setContextId(!=v)=>exception
	 */
	
	public function testExceptionIfEventAttributesAndContextIdSetButNotMatch(
		$eventType,$contextIdKey)
	{
		$this->setExpectedException('\InvalidArgumentException');
		
		$contextId1 = uniqid();
		$contextId2 = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setEventAttributes([
			$contextIdKey=>$contextId1
		]);
		$DesiredEvent->setContextId($contextId2);
	}
	
	/**
	 * @dataProvider providerEventTypesThatCanSetContextIdFromEventAttributes
	 * 
	 * @group setEventAttributes()
	 * @group setEventType()
	 * @group setContextId()
	 * @testdox setContextId(v)&&setEventAttributes([contextId=>!=v])=>exception
	 */
	
	public function testExceptionIfEventAttributesAndContextIdSetButNotMatch2(
		$eventType,$contextIdKey)
	{
		$this->setExpectedException('\InvalidArgumentException');
		
		$contextId1 = uniqid();
		$contextId2 = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setContextId($contextId2);
		$DesiredEvent->setEventAttributes([
			$contextIdKey=>$contextId1
		]);
	}
	
	/**
	 * @dataProvider providerEventTypesThatCanSetContextIdFromEventAttributes
	 * 
	 * @group setEventAttributes()
	 * @group setEventType()
	 * @group setContextId()
	 * @testdox setEventAttributes([contextId=>v])&&setContextId(v) works fine
	 */
	
	public function testNoProblemIfBothEventAttributeAndContextIdSetTheSame(
		$eventType,$contextIdKey)
	{
		$contextId = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		
		$DesiredEvent->setEventAttributes([
			$contextIdKey=>$contextId
		]);
		$DesiredEvent->setContextId($contextId);
		
		$this->assertEquals($contextId,$DesiredEvent->getContextId());
	}
	
	/**
	 * @dataProvider providerEventTypesThatCanSetContextIdFromEventAttributes
	 * 
	 * @group setEventAttributes()
	 * @group setEventType()
	 * @group setContextId()
	 * @testdox setContextId(v)&&setEventAttributes([contextId=>v]) works fine
	 */
	
	public function testNoProblemIfBothEventAttributeAndContextIdSetTheSame2(
		$eventType,$contextIdKey)
	{
		$contextId = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		
		$DesiredEvent->setContextId($contextId);
		$DesiredEvent->setEventAttributes([
			$contextIdKey=>$contextId
		]);
		
		$this->assertEquals($contextId,$DesiredEvent->getContextId());
	}
	
	/**
	 * @group setEventAttributes()
	 * @group getSignalName()
	 * @testdox setEventAttributes(signalName=>v)=>getSignalName()==v
	 */
	
	public function testGetSignalNameUsesEventAttributes()
	{
		$signalName = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		
		$DesiredEvent->setEventAttributes([
			'signalName'=>$signalName
		]);
		
		$this->assertEquals($signalName,$DesiredEvent->getSignalName());
	}
	
	/**
	 * @group getEventAttributes()
	 * @group setSignalName()
	 * @testdox setSignalName() does not affect getEventAttributes
	 */
	
	public function testSetSignalNameDoesNotAutoSetEventAttributes()
	{
		$signalName = uniqid();
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setSignalName($signalName);
		
		$this->assertEquals([],$DesiredEvent->getEventAttributes());
	}

	/**
	 * @group setEventAttributes()
	 * @group setSignalName()
	 * @testdox setEventAttributes(signalName=>v)&&setSignalName(!=v)=>ex
	 */
	
	public function testExceptionIfEventAttributesAndSignalNameSetButNotMatch()
	{
		$this->setExpectedException('\InvalidArgumentException');
		
		$signalName1 = uniqid();
		$signalName2 = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventAttributes([
			'signalName'=>$signalName1
		]);
		$DesiredEvent->setSignalName($signalName2);
	}

	/**
	 * @group setEventAttributes()
	 * @group setSignalName()
	 * @testdox setSignalName(!=v)&&setEventAttributes(signalName=>v)=>ex
	 */
	
	public function testExceptionIfEventAttributesAndSignalNameSetButNotMatch2()
	{
		$this->setExpectedException('\InvalidArgumentException');
		
		$signalName1 = uniqid();
		$signalName2 = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setSignalName($signalName2);
		$DesiredEvent->setEventAttributes([
			'signalName'=>$signalName1
		]);
	}
	
	/**
	 * @group setEventAttributes()
	 * @group setSignalName()
	 * @testdox setEventAttributes(signalName=>v)&&setSignalName(v)=>ok
	 */
	
	public function testNoProblemIfBothEventAttributeAndSignalNameSetTheSame()
	{
		$signalName = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		
		$DesiredEvent->setEventAttributes([
			'signalName'=>$signalName
		]);
		$DesiredEvent->setSignalName($signalName);
		
		$this->assertEquals($signalName,$DesiredEvent->getSignalName());
	}

	/**
	 * @group setEventAttributes()
	 * @group setSignalName()
	 * @testdox setSignalName(v)&&setEventAttributes(signalName=>v)=>ok
	 */
	
	public function testNoProblemIfBothEventAttributeAndSignalNameSetTheSame2()
	{
		$signalName = uniqid();
		
		$DesiredEvent = new DesiredEvent();
		
		$DesiredEvent->setSignalName($signalName);
		$DesiredEvent->setEventAttributes([
			'signalName'=>$signalName
		]);
		
		$this->assertEquals($signalName,$DesiredEvent->getSignalName());
	}
	
	public function providerWorkflowStarterEventsAndSecondsSinceAndValid()
	{
		return [
			['WorkflowExecutionStarted',1,false],
			['WorkflowExecutionContinuedAsNew',1,false],
			['WorkflowExecutionStarted',null,true],
			['WorkflowExecutionContinuedAsNew',null,true],
		];
	}
	
	/**
	 * @dataProvider providerWorkflowStarterEventsAndSecondsSinceAndValid
	 */
	
	public function testExceptionIfWorkflowExecutionStartedSecondsSinceLast(
		$eventType,$secondsSinceLastEvent,$valid)
	{
		if (!$valid) {
			$this->setExpectedException('\InvalidArgumentException');
		}
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setSecondsSinceLastEvent($secondsSinceLastEvent);
	}
	
	/**
	 * @dataProvider providerWorkflowStarterEventsAndSecondsSinceAndValid
	 */
	
	public function testExceptionIfWorkflowExecutionStartedSecondsSinceLast2(
		$eventType,$secondsSinceLastEvent,$valid)
	{
		if (!$valid) {
			$this->setExpectedException('\InvalidArgumentException');
		}
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setSecondsSinceLastEvent($secondsSinceLastEvent);
		$DesiredEvent->setEventType($eventType);
	}
	
	public function providerEventsAndWhetherTheyHaveAncestor()
	{
		$allEventTypes = $this->validEventTypes();
		
		$provider = [];
		foreach ($allEventTypes as $eventType) {
			$provider[] = [
				$eventType,1,(bool)$this
					->eventTypeMinimallyRequiredForEventType($eventType)
			];
		}
		
		return $provider;
	}
	
	/**
	 * @dataProvider providerEventsAndWhetherTheyHaveAncestor
	 */
	
	public function testExceptionIfEventTypeHasNoAncestor(
		$eventType,$secondsSinceLatestAncestor,$valid)
	{
		if (!$valid) {
			$this->setExpectedException('\InvalidArgumentException');
		}
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType($eventType);
		$DesiredEvent->setSecondsSinceLatestAncestor(
			$secondsSinceLatestAncestor);
	}
	
	/**
	 * @dataProvider providerEventsAndWhetherTheyHaveAncestor
	 */
	
	public function testExceptionIfEventTypeHasNoAncestor2(
		$eventType,$secondsSinceLatestAncestor,$valid)
	{
		if (!$valid) {
			$this->setExpectedException('\InvalidArgumentException');
		}
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setSecondsSinceLatestAncestor(
			$secondsSinceLatestAncestor);
		$DesiredEvent->setEventType($eventType);
	}
}