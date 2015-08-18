<?php
/**
 * Geräteliste MediaWiki extension.
 *
 * This extension implements a <geraeteliste> tag creating a gallery of all images in
 * a category.
 *
 * by habo
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Extensions
 */
if( !defined( 'MEDIAWIKI' ) ) {
        echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
        die( 1 );
}
$wgExtensionCredits['parserhook'][] = array(
        'path' => __FILE__,
        'name' => 'Geräteliste',
        'author' => 'habo',
        'url' => 'http://wiki.dingfabrik.de/index.php/Extention/Geräteliste',
        'description' => 'Adds <nowiki><geraeteliste></nowiki> tag',
        'version' => '1.4'
);
$wgExtensionFunctions[] = "CategoryGallery::categoryGallerySetHook";
class CategoryGallery {
        public static function categoryGallerySetHook() {
                global $wgParser;
                $wgParser->setHook( "geraeteliste", "CategoryGallery::renderCategoryGallery" );
                $wgParser->setHook( "projektliste", "CategoryGallery::renderCategoryGallery" );
                $wgParser->setHook( "geraetesuche", "CategoryGallery::renderGeraeteSuche" );
        }
        public static function renderGeraeteSuche( $input, $params, $parser ) {
                global $wgBedellPenDragonResident;

                $output= "<form action='/index.php'>";
		$output.="<input name='curid' type='text'>";
		$output.="<input type='submit' value='suchen'>";
		$output.="</form>";
		
		return $output;
	}
        public static function renderCategoryGallery( $input, $params, $parser ) {
                global $wgBedellPenDragonResident;
                $parser->disableCache();
                $dbr = wfGetDB( DB_SLAVE );
		$format="html";
                if ( isset( $params['format'] ) ) { // set output format
			$format = trim($params['format']);
		}
		$showstat=false;
                if ( isset( $params['stat'] ) ) { // show statistics
			$showstat=true;
		}
                if ( !isset( $params['cat'] ) ) { // No category selected
			$cat="Gerät";
	        } else {
			$cat=$params['cat'];
	        }
                if ( !isset( $params['noimg'] ) ) { // noimage attribute missing
			$noimg="Device-level-yellow.png";
	        } else {
			$noimg=$params['noimg'];
	        }
		$hasfilter=false; // if filter set, only allow matching cards
		if (isset($params['filterparameter']) && isset($params['filtervalue'])) {
			$filtername=trim($params['filterparameter']);
			$filtervalue=trim($params['filtervalue']);
			$hasfilter=true;
		}

                $res = $dbr->select( 'categorylinks', 'cl_from',
                        array (
                               'cl_to' => $cat,
			       'cl_type' => "page",
                        )
                );
                $ids = array();
                foreach ( $res as $row ) {
                        $ids[] = $row->cl_from;
                }
                $text = '';
		$count_total=0;
		$count_bild=0;
		$csvtext="id\tURL\tpagename\ttitle\timage\twarnlevel\tcontact\n";
		$htmllist="";

                foreach ( $ids as $id ) {
                        $page = WikiPage::newFromId( $id );
                        $title = Title::newFromID ( $id );
                        $tkey = $title->getPrefixedDBKey();
                        $tclean = str_replace("_"," ",$title->getPrefixedDBKey());
			$content = $page->getText();
			$isgpage=strstr($content,"{{Gerätekarte") || strstr($content,"{{Projektkarte");
			if (!$isgpage || strpos("x".$tkey,"Vorlage:",1)==1){
				continue;
			}
			if ($hasfilter){
				preg_match('/'.$filtername.'.*=(.*)/i', $content, $parametercontent);
				if (strcmp($filtervalue,trim($parametercontent[1]))!=0){
					continue;
				}

			}
			preg_match('/Bild.*=(.*)/i', $content, $str);
			$bild = $noimg;
			if ( !empty(trim($str[1]))) {
                        	$bild = $str[1];
				$count_bild++;
			}
			if ($format=="htmllist"){
				$htmllist.="* [[".$tkey."]]\n";
			}
			if ($format=="csv"){
				$name="";
				$warnstufe="";
				$ansprechpartner="";
				$arbeitssicherheit="";
				preg_match('/name.*=(.*)/i', $content, $name);
				preg_match('/warnstufe.*=(.*)/i', $content, $warnstufe);
				preg_match('/ansprechpartner.*=(.*)/i', $content, $ansprechpartner);
				preg_match('/arbeitssicherheit.*=(.*)/i', $content, $arbeitssicherheit);
				$csvtext.=$id."\t";
				$csvtext.="http://wiki.dingfabrik.de/?curid=".$id."\t";
				$csvtext.=trim($name[1])."\t";
				$csvtext.=trim($tkey)."\t";
				$csvtext.=trim($bild)."\t";
				$csvtext.=trim($warnstufe[1])."\t";
				$csvtext.=trim($ansprechpartner[1])."\t";
				$csvtext.=trim($arbeitssicherheit[1])."\t";
				$csvtext.="\n";
			}
			$count_total++;
                        $text .= $bild . "|[[".$tkey."|".$tclean."]]|link=".$tkey;
                        $text .= "\n";
		} 
		if ($format=="htmllist"){
			$output = $parser->recursiveTagParse($htmllist);
		} elseif ($format=="csv"){
			$output = "<pre>\n".$csvtext."\n</pre>";
		} else {
                	$output = $parser->renderImageGallery( $text, $params );
			if($showstat){
				$output.= "<div>Geräte: ".$count_total;
				$output.= ", mit Bild: ".$count_bild."</div>";
			}
		}
                return $output;
        }
}

