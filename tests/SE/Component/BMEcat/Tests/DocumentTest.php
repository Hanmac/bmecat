<?php
/**
 * This file is part of the BMEcat php library
 *
 * (c) Sven Eisenschmidt <sven.eisenschmidt@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SE\Component\BMEcat\Tests;

use SE\Component\BMEcat\SchemaValidator;

/**
 *
 * @package SE\Component\BMEcat\Tests
 * @author Sven Eisenschmidt <sven.eisenschmidt@gmail.com>
 */
class DocumentTest extends \PHPUnit\Framework\TestCase
{

    public function setUp() : void
    {
        $data = [
            'document' => [
                'header' =>[
                    'generator_info' => 'DocumentTest Document',
                    'catalog' => [
                        'language'  => 'eng',
                        'id'        => 'MY_CATALOG',
                        'version'   => '0.99',
                        'datetime'  => [
                            'date' => '1979-01-01',
                            'time' => '10:59:54',
                            'timezone' => '-01:00',
                        ]
                    ],
                    'supplier' => [
                        'id'    => 'BMECAT_TEST',
                        'name'  => 'TestSupplier',
                    ]
                ]
            ]
        ];

        $builder = new \SE\Component\BMEcat\DocumentBuilder();
        $builder->build();
        $builder->load($data);

        $catalog = new \SE\Component\BMEcat\Node\NewCatalogNode;
        $builder->getDocument()->setNewCatalog($catalog);

        foreach([1,2,3] as $index) {
            $product = new \SE\Component\BMEcat\Node\ProductNode;
            $product->setId($index);

            foreach([['EUR', 10.50], ['GBP', 7.30]] as $value) {
                list($currency, $amount) = $value;

                $price = new \SE\Component\BMEcat\Node\ProductPriceNode;

                $price->setPrice($amount);
                $price->setCurrency($currency);
                $price->setSupplierPrice(round($amount/2,2));

                $product->addPrice($price);
            }

            foreach([['A', 'B', 'C', 1, 2, 'D', 'E'],['F', 'G', 'H', 3, 4, 'I', 'J']] as $value) {
                list($systemName, $groupName, $groupId, $serialNumber, $tarifNumber, $countryOfOrigin, $tariftext) = $value;

                $features = new \SE\Component\BMEcat\Node\ProductFeaturesNode;

                $features->setReferenceFeatureSystemName($systemName);
                $features->setReferenceFeatureGroupName($groupName);
                $features->setReferenceFeatureGroupId($groupId);

                // Only for PIXI Import
                $features->setSerialNumberRequired($serialNumber);
                $features->setCustomsTariffNumber($tarifNumber);
                $features->setCustomsCountryOfOrigin($countryOfOrigin);
                $features->setCustomsTariffText($tariftext);

                $product->addFeatures($features);
            }

            foreach([
                ['image/jpeg', 'http://a.b/c/d.jpg', 'normal'],
                ['image/bmp', 'http://w.x/y/z.bmp', 'thumbnail']
                    ] as $value) {

                list($type, $source, $purpose) = $value;

                $mime = new \SE\Component\BMEcat\Node\MimeNode();

                $mime->setType($type);
                $mime->setSource($source);
                $mime->setPurpose($purpose);

                $product->addMime($mime);
            }

            $orderDetails = new \SE\Component\BMEcat\Node\ProductOrderDetailsNode;
            $orderDetails->setNoCuPerOu(1);
            $orderDetails->setPriceQuantity(1);
            $orderDetails->setQuantityMin(1);
            $orderDetails->setQuantityInterval(1);

            $product->setOrderDetails($orderDetails);

            $catalog->addProducts($product);
        }

        $this->builder = $builder;
    }

    /**
     *
     * @test
     */
    public function Compare_Document_With_Null_Values()
    {
        $this->builder->setSerializeNull(true);

        $expected = file_get_contents(__DIR__.'/Fixtures/document_with_null_values.xml');
        $actual = $this->builder->toString();

        $this->assertEquals($expected, $actual);

        $this->assertTrue(
            SchemaValidator::isValid($actual, '2005.1')
        );
    }

    /**
     *
     * @test
     */
    public function Compare_Document_Without_Null_Values()
    {
        $this->builder->setSerializeNull(false);

        $expected = file_get_contents(__DIR__.'/Fixtures/document_without_null_values.xml');
        $actual = $this->builder->toString();

        $this->assertEquals($expected, $actual);

        $this->assertTrue(
            SchemaValidator::isValid($actual, '2005.1')
        );
    }
}
