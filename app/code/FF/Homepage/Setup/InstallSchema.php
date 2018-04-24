<?php
namespace FF\Homepage\Setup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

use Magento\Framework\DB\Ddl\Table;
 
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
 
        // Get events table
        $tableName = $installer->getTable('homepage_promobanners');
        
        // Check if the table events already exists
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            // Create quotation table
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'banner_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                        'LENGTH' => 11
                    ],
                    'Banner ID'
                )
                ->addColumn(
                    'title',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false],
                    'Banner Text'
                )
                ->addColumn(
                    'sort_order', 
                    Table::TYPE_INTEGER, 
                    11, 
                    ['nullable' => false, 'default' => '0'],
                    'Sort Order'
                )
                ->addColumn(
                    'is_active', 
                    Table::TYPE_SMALLINT, 
                    null, 
                    ['nullable' => false, 'default' => '1'],
                    'Status')
                ->setComment('Homepage Promotional Banners.')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }
      
         
        $installer->endSetup();
    }
}
