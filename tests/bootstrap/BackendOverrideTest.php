<?php namespace NumenCode\Fundamentals\Tests\Bootstrap;

use Event;
use Winter\Storm\Database\Model;

class BackendOverrideTest extends \PluginTestCase
{
    public function testListColumnRenderingOverride()
    {
        Event::listen('backend.list.overrideColumnValue', function ($list, $record, $column, $value) {
            if ($column->type === 'switch') {
                $renderedValue = array_get($record, str_replace(['[', ']'], ['.', ''], $column->columnName)) ?
                    '<i style="color:#2ecc71;" class="icon-check"></i> Yes' :
                    '<i style="color:#d6220f;" class="icon-times"></i> No';

                $this->assertEquals(
                    $renderedValue,
                    $column->type === 'switch' ? '<i style="color:#2ecc71;" class="icon-check"></i> Yes' : '<i style="color:#d6220f;" class="icon-times"></i> No'
                );
            }
        });

        $list = new Model();
        $record = ['status' => true];
        $column = (object)['type' => 'switch', 'columnName' => 'status'];

        Event::fire('backend.list.overrideColumnValue', [$list, $record, $column, true]);
    }
}
