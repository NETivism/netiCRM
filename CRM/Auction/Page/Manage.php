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

require_once 'CRM/Core/Page.php';

/**
 * Page for displaying list of auctions
 */
class CRM_Auction_Page_Manage extends CRM_Core_Page
{
    /**
     * The action links that we need to display for the browse screen
     *
     * @var array
     * @static
     */
    static $_actionLinks = null;

    static $_links = null;

    protected $_pager = null;

    protected $_sortByCharacter;

    /**
     * Get action Links
     *
     * @return array (reference) of action links
     */
    function &links()
    {
        if (!(self::$_actionLinks)) {
            // helper variable for nicer formatting
            $disableExtra = ts('Are you sure you want to disable this Auction?');
            $deleteExtra = ts('Are you sure you want to delete this Auction?');
            $copyExtra = ts('Are you sure you want to make a copy of this Auction?');

            self::$_actionLinks = array(
                                        CRM_Core_Action::UPDATE  => array(
                                                                          'name'  => ts('Edit'),
                                                                          'url'   => 'civicrm/auction/add',
                                                                          'qs'    => 'action=update&id=%%id%%&reset=1',
                                                                          'title' => ts('Edit Auction') 
                                                                          ),
                                        CRM_Core_Action::PREVIEW => array(
                                                                          'name'  => ts('Items'),
                                                                          'url'   => 'civicrm/auction/item/manage',
                                                                          'qs'    => 'aid=%%id%%&reset=1',
                                                                          'title' => ts('Manage Items') 
                                                                          ),
                                        CRM_Core_Action::DISABLE => array(
                                                                          'name'  => ts('Disable'),
                                                                          'url'   => CRM_Utils_System::currentPath( ),
                                                                          'qs'    => 'action=disable&id=%%id%%',
                                                                          'extra' => 'onclick = "return confirm(\'' . $disableExtra . '\');"',
                                                                          'title' => ts('Disable Auction') 
                                                                          ),
                                        CRM_Core_Action::ENABLE  => array(
                                                                          'name'  => ts('Enable'),
                                                                          'url'   => CRM_Utils_System::currentPath( ),
                                                                          'qs'    => 'action=enable&id=%%id%%',
                                                                          'title' => ts('Enable Auction') 
                                                                          ),
                                        CRM_Core_Action::DELETE  => array(
                                                                          'name'  => ts('Delete'),
                                                                          'url'   => 'civicrm/auction/item/add',
                                                                          'qs'    => 'action=delete&id=%%id%%&reset=1',
                                                                          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
                                                                          'title' => ts('Delete Auction') 
                                                                          ),
                                        CRM_Core_Action::COPY     => array(
                                                                           'name'  => ts('Copy Auction'),
                                                                           'url'   => CRM_Utils_System::currentPath( ),
                                                                           'qs'    => 'reset=1&action=copy&id=%%id%%',
                                                                           'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
                                                                           'title' => ts('Copy Auction') 
                                                                          )
                                        );
        }
        return self::$_actionLinks;
    }

    /**
     * Run the page.
     *
     * This method is called after the page is created. It checks for the  
     * type of action and executes that action.
     * Finally it calls the parent's run method.
     *
     * @return void
     * @access public
     *
     */
    function run()
    {
        // get the requested action
        $action = CRM_Utils_Request::retrieve('action', 'String',
                                              $this, false, 'browse'); // default to 'browse'
        
        // assign vars to templates
        $this->assign('action', $action);
        $id = CRM_Utils_Request::retrieve('id', 'Positive',
                                          $this, false, 0);
        
        // set breadcrumb to append to 2nd layer pages
        $breadCrumb = array ( array('title' => ts('Manage Items'),
                                    'url'   => CRM_Utils_System::url( CRM_Utils_System::currentPath( ), 
                                                                      'reset=1' )) );

        // what action to take ?
        if ($action & CRM_Core_Action::DISABLE ) {
            require_once 'CRM/Auction/BAO/Item.php';
            CRM_Auction_BAO_Item::setIsActive($id ,0);
        } else if ($action & CRM_Core_Action::ENABLE ) {
            require_once 'CRM/Auction/BAO/Item.php';
            CRM_Auction_BAO_Item::setIsActive($id ,1); 
        } else if ($action & CRM_Core_Action::DELETE ) {
            $session =& CRM_Core_Session::singleton();
            $session->pushUserContext( CRM_Utils_System::url( CRM_Utils_System::currentPath( ), 'reset=1&action=browse' ) );
            $controller =& new CRM_Core_Controller_Simple( 'CRM_Auction_Form_Item_Delete',
                                                           'Delete Item',
                                                           $action );
            $id = CRM_Utils_Request::retrieve('id', 'Positive',
                                              $this, false, 0);
            $controller->set( 'id', $id );
            $controller->process( );
            return $controller->run( );
        } else if ($action & CRM_Core_Action::COPY ) {
            $this->copy( );
        }

        // finally browse the auctions
        $this->browse();
        
        // parent run 
        parent::run();
    }

    /**
     * Browse all auctions
     *  
     * 
     * @return void
     * @access public
     * @static
     */
    function browse()
    {

        $this->_sortByCharacter = CRM_Utils_Request::retrieve( 'sortByCharacter',
                                                               'String',
                                                               $this );
        if ( $this->_sortByCharacter == 1 ||
             ! empty( $_POST ) ) {
            $this->_sortByCharacter = '';
            $this->set( 'sortByCharacter', '' );
        }

        $this->_force = null;
        $this->_searchResult = null;
      
        $this->search( );

        $config =& CRM_Core_Config::singleton( );
        
        $params = array( );
        $this->_force = CRM_Utils_Request::retrieve( 'force', 'Boolean',
                                                       $this, false ); 
        $this->_searchResult = CRM_Utils_Request::retrieve( 'searchResult', 'Boolean', $this );
      
        $whereClause = $this->whereClause( $params, false, $this->_force );
        $this->pagerAToZ( $whereClause, $params );

        $params      = array( );
        $whereClause = $this->whereClause( $params, true, $this->_force );
        $this->pager( $whereClause, $params );
        list( $offset, $rowCount ) = $this->_pager->getOffsetAndRowCount( );

        // get all custom groups sorted by weight
        $auctions = array();
             
        $query = "
  SELECT *
    FROM civicrm_auction
   WHERE $whereClause
ORDER BY start_date desc
   LIMIT $offset, $rowCount";
        
        $dao = CRM_Core_DAO::executeQuery( $query, $params, true, 'CRM_Auction_DAO_Item' );
     
        while ($dao->fetch()) {
            $auctions[$dao->id] = array();
            CRM_Core_DAO::storeValues( $dao, $auctions[$dao->id]);
            
            // form all action links
            $action = array_sum(array_keys($this->links()));
            
            if ($dao->is_active) {
                $action -= CRM_Core_Action::ENABLE;
            } else {
                $action -= CRM_Core_Action::DISABLE;
            }
            
            $auctions[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action, 
                                                                      array('id' => $dao->id));
        }
        $this->assign('rows', $auctions);
    }
    
    function search( ) {
        $form = new CRM_Core_Controller_Simple( 'CRM_Auction_Form_SearchAuction', 
                                                ts( 'Search Items' ), CRM_Core_Action::ADD );
        $form->setEmbedded( true );
        $form->setParent( $this );
        $form->process( );
        $form->run( );
    }
    
    function whereClause( &$params, $sortBy = true, $force ) {
        $values  =  array( );
        $clauses = array( );
        $title   = $this->get( 'title' );
        if ( $title ) {
            $clauses[] = "title LIKE %1";
            if ( strpos( $title, '%' ) !== false ) {
                $params[1] = array( trim($title), 'String', false );
            } else {
                $params[1] = array( trim($title), 'String', true );
            }
        }

        if ( $sortBy &&
             $this->_sortByCharacter ) {
            $clauses[] = 'title LIKE %6';
            $params[6] = array( $this->_sortByCharacter . '%', 'String' );
        }

        // dont do a the below assignment when doing a 
        // AtoZ pager clause
        if ( $sortBy ) {
            if ( count( $clauses ) > 1 || $auctionByDates  ) {
                $this->assign( 'isSearch', 1 );
            } else {
                $this->assign( 'isSearch', 0 );
            }
        }

        if ( empty( $clauses ) ) {
            return 1;
        }

        return implode( ' AND ', $clauses );
    }


     function pager( $whereClause, $whereParams ) {
        require_once 'CRM/Utils/Pager.php';

        $params['status']       = ts('Item %%StatusMessage%%');
        $params['csvString']    = null;
        $params['buttonTop']    = 'PagerTopButton';
        $params['buttonBottom'] = 'PagerBottomButton';
        $params['rowCount']     = $this->get( CRM_Utils_Pager::PAGE_ROWCOUNT );
        if ( ! $params['rowCount'] ) {
            $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
        }

        $query = "
SELECT count(id)
  FROM civicrm_auction
 WHERE $whereClause";

        $params['total'] = CRM_Core_DAO::singleValueQuery( $query, $whereParams );
            
        $this->_pager = new CRM_Utils_Pager( $params );
        $this->assign_by_ref( 'pager', $this->_pager );
    }

    function pagerAtoZ( $whereClause, $whereParams ) {
        require_once 'CRM/Utils/PagerAToZ.php';
        
        $query = "
   SELECT DISTINCT UPPER(LEFT(title, 1)) as sort_name
     FROM civicrm_auction
    WHERE $whereClause
 ORDER BY LEFT(title, 1)
";
        $dao = CRM_Core_DAO::executeQuery( $query, $whereParams );

        $aToZBar = CRM_Utils_PagerAToZ::getAToZBar( $dao, $this->_sortByCharacter, true );
        $this->assign( 'aToZ', $aToZBar );
    }
    
}

