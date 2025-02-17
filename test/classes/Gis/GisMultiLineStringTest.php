<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisMultiLineString;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

/**
 * @covers \PhpMyAdmin\Gis\GisMultiLineString
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GisMultiLineStringTest extends GisGeomTestCase
{
    /** @var    GisMultiLineString */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = GisMultiLineString::singleton();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return array data for testGenerateWkt
     */
    public function providerForTestGenerateWkt(): array
    {
        $temp = [
            0 => [
                'MULTILINESTRING' => [
                    'no_of_lines' => 2,
                    0 => [
                        'no_of_points' => 2,
                        0 => [
                            'x' => 5.02,
                            'y' => 8.45,
                        ],
                        1 => [
                            'x' => 6.14,
                            'y' => 0.15,
                        ],
                    ],
                    1 => [
                        'no_of_points' => 2,
                        0 => [
                            'x' => 1.23,
                            'y' => 4.25,
                        ],
                        1 => [
                            'x' => 9.15,
                            'y' => 0.47,
                        ],
                    ],
                ],
            ],
        ];

        $temp1 = $temp;
        unset($temp1[0]['MULTILINESTRING'][1][1]['y']);

        $temp2 = $temp;
        $temp2[0]['MULTILINESTRING']['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['MULTILINESTRING'][1]['no_of_points'] = 1;

        return [
            [
                $temp,
                0,
                null,
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',
            ],
            // values at undefined index
            [
                $temp,
                1,
                null,
                'MULTILINESTRING(( , ))',
            ],
            // if a coordinate is missing, default is empty string
            [
                $temp1,
                0,
                null,
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 ))',
            ],
            // missing coordinates are replaced with provided values (3rd parameter)
            [
                $temp1,
                0,
                '0',
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0))',
            ],
            // at least one line should be there
            [
                $temp2,
                0,
                null,
                'MULTILINESTRING((5.02 8.45,6.14 0.15))',
            ],
            // a line should have at least two points
            [
                $temp3,
                0,
                '0',
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',
            ],
        ];
    }

    /**
     * test getShape method
     */
    public function testGetShape(): void
    {
        $row_data = [
            'numparts' => 2,
            'parts' => [
                0 => [
                    'points' => [
                        0 => [
                            'x' => 5.02,
                            'y' => 8.45,
                        ],
                        1 => [
                            'x' => 6.14,
                            'y' => 0.15,
                        ],
                    ],
                ],
                1 => [
                    'points' => [
                        0 => [
                            'x' => 1.23,
                            'y' => 4.25,
                        ],
                        1 => [
                            'x' => 9.15,
                            'y' => 0.47,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals(
            'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',
            $this->object->getShape($row_data)
        );
    }

    /**
     * data provider for testGenerateParams
     *
     * @return array data for testGenerateParams
     */
    public function providerForTestGenerateParams(): array
    {
        $temp = [
            'MULTILINESTRING' => [
                'no_of_lines' => 2,
                0 => [
                    'no_of_points' => 2,
                    0 => [
                        'x' => 5.02,
                        'y' => 8.45,
                    ],
                    1 => [
                        'x' => 6.14,
                        'y' => 0.15,
                    ],
                ],
                1 => [
                    'no_of_points' => 2,
                    0 => [
                        'x' => 1.23,
                        'y' => 4.25,
                    ],
                    1 => [
                        'x' => 9.15,
                        'y' => 0.47,
                    ],
                ],
            ],
        ];

        $temp1 = $temp;
        $temp1['gis_type'] = 'MULTILINESTRING';

        return [
            [
                "'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',124",
                null,
                [
                    'srid' => 124,
                    0 => $temp,
                ],
            ],
            [
                'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',
                2,
                [2 => $temp1],
            ],
        ];
    }

    /**
     * data provider for testScaleRow
     *
     * @return array data for testScaleRow
     */
    public function providerForTestScaleRow(): array
    {
        return [
            [
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                [
                    'minX' => 17,
                    'maxX' => 178,
                    'minY' => 10,
                    'maxY' => 75,
                ],
            ],
        ];
    }

    /**
     * @requires extension gd
     */
    public function testPrepareRowAsPng(): void
    {
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        $this->assertNotNull($image);
        $return = $this->object->prepareRowAsPng(
            'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
            'image',
            [176, 46, 224],
            ['x' => 3, 'y' => -16, 'scale' => 1.06, 'height' => 124],
            $image
        );
        $this->assertEquals(200, $return->width());
        $this->assertEquals(124, $return->height());

        $fileExpected = $this->testDir . '/multilinestring-expected.png';
        $fileActual = $this->testDir . '/multilinestring-actual.png';
        $this->assertTrue($image->png($fileActual));
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param string $label      label for the GIS MULTILINESTRING object
     * @param int[]  $color      color for the GIS MULTILINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param TCPDF  $pdf        TCPDF instance
     *
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scale_data,
        TCPDF $pdf
    ): void {
        $return = $this->object->prepareRowAsPdf($spatial, $label, $color, $scale_data, $pdf);

        $fileExpected = $this->testDir . '/multilinestring-expected.pdf';
        $fileActual = $this->testDir . '/multilinestring-actual.pdf';
        $return->Output($fileActual, 'F');
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                'pdf',
                [176, 46, 224],
                ['x' => 4, 'y' => -90, 'scale' => 1.12, 'height' => 297],
                $this->createEmptyPdf('MULTILINESTRING'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string $spatial   GIS MULTILINESTRING object
     * @param string $label     label for the GIS MULTILINESTRING object
     * @param int[]  $color     color for the GIS MULTILINESTRING object
     * @param array  $scaleData array containing data related to scaling
     * @param string $output    expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        string $output
    ): void {
        $svg = $this->object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        $this->assertEquals($output, $svg);
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                'svg',
                [176, 46, 224],
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                '<polyline points="48,260 70,242 100,138 " name="svg" '
                . 'class="linestring vector" fill="none" stroke="#b02ee0" '
                . 'stroke-width="2" id="svg1234567890"/><polyline points="48,268 10,'
                . '242 332,182 " name="svg" class="linestring vector" fill="none" '
                . 'stroke="#b02ee0" stroke-width="2" id="svg1234567890"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS MULTILINESTRING object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS MULTILINESTRING object
     * @param int[]  $color      color for the GIS MULTILINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
        array $scale_data,
        string $output
    ): void {
        $ol = $this->object->prepareRowAsOl($spatial, $srid, $label, $color, $scale_data);
        $this->assertEquals($output, $ol);
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ],
                'var style = new ol.style.Style({stroke: new ol.style.Stroke({"color":[176,46,224],'
                . '"width":2}), text: new ol.style.Text({"text":"Ol"})});var minLoc = [0, 0];var ma'
                . 'xLoc = [1, 1];var ext = ol.extent.boundingExtent([minLoc, maxLoc]);ext = ol.proj'
                . '.transformExtent(ext, ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'));map.'
                . 'getView().fit(ext, map.getSize());var arr = [];var lineArr = [];var line = new o'
                . 'l.geom.LineString(new Array((new ol.geom.Point([36,14]).transform(ol.proj.get("E'
                . 'PSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Point([4'
                . '7,23]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoord'
                . 'inates(), (new ol.geom.Point([62,75]).transform(ol.proj.get("EPSG:4326"), ol.pro'
                . 'j.get(\'EPSG:3857\'))).getCoordinates()));var coord = line.getCoordinates();for (var i = 0;'
                . ' i < coord.length; i++) lineArr.push(coord[i]);arr.push(lineArr);var lineArr = [];var lin'
                . 'e = new ol.geom.LineString(new Array((new ol.geom.Point([36,10]).transform(ol.pr'
                . 'oj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom'
                . '.Point([17,23]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\')))'
                . '.getCoordinates(), (new ol.geom.Point([178,53]).transform(ol.proj.get("EPSG:4326'
                . '"), ol.proj.get(\'EPSG:3857\'))).getCoordinates()));var coord = line.getCoordina'
                . 'tes();for (var i = 0; i < coord.length; i++) lineArr.push(coord[i]);arr.push(lineArr);va'
                . 'r multiLineString = new ol.geom.MultiLineString(arr);var feature = new ol.Feature({geometry:'
                . ' multiLineString});feature.setStyle(style);vectorLayer.addFeature(feature);',
            ],
        ];
    }
}
