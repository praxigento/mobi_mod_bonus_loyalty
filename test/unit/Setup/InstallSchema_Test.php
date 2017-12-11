<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusLoyalty\Setup;

use Praxigento\BonusLoyalty\Repo\Entity\Data\Cfg\Param;
use Praxigento\BonusLoyalty\Repo\Entity\Data\Qualification;

include_once(__DIR__ . '/../phpunit_bootstrap.php');

class InstallSchema_UnitTest extends \Praxigento\Core\Test\BaseCase\Mockery
{

    /** @var  \Mockery\MockInterface */
    private $mConn;
    /** @var  \Mockery\MockInterface */
    private $mContext;
    /** @var  \Mockery\MockInterface */
    private $mSetup;
    /** @var  \Mockery\MockInterface */
    private $mToolDem;
    /** @var  InstallSchema */
    private $obj;

    public function setUp()
    {
        parent::setUp();
        $this->markTestSkipped('Test is deprecated after M1 & M2 merge is done.');
        /** create mocks */
        $this->mConn = $this->_mockConn();
        $this->mToolDem = $this->_mock(\Praxigento\Core\App\Setup\Dem\Tool::class);
        $this->mSetup = $this->_mock(\Magento\Framework\Setup\SchemaSetupInterface::class);
        $this->mContext = $this->_mock(\Magento\Framework\Setup\ModuleContextInterface::class);
        /** create object to test */
        $mResource = $this->_mockResourceConnection($this->mConn);
        $this->obj = new InstallSchema($mResource, $this->mToolDem);
    }

    public function test_install()
    {
        /** === Test Data === */
        /** === Setup Mocks === */
        // $setup->startSetup();
        $this->mSetup
            ->shouldReceive('startSetup')->once();
        // $demPackage = $this->_toolDem->readDemPackage($pathToFile, $pathToNode);
        $mDemPackage = $this->_mock(DataObject::class);
        $this->mToolDem
            ->shouldReceive('readDemPackage')->once()
            ->withArgs([\Mockery::any(), '/dBEAR/package/Praxigento/package/Accounting'])
            ->andReturn($mDemPackage);
        // $demEntity = $demPackage->get('package/Type/entity/Asset');
        $mDemPackage->shouldReceive('getData');
        //
        // $this->_toolDem->createEntity($entityAlias, $demEntity);
        //
        $this->mToolDem->shouldReceive('createEntity')->withArgs([Param::ENTITY_NAME, \Mockery::any()]);
        $this->mToolDem->shouldReceive('createEntity')->withArgs([Qualification::ENTITY_NAME, \Mockery::any()]);
        // $setup->endSetup();
        $this->mSetup
            ->shouldReceive('endSetup')->once();
        /** === Call and asserts  === */
        $this->obj->install($this->mSetup, $this->mContext);
    }
}