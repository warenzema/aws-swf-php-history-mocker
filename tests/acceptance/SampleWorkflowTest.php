<?php
namespace SwfHistoryMockerTests\acceptance;

use SwfHistoryMocker\HistoryBuilder;
use SwfHistoryMocker\DesiredEvent;
class SampleWorkflowTest extends \PHPUnit_Framework_TestCase
{
	public function testTimedOutWorkflowExecutionMatchesRealSample()
	{
		$HistoryBuilder = new HistoryBuilder();
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('WorkflowExecutionStarted');
		$DesiredEvent->setUnixTimestamp('1427393547.1470001');
		$DesiredEvent->setEventAttributes([
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
		]);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('DecisionTaskScheduled');
		$DesiredEvent->setUnixTimestamp('1427393547.1470001');
		$DesiredEvent->setEventAttributes([
			'startToCloseTimeout'=>'30',
			'taskList'=>
			array (
				'name'=>'test',
			),
		]);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$DesiredEvent = new DesiredEvent();
		$DesiredEvent->setEventType('WorkflowExecutionTimedOut');
		$DesiredEvent->setSecondsSinceLastEvent(30.006);
		$DesiredEvent->setEventAttributes([
			'childPolicy'=>'TERMINATE',
			'timeoutType'=>'START_TO_CLOSE',
		]);
		$HistoryBuilder->addDesiredEvent($DesiredEvent);
		
		$realHistoryPath = __DIR__.'/realSampleEventHistory.php';
		$this->assertTrue(file_exists($realHistoryPath));
		$realHistory = require $realHistoryPath;
		$this->assertArrayHasKey('events',$realHistory);
		$realEvents = $realHistory['events'];
		
		$this->assertEquals(
			$realEvents,
			$HistoryBuilder->getEventHistory()
		);
	}
	public function testSimpleWorkflowWithActivity()
	{
		$this->markTestIncomplete();
	}
}