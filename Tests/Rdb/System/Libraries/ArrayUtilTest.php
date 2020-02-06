<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\Rdb\System\Libraries;


class ArrayUtilTest extends \Tests\Rdb\BaseTestCase
{


    public function testInArrayI()
    {
        $ArrayUtil = new \System\Libraries\ArrayUtil();

        $this->assertTrue($ArrayUtil->inArrayI('two', ['one', 'two', 'three']));
        $this->assertTrue($ArrayUtil->inArrayI('two', ['one', 'Two', 'three']));
        $this->assertTrue($ArrayUtil->inArrayI('two', ['one', 'TWO', 'three']));
        $this->assertFalse($ArrayUtil->inArrayI('two', ['one', 'three', 'five']));

        // test for static
        $this->assertTrue(\System\Libraries\ArrayUtil::staticInArrayI('two', ['one', 'two', 'three']));
        $this->assertTrue(\System\Libraries\ArrayUtil::staticInArrayI('two', ['one', 'Two', 'three']));
        $this->assertTrue(\System\Libraries\ArrayUtil::staticInArrayI('two', ['one', 'TWO', 'three']));
        $this->assertFalse(\System\Libraries\ArrayUtil::staticInArrayI('two', ['one', 'five', 'three']));
    }// testInArrayI


    public function testIsAssoc()
    {
        $ArrayUtil = new \System\Libraries\ArrayUtil();

        $this->assertTrue($ArrayUtil->isAssoc(['one', 'two', 'three' => 3, 4 => 'four']));
        $this->assertTrue($ArrayUtil->isAssoc(['car' => 'Honda', 'bike' => 'Yamaha']));
        $this->assertFalse($ArrayUtil->isAssoc(['one', 'two', 'three']));
        $this->assertFalse($ArrayUtil->isAssoc([0 => 'one', 1 => 'two', 2 => 'three']));
        $this->assertFalse($ArrayUtil->isAssoc([1 => 'one', 0 => 'two', 2 => 'three']));
        $this->assertFalse($ArrayUtil->isAssoc([1 => 'one', 4 => 'two', 3 => 'three']));

        // test for static
        $this->assertTrue(\System\Libraries\ArrayUtil::staticIsAssoc(['one', 'two', 'three' => 3, 4 => 'four']));
        $this->assertTrue(\System\Libraries\ArrayUtil::staticIsAssoc(['car' => 'Honda', 'bike' => 'Yamaha']));
        $this->assertFalse(\System\Libraries\ArrayUtil::staticIsAssoc(['one', 'two', 'three']));
        $this->assertFalse(\System\Libraries\ArrayUtil::staticIsAssoc([0 => 'one', 1 => 'two', 2 => 'three']));
        $this->assertFalse(\System\Libraries\ArrayUtil::staticIsAssoc([1 => 'one', 0 => 'two', 2 => 'three']));
        $this->assertFalse(\System\Libraries\ArrayUtil::staticIsAssoc([1 => 'one', 4 => 'two', 3 => 'three']));
    }// testIsAssoc


    public function testRecursiveKsort()
    {
        $ArrayUtil = new \System\Libraries\ArrayUtil();

        $array1 = [
            1 => 'b',
            0 => 'a',
            2 => 'c',
            3 => [
                1 => 'c.1',
                0 => 'c.0',
                4 => 'c.4',
                3 => 'c.3',
                2 => 'c.2',
            ],
        ];
        $assert = [
            0 => 'a',
            1 => 'b',
            2 => 'c',
            3 => [
                0 => 'c.0',
                1 => 'c.1',
                2 => 'c.2',
                3 => 'c.3',
                4 => 'c.4',
            ],
        ];

        $ArrayUtil->recursiveKsort($array1, SORT_NATURAL);
        $this->assertArraySubset($assert, $array1);

        // test for static.
        unset($array1);
        $array1 = [
            1 => 'b',
            0 => 'a',
            2 => 'c',
            3 => [
                1 => 'c.1',
                0 => 'c.0',
                4 => 'c.4',
                3 => 'c.3',
                2 => 'c.2',
            ],
        ];
        \System\Libraries\ArrayUtil::staticRecursiveKsort($array1, SORT_NATURAL);
        $this->assertSame($array1, $assert);
    }// testRecursiveKsort


}
