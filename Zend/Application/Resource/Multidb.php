<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Application
 * @subpackage Resource
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @namespace
 */
namespace Zend\Application\Resource;

use Zend\DB\Adapter,
    Zend\Application\ResourceException;

/**
 * Database resource for multiple database setups
 *
 * Example configuration:
 * <pre>
 *   resources.multidb.db1.adapter = "pdo_mysql"
 *   resources.multidb.db1.host = "localhost"
 *   resources.multidb.db1.username = "webuser"
 *   resources.multidb.db1.password = "XXXX"
 *   resources.multidb.db1.dbname = "db1"
 *   resources.multidb.db1.default = true
 *   
 *   resources.multidb.db2.adapter = "pdo_pgsql"
 *   resources.multidb.db2.host = "example.com"
 *   resources.multidb.db2.username = "dba"
 *   resources.multidb.db2.password = "notthatpublic"
 *   resources.multidb.db2.dbname = "db2"
 * </pre>
 *
 * @uses       \Zend\Application\ResourceException
 * @uses       \Zend\Application\Resource\AbstractResource
 * @uses       \Zend\DB\DB
 * @uses       \Zend\DB\Table\Table
 * @category   Zend
 * @package    Zend_Application
 * @subpackage Resource
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Multidb extends AbstractResource
{
    /**
     * Associative array containing all configured db's
     * 
     * @var array
     */   
    protected $_dbs = array();
    
    /**
     * An instance of the default db, if set
     * 
     * @var null|\Zend\DB\Adapter\AbstractAdapter
     */
    protected $_defaultDb;

    /**
     * Initialize the Database Connections (instances of Zend_Db_Table_Abstract)
     *
     * @return \Zend\Application\Resource\MultiDB
     */    
    public function init() 
    {
        $options = $this->getOptions();
        
        foreach ($options as $id => $params) {
        	$adapter = $params['adapter'];
            $default = isset($params['default'])?(int)$params['default']:false;
            unset($params['adapter'], $params['default']);
        	
            $this->_dbs[$id] = \Zend\DB\DB::factory($adapter, $params);

            if ($default
                // For consistency with the Db Resource Plugin
                || (isset($params['isDefaultTableAdapter']) 
                    && $params['isDefaultTableAdapter'] == true)
            ) {
                $this->_setDefault($this->_dbs[$id]);
            }
        }
        
        return $this;
    }

    /**
     * Determine if the given db(identifier) is the default db.
     *
     * @param  string|\Zend\DB\Adapter\AbstractAdapter $db The db to determine whether it's set as default
     * @return boolean True if the given parameter is configured as default. False otherwise
     */
    public function isDefault($db)
    {
        if(!$db instanceof Adapter\AbstractAdapter) {
            $db = $this->getDb($db);
        }

        return $db === $this->_defaultDb;
    }

    /**
     * Retrieve the specified database connection
     * 
     * @param  null|string|\Zend\DB\Adapter\AbstractAdapter $db The adapter to retrieve.
     *                                               Null to retrieve the default connection
     * @return \Zend\DB\Adapter\AbstractAdapter
     * @throws \Zend\Application\ResourceException if the given parameter could not be found
     */
    public function getDb($db = null) 
    {
        if ($db === null) {
            return $this->getDefaultDb();
        }
        
        if (isset($this->_dbs[$db])) {
            return $this->_dbs[$db];
        }
        
        throw new ResourceException(
            'A DB adapter was tried to retrieve, but was not configured'
        );
    }

    /**
     * Get the default db connection
     * 
     * @param  boolean $justPickOne If true, a random (the first one in the stack)
     *                           connection is returned if no default was set.
     *                           If false, null is returned if no default was set.
     * @return null|\Zend\DB\Adapter\AbstractAdapter
     */
    public function getDefaultDb($justPickOne = true) 
    {
        if ($this->_defaultDb !== null) {
            return $this->_defaultDb;
        }

        if ($justPickOne) {
            return reset($this->_dbs); // Return first db in db pool
        }
        
        return null;
    }

    /**
     * Set the default db adapter
     * 
     * @var \Zend\DB\Adapter\AbstractAdapter $adapter Adapter to set as default
     */
    protected function _setDefault(Adapter\AbstractAdapter $adapter) 
    {
        \Zend\DB\Table\Table::setDefaultAdapter($adapter);
        $this->_defaultDb = $adapter;
    }
}
