<?php

namespace Flying\Bundle\ClientActionBundle\Tests\Struct\Fixtures;

use Flying\Bundle\ClientActionBundle\State\State;

/**
 * @Struct\String(name="category", default="main")
 * @Struct\Collection(name="selected", default={1,2,3})
 * @Struct\Struct(name="sort", {
 * @Struct\String(name="column", default="date", nullable=false),
 * @Struct\Enum(name="order", values={"asc", "desc"}, default="desc", nullable=false)
 * })
 * @Struct\Struct(name="paginator", {
 * @Struct\Int(name="page", default=1, min=1),
 * @Struct\Int(name="page_size", default=20, min=10)
 * })
 * @Struct\Struct(name="synthetic", {
 * @Struct\String(name="test", default="for"),
 * @Struct\Struct(name="multiple", {
 * @Struct\String(name="structure", default="levels")
 *      })
 * })
 */
class MultiLevelState extends State implements TestStateInterface
{
    /**
     * {@inheritdoc}
     */
    public function getExpectedDefaults()
    {
        return array(
            'category'  => 'main',
            'selected'  => array(1, 2, 3),
            'sort'      => array(
                'column' => 'date',
                'order'  => 'desc',
            ),
            'paginator' => array(
                'page'      => 1,
                'page_size' => 20,
            ),
            'synthetic' => array(
                'test'     => 'for',
                'multiple' => array(
                    'structure' => 'levels',
                ),
            ),
        );
    }
}
