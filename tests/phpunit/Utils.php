<?php  // vim: set si ai expandtab tabstop=4 shiftwidth=4 softtabstop=4:

/**
 *  File for the Utils class
 *
 *  (PHP 5)
 *  
 *   @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 *   @copyright Copyright CiviCRM LLC (C) 2009
 *   @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 *   @version   $Id: Utils.php 32492 2011-02-14 21:06:52Z shot $
 *   @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */


/**
 *  Utility functions
 *   @package   CiviCRM
 */
class Utils {

  /**
   *  PDO for the database
   *  @var PDO
   */
  public $pdo;

  /**
   *  Construct an object for this database
   */
  public function __construct( $host, $user, $pass ) {
    try {
      $this->pdo = new PDO("mysql:host={$host}", $user, $pass, array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true ));
    }
    catch ( PDOException $e ) {
      echo "Can't connect to MySQL server:" . PHP_EOL . $e->getMessage() . PHP_EOL;
      exit(1);
    }
  }

  /**
   *  Prepare and execute a query
   *
   *  If the query fails, output a diagnostic message
   *  @param  string  Query to run
   *  @return mixed   PDOStatement => Results of the query
   *          false    => Query failed
   */
  function do_query( $query ) {
    $string = preg_replace("/^#[^\n]*$/m", "\n", $query );
    $string = preg_replace("/^(--[^-]).*/m", "\n", $string );
    
    $queries  = preg_split('/;$/m', $string);
    foreach ( $queries as $query ) {
      $query = trim( $query );
      if ( ! empty( $query ) ) {
        $result = $this->pdo->query( $query );
        if ( $this->pdo->errorCode() == 0 ) {
          continue;
        } 
        else { 
          var_dump( $result );
          var_dump( $this->pdo->errorInfo() );
        }
      }
    }
    return true;
  }

}
