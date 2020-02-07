<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System\Libraries;


class XmlTest extends \Rdb\Tests\BaseTestCase
{


    public function testFromArray()
    {
        $Xml = new \Rdb\System\Libraries\Xml();

        $data = [];
        $data['name'] = 'Vee';
        $data['lastname'] = 'Winch';
        $SimpleXml = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
        $Xml->fromArray($data, $SimpleXml);
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?><data><name>Vee</name><lastname>Winch</lastname></data>', $SimpleXml->asXML());
        unset($data);

        $data = [];
        $data['website'] = 'Google';
        $data['location'] = ['USA', 'Thailand'];
        $SimpleXml = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
        $Xml->fromArray($data, $SimpleXml);
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?><data><website>Google</website><location><item0>USA</item0><item1>Thailand</item1></location></data>', $SimpleXml->asXML());
        unset($data);

        $data = [];
        $data['website'] = 'Google';
        $data['location'] = ['America' => 'USA', 'Asia' => 'Thailand'];
        $SimpleXml = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
        $Xml->fromArray($data, $SimpleXml);
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?><data><website>Google</website><location><America>USA</America><Asia>Thailand</Asia></location></data>', $SimpleXml->asXML());
        unset($data);

        unset($Xml);
    }// testFromArray


}
