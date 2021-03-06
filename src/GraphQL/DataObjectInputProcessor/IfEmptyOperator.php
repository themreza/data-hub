<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor;


use GraphQL\Type\Definition\ResolveInfo;
use Pimcore\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use Pimcore\Model\DataObject\Concrete;

class IfEmptyOperator extends BaseOperator
{

    /**
     * Base constructor.
     * @param $nodeDef
     */
    public function __construct($nodeDef)
    {
        parent::__construct($nodeDef);
    }


    /**
     * @param Concrete $object
     * @param $newValue
     * @param $args
     * @param array $context
     * @param ResolveInfo $info
     * @return void|null
     * @throws \Exception|\UnexpectedValueException
     */
    public function process(Concrete $object, $newValue, $args, $context, ResolveInfo $info)
    {
        $class = $object->getClass();

        $nodeDef = $this->nodeDef;
        $nodeDefAttributes = $nodeDef["attributes"];
        $children = $nodeDefAttributes['childs'];
        if (!$children) {
            return null;
        }

        if (count($children) !== 1) {
            throw new ClientSafeException("Only one child allowed");
        }

        $firstChild = $children[0];

        if ($firstChild['isOperator']) {
            throw new ClientSafeException("First child should not be an operator");
        }

        $key = $firstChild["attributes"]["attribute"];
        $fieldDefinition = $this->getGraphQlService()->getObjectFieldHelper()->getFieldDefinitionFromKey($class, $key);
        if ($fieldDefinition) {
            $valueResolver = $this->getGraphQlService()->buildValueResolverFromAttributes($firstChild);
            $valueFromChild = $valueResolver->getLabeledValue($object, null);

            if (!$valueFromChild || $fieldDefinition->isEmpty($valueFromChild->value)) {
                $parentProcessor = $this->getParentProcessor($this->nodeDef, $class);
                if ($parentProcessor) {
                    call_user_func_array($parentProcessor, [$object, $newValue, $args, $context, $info]);
                }
            }
        }
    }
}

