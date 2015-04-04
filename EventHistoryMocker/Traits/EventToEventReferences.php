<?php

namespace swf4php\EventHistoryMocker\Traits;


trait EventToEventReferences
{
	/**
	 * @codeCoverageIgnore Any tests would be tautologies
	 * @return multitype:string
	 */
	private function eventTypesThatReferenceActivityTaskScheduled()
	{
		return [
			'ActivityTaskStarted',
			'ActivityTaskCompleted',
			'ActivityTaskTimedOut',
			'ActivityTaskFailed',
			'ActivityTaskCanceled',
		];
	}
	
	/**
	 * @codeCoverageIgnore Any tests would be tautologies
	 * @return multitype:string
	 */
	
	private function eventTypesThatReferenceActivityTaskStarted()
	{
		return [
			'ActivityTaskCompleted',
			'ActivityTaskTimedOut',
			'ActivityTaskFailed',
			'ActivityTaskCanceled',
		];
	}
	
	/**
	 * @codeCoverageIgnore Any tests would be tautologies
	 * @return multitype:string
	 */
	
	private function eventTypesThatReferenceTimerStarted()
	{
		return [
			'TimerFired',
			'TimerCanceled',
		];
	}
	
	/**
	 * @codeCoverageIgnore Any tests would be tautologies
	 * @return multitype:string
	 */
	
	private function eventTypesThatRequireMoreThanWorkflowStarted()
	{
		return [
			'WorkflowExecutionCompleted',
			'CompleteWorkflowExecutionFailed',
			'WorkflowExecutionFailed',
			'FailWorkflowExecutionFailed',
			'WorkflowExecutionCanceled',
			'CancelWorkflowExecutionFailed',
			'WorkflowExecutionContinuedAsNew',
			'ContinueAsNewWorkflowExecutionFailed',
			'DecisionTaskStarted',
			'DecisionTaskCompleted',
			'DecisionTaskTimedOut',
			'ActivityTaskScheduled',
			'ScheduleActivityTaskFailed',
			'ActivityTaskStarted',
			'ActivityTaskCompleted',
			'ActivityTaskFailed',
			'ActivityTaskTimedOut',
			'ActivityTaskCanceled',
			'ActivityTaskCancelRequested',
			'RequestCancelActivityTaskFailed',
			'MarkerRecorded',
			'RecordMarkerFailed',
			'TimerStarted',
			'StartTimerFailed',
			'TimerFired',
			'TimerCanceled',
			'CancelTimerFailed',
			'StartChildWorkflowExecutionInitiated',
			'StartChildWorkflowExecutionFailed',
			'ChildWorkflowExecutionStarted',
			'ChildWorkflowExecutionCompleted',
			'ChildWorkflowExecutionFailed',
			'ChildWorkflowExecutionTimedOut',
			'ChildWorkflowExecutionCanceled',
			'ChildWorkflowExecutionTerminated',
			'SignalExternalWorkflowExecutionInitiated',
			'SignalExternalWorkflowExecutionFailed',
			'ExternalWorkflowExecutionSignaled',
			'RequestCancelExternalWorkflowExecutionInitiated',
			'RequestCancelExternalWorkflowExecutionFailed',
			'ExternalWorkflowExecutionCancelRequested',
		];
	}
	
	private function eventsThatRequireOnlyDecisionTaskCompleted()
	{
		return [
			'WorkflowExecutionCompleted',
			'CompleteWorkflowExecutionFailed',
			'WorkflowExecutionFailed',
			'FailWorkflowExecutionFailed',
			'WorkflowExecutionCanceled',
			'CancelWorkflowExecutionFailed',
			'WorkflowExecutionContinuedAsNew',
			'ContinueAsNewWorkflowExecutionFailed',
			'ActivityTaskScheduled',
			'ScheduleActivityTaskFailed',
			'ActivityTaskCancelRequested',
			'RequestCancelActivityTaskFailed',
			'MarkerRecorded',
			'RecordMarkerFailed',
			'TimerStarted',
			'StartTimerFailed',
			'CancelTimerFailed',
			'StartChildWorkflowExecutionInitiated',
			//'StartChildWorkflowExecutionFailed',
			'SignalExternalWorkflowExecutionInitiated',
			//'SignalExternalWorkflowExecutionFailed',
			'RequestCancelExternalWorkflowExecutionInitiated',
			//'RequestCancelExternalWorkflowExecutionFailed',
		];
	}
	
	private function eventTypesThatReferenceChildWorkflowInitiatedEventId()
	{
		return [
			'ChildWorkflowExecutionStarted',
			'ChildWorkflowExecutionCompleted',
			'ChildWorkflowExecutionFailed',
			'ChildWorkflowExecutionTimedOut',
			'ChildWorkflowExecutionCanceled',
			'ChildWorkflowExecutionTerminated',
			'StartChildWorkflowExecutionFailed',
		];
	}
	
	private function eventTypesThatReferenceChildWorkflowStartedEventId()
	{
		return [
			'ChildWorkflowExecutionCompleted',
			'ChildWorkflowExecutionFailed',
			'ChildWorkflowExecutionTimedOut',
			'ChildWorkflowExecutionCanceled',
			'ChildWorkflowExecutionTerminated',
		];
	}
	
	private function eventTypesThatReferenceChildWorkflowInitiatedOnly()
	{
		return [
			'ChildWorkflowExecutionStarted',
			'StartChildWorkflowExecutionFailed',
		];
	}
}