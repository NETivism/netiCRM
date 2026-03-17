<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * Manage simple Tree data structure
 * example of Tree is
 *
 *                             'a'
 *                              |
 *    --------------------------------------------------------------
 *    |                 |                 |              |         |
 *   'b'               'c'               'd'            'e'       'f'
 *    |                 |         /-----/ |                        |
 *  -------------     ---------  /     --------     ------------------------
 *  |           |     |       | /      |      |     |           |          |
 * 'g'         'h'   'i'     'j'      'k'    'l'   'm'         'n'        'o'
 *                            |
 *                  ----------------------
 *                 |          |          |
 *                'p'        'q'        'r'
 *
 *
 *
 * From the above diagram we have
 *   'a'  - root node
 *   'b'  - child node
 *   'g'  - leaf node
 *   'j'  - node with multiple parents 'c' and 'd'
 *
 *
 * All nodes of the tree (including root and leaf node) contain the following properties
 *       Name      - what is the node name ?
 *       Children  - who are it's children
 *       Data      - any other auxillary data
 *
 *
 * Internally all nodes are an array with the following keys
 *      'name' - string
 *      'children' - array
 *      'data' - array
 *
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id: $
 *
 */
class CRM_Utils_Tree {

  /**
   * Store the tree information as a string or array
   * @var string|array
   */
  private $tree;

  /**
   * Constructor. Creates the tree with a single root node.
   *
   * @param string $nodeName  The name for the root node.
   */
  public function __construct($nodeName) {
    // create the root node
    $rootNode = &$this->createNode($nodeName);

    // add the root node to the tree
    $this->tree['rootNode'] = &$rootNode;
  }

  /**
   * Recursively search for a node by name within a subtree.
   *
   * @param string $name        The node name to search for.
   * @param array  $parentNode  The subtree root to search within (passed by reference).
   *                            If empty/falsy, the search starts from the tree root.
   *
   * @return array|false  A reference to the matching node array, or FALSE if not found.
   */
  //public function &findNode(&$parentNode, $name)
  public function &findNode($name, &$parentNode) {
    // if no parent node specified, please start from root node
    if (!$parentNode) {
      $parentNode = &$this->tree['rootNode'];
    }

    // first check the nodename of subtree itself
    if ($parentNode['name'] == $name) {
      return $parentNode;
    }

    $falseRet = FALSE;
    // no children ? return false
    if ($this->isLeafNode($node)) {
      return $falseRet;
    }

    // search children of the subtree
    foreach ($parentNode['children'] as $key => $childNode) {
      $cNode = &$parentNode['children'][$key];
      if ($node = &$this->findNode($name, $cNode)) {
        return $node;
      }
    }

    // name does not match subtree or any of the children, negative result
    return $falseRet;
  }

  /**
   * Determine whether a node has no children (is a leaf node).
   *
   * @param array $node  The node to check (passed by reference).
   *
   * @return bool  TRUE if the node has children, FALSE if it is a leaf.
   */
  public function isLeafNode(&$node) {
    return (count($node['children']) ? TRUE : FALSE);
  }

  /**
   * Create and return a new node array with the given name.
   *
   * @param string $name  The name for the new node.
   *
   * @return array  A reference to the newly created node array with keys 'name', 'children', and 'data'.
   */
  public function &createNode($name) {
    $node['name'] = $name;
    $node['children'] = [];
    $node['data'] = [];

    return $node;
  }

  /**
   * Add a node as a child of the named parent node.
   *
   * @param string $parentName  The name of the parent node to attach the child to.
   * @param array  $node        The node to add (passed by reference).
   *
   * @return void
   */
  public function addNode($parentName, &$node) {
    $temp = '';
    $parentNode = &$this->findNode($parentName, $temp);

    $parentNode['children'][] = &$node;
  }

  /**
   * Attach arbitrary data to a named child node within a parent node.
   *
   * @param string $parentName  The name of the parent node to search within.
   * @param string $childName   The name of the child node to attach data to.
   * @param mixed  $data        The data to store in the child node's 'data' array.
   *
   * @return void
   */
  public function addData($parentName, $childName, $data) {
    $temp = '';
    if ($parentNode = &$this->findNode($parentName, $temp)) {
      foreach ($parentNode['children'] as $key => $childNode) {
        $cNode = &$parentNode['children'][$key];
        if ($cNode = &$this->findNode($childName, $parentNode)) {
          $cNode['data']['fKey'] = &$data;
        }
      }
    }
  }

  /**
   * Return the entire tree structure.
   *
   * @return array  The internal tree array with a 'rootNode' key.
   */
  public function getTree() {
    return $this->tree;
  }

  /**
   * Print the entire tree structure for debugging.
   *
   * @return void
   */
  public function display() {
    print_r($this->tree);
  }
}
