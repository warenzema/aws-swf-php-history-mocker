<?php

namespace SwfHistoryMocker\traits;

trait ValidSwfEventTypes
{
	public function isValidEventType($eventType)
	{
		switch ($eventType) {
			case 'WorkflowExecutionStarted':
			case 'WorkflowExecutionCancelRequested':
			case 'WorkflowExecutionCompleted':
			case 'CompleteWorkflowExecutionFailed':
			case 'WorkflowExecutionFailed':
			case 'FailWorkflowExecutionFailed':
			case 'WorkflowExecutionTimedOut':
			case 'WorkflowExecutionCanceled':
			case 'CancelWorkflowExecutionFailed':
			case 'WorkflowExecutionContinuedAsNew':
			case 'ContinueAsNewWorkflowExecutionFailed':
			case 'WorkflowExecutionTerminated':
			case 'DecisionTaskScheduled':
			case 'DecisionTaskStarted':
			case 'DecisionTaskCompleted':
			case 'DecisionTaskTimedOut':
			case 'ActivityTaskScheduled':
			case 'ScheduleActivityTaskFailed':
			case 'ActivityTaskStarted':
			case 'ActivityTaskCompleted':
			case 'ActivityTaskFailed':
			case 'ActivityTaskTimedOut':
			case 'ActivityTaskCanceled':
			case 'ActivityTaskCancelRequested':
			case 'RequestCancelActivityTaskFailed':
			case 'WorkflowExecutionSignaled':
			case 'MarkerRecorded':
			case 'RecordMarkerFailed':
			case 'TimerStarted':
			case 'StartTimerFailed':
			case 'TimerFired':
			case 'TimerCanceled':
			case 'CancelTimerFailed':
			case 'StartChildWorkflowExecutionInitiated':
			case 'StartChildWorkflowExecutionFailed':
			case 'ChildWorkflowExecutionStarted':
			case 'ChildWorkflowExecutionCompleted':
			case 'ChildWorkflowExecutionFailed':
			case 'ChildWorkflowExecutionTimedOut':
			case 'ChildWorkflowExecutionCanceled':
			case 'ChildWorkflowExecutionTerminated':
			case 'SignalExternalWorkflowExecutionInitiated':
			case 'SignalExternalWorkflowExecutionFailed':
			case 'ExternalWorkflowExecutionSignaled':
			case 'RequestCancelExternalWorkflowExecutionInitiated':
			case 'RequestCancelExternalWorkflowExecutionFailed':
			case 'ExternalWorkflowExecutionCancelRequested':
				return true;
			default:
				return false;
		}
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