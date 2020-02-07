<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System\Libraries;


class FormTest extends \Rdb\Tests\BaseTestCase
{


    public function testIsMatched()
    {
        $Form = new \Rdb\System\Libraries\Form();

        // null value
        $this->assertTrue($Form->isMatched(null, ''));
        $this->assertTrue($Form->isMatched(null, null));
        $this->assertTrue($Form->isMatched(null, 0));

        // non scalar or null
        $this->assertFalse($Form->isMatched(['aaa'], 'aaa'));
        $value = new \stdClass();
        $value->aaa = 'aaa';
        $this->assertFalse($Form->isMatched($value, 'aaa'));
        unset($value);

        $this->assertTrue($Form->isMatched('Aaa', 'Aaa'));
        $this->assertFalse($Form->isMatched('Aaa', 'aaa'));
        $this->assertTrue(\Rdb\System\Libraries\Form::staticIsMatched('Aaa', 'Aaa'));
        $this->assertFalse(\Rdb\System\Libraries\Form::staticIsMatched('Aaa', 'aaa'));
        $this->assertTrue(\Rdb\System\Libraries\Form::staticIsMatched('Aaa', 'aaa', true));

        $inputValue = ['Aaa', 'Aab', 'Baa', 'Bab'];
        $this->assertTrue(\Rdb\System\Libraries\Form::staticIsMatched('Baa', $inputValue));
    }// testIsMatched


    public function testSetChecked()
    {
        $Form = new \Rdb\System\Libraries\Form();

        $this->assertSame(' checked="checked"', $Form->setChecked(123, 123));
        $this->assertSame(' checked="checked"', \Rdb\System\Libraries\Form::staticSetChecked(123, 123));
        $this->assertContains('checked="checked"', \Rdb\System\Libraries\Form::staticSetChecked(123, '123'));
        $this->assertContains('checked="checked"', \Rdb\System\Libraries\Form::staticSetChecked('Select', 'select', true));
    }// testSetChecked


    public function testSetSelected()
    {
        $Form = new \Rdb\System\Libraries\Form();

        $this->assertSame(' selected="selected"', $Form->setSelected(123, 123));
        $this->assertSame(' selected="selected"', \Rdb\System\Libraries\Form::staticSetSelected(123, 123));
        $this->assertContains('selected="selected"', \Rdb\System\Libraries\Form::staticSetSelected(123, '123'));
        $this->assertContains('selected="selected"', \Rdb\System\Libraries\Form::staticSetSelected('Select', 'select', true));
    }// testSetSelected


}
