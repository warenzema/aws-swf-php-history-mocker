<?php

namespace SwfHistoryMocker\traits;

trait ValidSwfEventTypes
{
	public function validEventTypes()
	{
		return [
			'WorkflowExecutionStarted',
			'WorkflowExecutionCancelRequested',
			'WorkflowExecutionCompleted',
			'CompleteWorkflowExecutionFailed',
			'WorkflowExecutionFailed',
			'FailWorkflowExecutionFailed',
			'WorkflowExecutionTimedOut',
			'WorkflowExecutionCanceled',
			'CancelWorkflowExecutionFailed',
			'WorkflowExecutionContinuedAsNew',
			'ContinueAsNewWorkflowExecutionFailed',
			'WorkflowExecutionTerminated',
			'DecisionTaskScheduled',
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
			'WorkflowExecutionSignaled',
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
	public function isValidEventType($eventType)
	{
		return in_array($eventType,$this->validEventTypes());
	}
	
	protected function eventTypeMinimallyRequiredForEventType($eventType)
	{
		switch ($eventType) {
			case 'DecisionTaskStarted':
				return 'DecisionTaskScheduled';
			case 'DecisionTaskTimedOut':
			case 'DecisionTaskCompleted':
				return 'DecisionTaskStarted';
			case 'ActivityTaskStarted':
				return 'ActivityTaskScheduled';
			case 'ActivityTaskCompleted':
			case 'ActivityTaskFailed':
			case 'ActivityTaskTimedOut':
			case 'ActivityTaskCanceled':
				return 'ActivityTaskStarted';
			case 'TimerFired':
			case 'TimerCanceled':
				return 'TimerStarted';
			case 'ChildWorkflowExecutionCompleted':
			case 'ChildWorkflowExecutionFailed':
			case 'ChildWorkflowExecutionTimedOut':
			case 'ChildWorkflowExecutionCanceled':
			case 'ChildWorkflowExecutionTerminated':
				return 'ChildWorkflowExecutionStarted';
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
			case 'SignalExternalWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionInitiated':
			case 'ScheduleActivityTaskFailed':
			case 'RequestCancelActivityTaskFailed':
			case 'StartTimerFailed':
			case 'CancelTimerFailed':
			case 'StartChildWorkflowExecutionInitiated':
				return 'DecisionTaskCompleted';
			case 'StartChildWorkflowExecutionFailed':
			case 'ChildWorkflowExecutionStarted':
				return 'StartChildWorkflowExecutionInitiated';
			case 'ChildWorkflowExecutionCompleted':
			case 'ChildWorkflowExecutionFailed':
			case 'ChildWorkflowExecutionTimedOut':
			case 'ChildWorkflowExecutionCanceled':
			case 'ChildWorkflowExecutionTerminated':
				return 'ChildWorkflowExecutionStarted';
			case 'ExternalWorkflowExecutionSignaled':
			case 'SignalExternalWorkflowExecutionFailed':
				return 'SignalExternalWorkflowExecutionInitiated';
			case 'ExternalWorkflowExecutionCancelRequested':
			case 'RequestCancelExternalWorkflowExecutionFailed':
				return 'RequestCancelExternalWorkflowExecutionInitiated';
		}
	
		return false;
	}
}