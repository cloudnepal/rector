<?php

declare (strict_types=1);
namespace Rector\Symfony\ValueObject\ValidatorAssert;

use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
final class PropertyAndAnnotation
{
    /**
     * @readonly
     */
    private string $property;
    /**
     * @readonly
     */
    private DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode;
    public function __construct(string $property, DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode)
    {
        $this->property = $property;
        $this->doctrineAnnotationTagValueNode = $doctrineAnnotationTagValueNode;
    }
    public function getProperty() : string
    {
        return $this->property;
    }
    public function getDoctrineAnnotationTagValueNode() : DoctrineAnnotationTagValueNode
    {
        return $this->doctrineAnnotationTagValueNode;
    }
}
