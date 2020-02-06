<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Tests\Rdb\System\Libraries;


class FormTest extends \Tests\Rdb\BaseTestCase
{


    public function testIsMatched()
    {
        $Form = new \System\Libraries\Form();

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
        $this->assertTrue(\System\Libraries\Form::staticIsMatched('Aaa', 'Aaa'));
        $this->assertFalse(\System\Libraries\Form::staticIsMatched('Aaa', 'aaa'));
        $this->assertTrue(\System\Libraries\Form::staticIsMatched('Aaa', 'aaa', true));

        $inputValue = ['Aaa', 'Aab', 'Baa', 'Bab'];
        $this->assertTrue(\System\Libraries\Form::staticIsMatched('Baa', $inputValue));
    }// testIsMatched


    public function testSetChecked()
    {
        $Form = new \System\Libraries\Form();

        $this->assertSame(' checked="checked"', $Form->setChecked(123, 123));
        $this->assertSame(' checked="checked"', \System\Libraries\Form::staticSetChecked(123, 123));
        $this->assertContains('checked="checked"', \System\Libraries\Form::staticSetChecked(123, '123'));
        $this->assertContains('checked="checked"', \System\Libraries\Form::staticSetChecked('Select', 'select', true));
    }// testSetChecked


    public function testSetSelected()
    {
        $Form = new \System\Libraries\Form();

        $this->assertSame(' selected="selected"', $Form->setSelected(123, 123));
        $this->assertSame(' selected="selected"', \System\Libraries\Form::staticSetSelected(123, 123));
        $this->assertContains('selected="selected"', \System\Libraries\Form::staticSetSelected(123, '123'));
        $this->assertContains('selected="selected"', \System\Libraries\Form::staticSetSelected('Select', 'select', true));
    }// testSetSelected


}
