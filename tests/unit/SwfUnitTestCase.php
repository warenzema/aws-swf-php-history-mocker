<?php

namespace SwfHistoryMockerTests\Unit;

class SwfUnitTestCase extends \PHPUnit_Framework_TestCase
{
	public function assertGetReturnsSet($object,$commonMethodNamePart,$value)
	{
		$setMethod = 'set'.$commonMethodNamePart;
		$getMethod = 'get'.$commonMethodNamePart;
		$this->assertTrue(
			method_exists($object,$setMethod),
			get_class($object)."::".$setMethod."() does not exist"
		);
		$this->assertTrue(
			method_exists($object,$getMethod),
			get_class($object)."::".$getMethod."() does not exist"
		);
		$object->$setMethod($value);
		$getValue = $object->$getMethod($value);
		
		$this->assertEquals($value,$getValue);
	}
}