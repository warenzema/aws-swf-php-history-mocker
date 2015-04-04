<?php
namespace swf4php\tests\unit;

use Aws\Swf\SwfClient;
class ContactSwfTest extends \PHPUnit_Framework_TestCase
{
	public function testContactSwf()
	{
		$this->markTestSkipped();
		$swfClient = SwfClient::factory([
			'region'=>'us-east-1'
		]);
		
		$history = $swfClient->getWorkflowExecutionHistory([
			'domain'=>'Short Term Testing',
			'execution'=>[
				'workflowId'=>'test',
				'runId'=>'2226190mszyRqZ/eNyoiFFx0rtzbZcKQB/pYFkoYVdrHA=',
			]
		]);
		$historyArray = $history->toArray();
		var_export($historyArray);
		echo time();
	}
	
	public function testDoesDatetimeTakeDecimals()
	{
		$this->markTestSkipped('no it does not');
		echo "\n\n";
		print_r(microtime());
		$time = '1427393547.1470001';
		$DateTime = \DateTime::createFromFormat('U', $time);
		$errors = \DateTime::getLastErrors();
		echo "\n";
		print_r($errors);
		if (false === $DateTime) {
			return;
		}
		//$DateTime = new \DateTime($time);
		echo "\n";
		echo $DateTime->format('u')."\n";
		echo $DateTime->format('U')."\n";
	}
}