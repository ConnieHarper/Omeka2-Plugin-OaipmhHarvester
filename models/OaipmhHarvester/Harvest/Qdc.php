<?php
/**
 * @package OaipmhHarvester
 * @subpackage Models
 * @copyright Copyright (c) 2009 Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Metadata format map for the required qdc Dublin Core format
 *
 * @package OaipmhHarvester
 * @subpackage Models
 */
class OaipmhHarvester_Harvest_Qdc extends OaipmhHarvester_Harvest_Abstract
{
    /*  XML schema and OAI prefix for the format represented by this class.
        These constants are required for all maps. */
    const VETDB = 'UCF Community Veterans History Project';
    const METADATA_SCHEMA = 'http://epubs.cclrc.ac.uk/xsd/qdc.xsd';
    const METADATA_PREFIX = 'qdc';
    const QDC_NAMESPACE = 'http://epubs.cclrc.ac.uk/xmlns/qdc/';
    const DUBLIN_CORE_NAMESPACE = 'http://purl.org/dc/elements/1.1/';
    const TERM_NAMESPACE = 'http://purl.org/dc/terms/';

    /**
     * Collection to insert items into.
     * @var Collection
     */
    protected $collection;
    
    /**
     * Actions to be carried out before the harvest of any items begins.
     */
    protected function _beforeHarvest()
    {
error_log("models/OaipmhHarvester/Harvest/Qdc.php beforeHarvest".print_r($harvest,true),0);
        $harvest = $this->_getHarvest();
	$collectionMetadata = array(
            'metadata' => array(
                'public' => $this->getOption('public'),
                'featured' => $this->getOption('featured'),
            ),);
        $collectionMetadata['elementTexts']['Dublin Core']['Title'][]
            = array('text' => (string) $harvest->set_name, 'html' => false);
        $collectionMetadata['elementTexts']['Dublin Core']['Description'][]
            = array('text' => (string) $harvest->set_Description, 'html' => false);

        $this->_collection = $this->_insertCollection($collectionMetadata);

    }
    
    /**
     * Harvest one record.
     *
     * @param SimpleXMLIterator $record XML metadata record
     * @return array Array of item-level, element texts and file metadata.
     */
    protected function _harvestRecord($record)
    {
        $itemMetadata = array('collection_id' => $this->_collection->id, 
                              'public'        => $this->getOption('public'), 
                              'featured'      => $this->getOption('featured'));

//        $dcMetadata = $record
 //                   ->metadata
  //                  ->children(self::QDC_NAMESPACE)
   //                 ->children(self::DUBLIN_CORE_NAMESPACE);
        $dcMetadataPREFIXQDC = $record->metadata->children("qdc",true);
        $dcMetadataCHILDRENofPREFIXQDC= $dcMetadataPREFIXQDC->children("dc", true);
        $dcMetadataCHILDRENofPREFIXTERMS= $dcMetadataPREFIXQDC->children("dcterms", true);


	$descriptions = array(
		0=>'Description: ',
		1=>'Gender: ',
		2=>'Race: ',
		3=>'War: ',
		4=>'Status: ',
		5=>'Entrance into Service: ',
		6=>'Branch of Service: ',
		7=>'Unit of Service: ',
		8=>'Highest Rank: ',
		9=>'POW: ',
		10=>'Service Related Injury: ',
		11=>'Battles: ',
		12=>'Medals: ',
		13=>'Achievements: ',
		14=>'Note: ',
		15=>'Location of Interview: '
	);
	$elementTexts = array();
        $elementQDCs = array('spatial', 
			'temporal', 'medium', 'extent' );
        $elementDCs = array('contributor', 'coverage', 'creator', 
                          'date', 'description', 'format', 
                          'identifier', 'language', 'publisher', 
                          'relation', 'rights', 'source', 
                          'subject', 'title', 'type');
        foreach ($elementDCs as $element) {
            if (isset($dcMetadataCHILDRENofPREFIXQDC->$element)) {
		$i=0;
                foreach ($dcMetadataCHILDRENofPREFIXQDC->$element as $rawText) {
			$text=trim($rawText);
			$text = $this->convertHttp($text);
			$harvest = $this->_getHarvest();
			$texts = explode(";",$text);
			foreach($texts as $subText){
			   if (strlen($subText)>0){
			   // special case UCF VET History project gets description headings.
			   if ((substr($harvest->set_spec,0,3) == "VET" && substr($harvest->set_name,0,3) == "UCF") && ($i<count($descriptions) && $element == "description")) {	
				$subText = $descriptions[$i].' '.$subText;
/* no tags
				     if ($i == 0 || $i == 2 || $i == 5 || $i == 7 || $i == 10 || $i == 11)
					$itemMetadata['tags'] .= $subText.";";
** no tags **/
	                    	    $elementTexts['Dublin Core'][ucwords($element)][] = array('text' => (string) $subText, 'html' => true);
				}
				else { //not a descriptions from UCF VHP harvest
			
                       		    $elementTexts['Dublin Core'][ucwords($element)][] = array('text' => (string) $subText, 'html' => true);
				    // special case title gets and alt title
				    if ($element=="title"){
					$alt_title= substr($subText,0,20);
                    			$elementTexts['Dublin Core']['Alternative Title'][] = array('text' => (string) $alt_title, 'html' => true);
				    }
				}
			   } //not nothing
                	}  //breaking out by ;
			$i++;
        }}}
        foreach ($elementQDCs as $element) {
            if (isset($dcMetadataCHILDRENofPREFIXTERMS->$element)) {
                foreach ($dcMetadataCHILDRENofPREFIXTERMS->$element as $rawText) {
		    if ($element == "spatial") $element = "Spatial Coverage";
		    if ($element == "temporal") $element = "Temporal Coverage";
			$text=trim($rawText);
		   $texts = explode(";",$text);
		   foreach($texts as $subText){
			if (strlen($subText)>0)
	                    	$elementTexts['Dublin Core'][ucwords($element)][] = array('text' => (string) $subText, 'html' => true);
		   }//splitting with semicolons
                }
            }
        }
        return array('itemMetadata' => $itemMetadata,
                     'elementTexts' => $elementTexts,
                     'fileMetadata' => array());
    }
function convertHttp($text){
	if (substr($text,0,4)=="http"){
		$pos = 0;
		$posStop = strpos($text, " ",$pos);
		if ($posStop === false) $posStop = strlen($text);
		$lenStop = $posStop-$pos;
		$httpaddr = substr($text, $pos, $lenStop);
		$text = " <a href='".$httpaddr."'>$httpaddr</a> ".substr($text,$posStop, strlen($text));
	} else {
		$pos = strpos($text, "http");
		if (!$pos === false){
			$posStop = strpos($text, " ",$pos);
			if ($posStop === false) $posStop = strlen($text);
			$lenStop = $posStop-$pos;
			$httpaddr = substr($text, $pos, $lenStop);
			$text = substr($text, 0,$pos-1)." <a href='".$httpaddr."'>$httpaddr</a> ".substr($text,$posStop, strlen($text));
		}
	}
	return $text;
} 
    /**
     * Return the metadata schema URI.
     *
     * @return string Schema URI
     */
    public function getMetadataSchema()
    {
        return self::METADATA_SCHEMA;
    }
    
    /**
	 * Return the metadata prefix.
	 *
	 * @return string Metadata prefix
	 */
    public function getMetadataPrefix()
    {
        return self::METADATA_PREFIX;
    }
}
