<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries;


/**
 * XML class.
 * 
 * @since 0.1
 */
class Xml
{


    /**
     * Convert array to XML.
     * 
     * Usage:
     * <pre>
     * // initialize the class.
     * $SimpleXml = new \SimpleXMLElement('&lt;?xml version="1.0"?&gt;&lt;data&gt;&lt;/data&gt;');
     * $Xml = new \Rdb\System\Libraries\Xml();
     * $Xml->fromArray(array('key1' => 'val1', 'key2_array' => array('k2.1' => 'v2.1', 'k2.2' => 'v2.2')), $SimpleXml);
     * // then generate XML content.
     * $content = $SimpleXml->asXML();
     * // or write out as XML file.
     * $content = $SimpleXml->asXml('xmlfile.xml');
     * </pre>
     * 
     * @link http://stackoverflow.com/a/5965940/128761 Reference
     * @param array $array
     * @param \SimpleXMLElement $SimpleXml
     */
    public function fromArray(array $array, \SimpleXMLElement &$SimpleXml)
    {
        foreach($array as $key => $value) {
            if(is_numeric($key)){
                $key = 'item'.$key; //dealing with <0/>..<n/> issues
            }

            if(is_array($value)) {
                $subnode = $SimpleXml->addChild($key);
                $this->fromArray($value, $subnode);
            } else {
                $SimpleXml->addChild("$key",htmlspecialchars("$value"));
            }
         }
    }// fromArray


}
