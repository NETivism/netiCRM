<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Contact/Form/Search/Custom/Base.php';

class CRM_Contact_Form_Search_Custom_Proximity
   extends    CRM_Contact_Form_Search_Custom_Base
   implements CRM_Contact_Form_Search_Interface {

    /**
     * Trigonometry for calculating geographical distances.
     * All function arguments and return values measure distances in metres
     * and angles in degrees.  The ellipsoid model is from the WGS-84 datum.
     * Ka-Ping Yee, 2003-08-11

     * earth_radius_semimajor = 6378137.0;
     * earth_flattening = 1/298.257223563;
     * earth_radius_semiminor = $earth_radius_semimajor * (1 - $earth_flattening);
     * earth_eccentricity_sq = 2*$earth_flattening - pow($earth_flattening, 2);

     * This library is an implementation of UCB CS graduate student, Ka-Ping Yee (http://www.zesty.ca).
     * This version has been taken from Drupal's location module: http://drupal.org/project/location
     **/

    protected $_earthFlattening;
    protected $_earthRadiusSemiMinor;
    protected $_earthRadiusSemiMajor;
    protected $_earthEccentricitySQ;

    protected $_latitude  = 37.76;
    protected $_longitude = -122.44;
    protected $_distance  = 300000;
    protected $_earthDistanceSQL = null;

    function __construct( &$formValues ) {
        parent::__construct( $formValues );

        $this->_earthFlattening       = 1.0 / 298.257223563;
        $this->_earthRadiusSemiMajor = 6378137.0;
        $this->_earthRadiusSemiMinor = $this->_earthRadiusSemiMajor * ( 1.0 - $this->_earthFlattening );
        $this->_earthEccentricitySQ  = 2 * $this->_earthFlattening - pow ( $this->_earthFlattening, 2 );

        // unset search profile if set
        unset( $this->_formValues['uf_group_id'] );

        if ( ! empty( $this->_formValues ) ) {
            // add the country and state
            if ( CRM_Utils_Array::value( 'country_id', $this->_formValues ) ) {
                $this->_formValues['country'] = CRM_Core_PseudoConstant::country( $this->_formValues['country_id'] );
            } 
            
            if ( CRM_Utils_Array::value( 'state_province_id', $this->_formValues ) ) {
                $this->_formValues['state_province'] = CRM_Core_PseudoConstant::stateProvince( $this->_formValues['state_province_id'] );
            }
            
            // use the address to get the latitude and longitude
            require_once 'CRM/Utils/Geocode/Google.php';
            CRM_Utils_Geocode_Google::format( $this->_formValues );

            if ( ! isset( $this->_formValues['geo_code_1'] ) ||
                 ! isset( $this->_formValues['geo_code_2'] ) ||
                 ! isset( $this->_formValues['distance'] ) ) {
                CRM_Core_Error::fatal( ts( 'Could not geocode input' ) );
            }

            $this->_latitude  = $this->_formValues['geo_code_1'];
            $this->_longitude = $this->_formValues['geo_code_2'];
            $this->_distance  = $this->_formValues['distance'] * 1000;
        }

        $this->_earthDistanceSQL = $this->earthDistanceSQL( $this->_latitude, $this->_longitude );

        $this->_tag = CRM_Utils_Array::value( 'tag', $this->_formValues );

        $this->_columns = array( ts('Name')           => 'sort_name'      ,
                                 ts('Street Address') => 'street_address' ,
                                 ts('City'          ) => 'city'           ,
                                 ts('Postal Code'   ) => 'postal_code'    ,
                                 ts('State'         ) => 'state_province' ,
                                 ts('Country'       ) => 'country'        );
    }

    /**
     * Latitudes in all of U. S.: from -7.2 (American Samoa) to 70.5 (Alaska).
     * Latitudes in continental U. S.: from 24.6 (Florida) to 49.0 (Washington).
     * Average latitude of all U. S. zipcodes: 37.9.
     */

    /*
    /**
     * Estimate the Earth's radius at a given latitude. 
     * Default to an approximate average radius for the United States.
     */
    function earthRadius( $latitude = 37.9 ) {
        $lat = deg2rad( $latitude );

        $x   = cos( $lat ) / $this->_earthRadiusSemiMajor;
        $y   = sin( $lat ) / $this->_earthRadiusSemiMinor;
        return 1.0 / sqrt( $x * $x + $y * $y );
    }

    /**
     * Convert longitude and latitude to earth-centered earth-fixed coordinates.
     * X axis is 0 long, 0 lat; Y axis is 90 deg E; Z axis is north pole.
     */
    function earthXYZ( $longitude, $latitude, $height = 0 ) {
        $long = deg2rad( $longitude );
        $lat  = deg2rad( $latitude  );

        $cosLong = cos( $long );
        $cosLat  = cos( $lat  );
        $sinLong = sin( $long );
        $sinLat  = sin( $lat  );
        
        $radius = $this->_earthRadiusSemiMajor / 
            sqrt( 1 - $this->_earthEccentricitySQ * $sinLat * $sinLat);

        $x = ( $radius + $height ) * $cosLat * $cosLong;
        $y = ( $radius + $height ) * $cosLat * $sinLong;
        $z = ( $radius * ( 1 - $this->_earthEccentricitySQ ) + $height ) * $sinLat;

        return array( $x, $y, $z );
    }

    /**
     * Convert a given angle to earth-surface distance.
     */
    function earthArcLength( $angle, $latitude = 37.9 ) {
        return deg2rad( $angle ) * $this->earthRadius( $latitude );
    }

    /**
     * Estimate the earth-surface distance between two locations.
     */
    function earthDistance( $longitudeSrc, $latitudeSrc,
                            $longitudeDst, $latitudeDst ) {

        $longSrc = deg2rad( $longitudeSrc );
        $latSrc  = deg2rad( $latitudeSrc  );
        $longDst = deg2rad( $longitudeDst );
        $latDst  = deg2rad( $latitudeDst  );

        $radius = $this->_earthRadius( ( $latitudeSrc + $latitudeDst ) / 2 );
        
        $cosAngle = cos( $latSrc ) * cos( $latDst ) *
            ( cos( $longSrc ) * cos( $longDst ) + sin( $longSrc ) * sin( $longDst ) ) +
            sin( $latSrc ) * sin( $latDst );
        return acos( $cosAngle ) * $radius;
    }

    /**
     * Estimate the min and max longitudes within $distance of a given location.
     */
    function earthLongitudeRange( $longitude, $latitude, $distance ) {
        $long   = deg2rad( $longitude );
        $lat    = deg2rad( $latitude  );
        $radius = $this->earthRadius( $latitude );

        $angle   = $distance / $radius;
        $diff    = asin( sin( $angle ) / cos( $lat ) );
        $minLong = $long - $diff;
        $maxLong = $long + $diff;

        if ( $minLong < -pi( ) ) {
            $minLong = $minLong + pi( ) * 2;
        }
        
        if ( $maxLong > pi( ) ) {
            $maxLong = $maxLong - pi( ) * 2;
        }

        return array( rad2deg( $minLong ),
                      rad2deg( $maxLong ) );
    }

    /**
     * Estimate the min and max latitudes within $distance of a given location.
     */
    function earthLatitudeRange( $longitude, $latitude, $distance ) {
        $long   = deg2rad( $longitude );
        $lat    = deg2rad( $latitude  );
        $radius = $this->earthRadius( $latitude );

        $angle  = $distance / $radius;
        $minLat = $lat - $angle;
        $maxLat = $lat + $angle;
        $rightangle = pi( ) / 2.0;

        if ( $minLat < -$rightangle ) { // wrapped around the south pole
            $overshoot = -$minLat - $rightangle;
            $minLat    = -$rightangle + $overshoot;
            if ($minLat > $maxLat) {
                $maxLat = $minLat;
            }
            $minLat = -$rightangle;
        }

        if ($maxLat > $rightangle) { // wrapped around the north pole
            $overshoot = $maxLat - $rightangle;
            $maxLat    = $rightangle - $overshoot;
            if ($maxLat < $minLat) {
                $minLat = $maxLat;
            }
            $maxLat = $rightangle;
        }

        return array( rad2deg( $minLat ),
                      rad2deg( $maxLat ) );
    }

    /*
     * Returns the SQL fragment needed to add a column called 'distance'
     * to a query that includes the location table
     *
     * @param $longitude
     * @param $latitude 
     */
    function earthDistanceSQL( $longitude, $latitude ) {
        $long   = deg2rad( $longitude );
        $lat    = deg2rad( $latitude  );
        $radius = $this->earthRadius( $latitude );

        $cosLong = cos( $long );
        $cosLat  = cos( $lat  );
        $sinLong = sin( $long );
        $sinLat  = sin( $lat  );
        
        return "
IFNULL( ACOS( $cosLat * COS( RADIANS( $latitude ) ) *
              ( $cosLong * COS( RADIANS( $longitude ) ) +
                $sinLong * SIN( RADIANS( $longitude ) ) ) +
              $sinLat  * SIN( RADIANS( $latitude  ) ) ), 0.00000 ) * $radius
";
    }

    function buildForm( &$form ) {

        $config         =& CRM_Core_Config::singleton( );
        $countryDefault = $config->defaultContactCountry; 
        $tag =
            array('' => ts('- any tag -')) +
            CRM_Core_PseudoConstant::tag( );
        $form->addElement('select', 'tag', ts('Tag'), $tag);

        $form->add( 'text',
                    'distance',
                    ts( 'Distance' ) );

        $form->add( 'text',
                    'street_address',
                    ts( 'Street Address' ) );

        $form->add( 'text',
                    'city',
                    ts( 'City' ) );

        $form->add( 'text',
                    'postal_code',
                    ts( 'Postal Code' ) );

        $stateCountryMap   = array( );
        $stateCountryMap[] = array( 'state_province' => 'state_province_id',
                                    'country'        => 'country_id' );
        $defaults = array( ); 
        if ( $countryDefault ) {
            $stateProvince = array( '' => ts('- select -') ) + CRM_Core_PseudoConstant::stateProvinceForCountry( $countryDefault );
            $defaults['country_id'] = $countryDefault;
        } else {
            $stateProvince = array( '' => ts('- select -') ) + CRM_Core_PseudoConstant::stateProvince( );
        }
        $form->addElement('select', 'state_province_id', ts('State/Province'), $stateProvince);        
        
        $country = array( '' => ts('- select -') ) + CRM_Core_PseudoConstant::country( );
        $form->add( 'select', 'country_id', ts('Country'), $country, true );
        
        $form->add( 'text', 'distance', ts( 'Radius for Proximity Search (in km)' ), null, true );
        // state country js, CRM-5233
        require_once 'CRM/Core/BAO/Address.php';
        CRM_Core_BAO_Address::addStateCountryMap( $stateCountryMap ); 
        CRM_Core_BAO_Address::fixAllStateSelects( $form, $defaults );
        

        /**
         * You can define a custom title for the search form
         */
         $this->setTitle('Proximity Search');
         
         /**
         * if you are using the standard template, this array tells the template what elements
         * are part of the search criteria
         */
        $form->assign( 'elements', array( 'tag',
                                          'street_address',
                                          'city',
                                          'postal_code',
                                          'country_id',
                                          'state_province_id',
                                          'distance' ) );
    }

    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false ) {

        $selectClause = "
contact_a.id           as contact_id    ,
contact_a.sort_name    as sort_name     ,
address.street_address as street_address,
address.city           as city          ,
address.postal_code    as postal_code   ,
state_province.name    as state_province,
country.name           as country       ,
{$this->_earthDistanceSQL} as distance
";

        return $this->sql( $selectClause,
                           $offset, $rowcount, $sort,
                           $includeContactIDs, null );

    }
    
    function from( ) {
        $f = "
FROM      civicrm_contact contact_a
LEFT JOIN civicrm_address address ON ( address.contact_id       = contact_a.id AND
                                       address.is_primary       = 1 )
LEFT JOIN civicrm_state_province state_province ON state_province.id = address.state_province_id
LEFT JOIN civicrm_country country               ON country.id        = address.country_id
";

		// This prevents duplicate rows when contacts have more than one tag any you select "any tag"
		if ($this->_tag) {
			$f .= "
LEFT JOIN civicrm_entity_tag t ON contact_a.id = t.contact_id
";
		}
		
		return $f;
    }

    function where( $includeContactIDs = false ) {
        $params = array( );
        $clause = array( );

        list( $minLongitude, $maxLongitude ) = $this->earthLongitudeRange( $this->_longitude,
                                                                           $this->_latitude ,
                                                                           $this->_distance );
        list( $minLatitude , $maxLatitude  ) = $this->earthLatitudeRange ( $this->_longitude,
                                                                           $this->_latitude ,
                                                                           $this->_distance );

        $where = "
address.geo_code_1  >= $minLatitude  AND
address.geo_code_1  <= $maxLatitude  AND
address.geo_code_2 >= $minLongitude AND
address.geo_code_2 <= $maxLongitude AND
{$this->_earthDistanceSQL} <= $this->_distance
";

		if ($this->_tag) {
			$where .= "
AND t.tag_id = {$this->_tag}
";
		}
		
        return $this->whereClause( $where, $params );
    }

    function templateFile( ) {
        return 'CRM/Contact/Form/Search/Custom/Sample.tpl';
    }

    function setDefaultValues( ) {
    	$config =& CRM_Core_Config::singleton( );
    	$countryDefault = $config->defaultContactCountry;
    	
    	if ($countryDefault) {
    		return array( 'country_id' => $countryDefault );
    	}
    	return null;     
    }

    function alterRow( &$row ) {
    }
    
    function setTitle( $title ) {
        if ( $title ) {
            CRM_Utils_System::setTitle( $title );
        } else {
            CRM_Utils_System::setTitle(ts('Search'));
        }
    }    
}


