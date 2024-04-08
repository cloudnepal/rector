<?php

declare (strict_types=1);
namespace Rector\Php81\Rector\Array_;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeTraverser;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\NodeCollector\NodeAnalyzer\ArrayCallableMethodMatcher;
use Rector\NodeCollector\ValueObject\ArrayCallable;
use Rector\Rector\AbstractScopeAwareRector;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Rector\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://php.watch/versions/8.1/first-class-callable-syntax
 *
 * @see \Rector\Tests\Php81\Rector\Array_\FirstClassCallableRector\FirstClassCallableRectorTest
 */
final class FirstClassCallableRector extends AbstractScopeAwareRector implements MinPhpVersionInterface
{
    /**
     * @readonly
     * @var \Rector\NodeCollector\NodeAnalyzer\ArrayCallableMethodMatcher
     */
    private $arrayCallableMethodMatcher;
    /**
     * @readonly
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    public function __construct(ArrayCallableMethodMatcher $arrayCallableMethodMatcher, ReflectionProvider $reflectionProvider)
    {
        $this->arrayCallableMethodMatcher = $arrayCallableMethodMatcher;
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Upgrade array callable to first class callable', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        $name = [$this, 'name'];
    }

    public function name()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        $name = $this->name(...);
    }

    public function name()
    {
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [Property::class, ClassConst::class, Array_::class];
    }
    /**
     * @param Property|ClassConst|Array_ $node
     * @return int|null|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\MethodCall
     */
    public function refactorWithScope(Node $node, Scope $scope)
    {
        if ($node instanceof Property || $node instanceof ClassConst) {
            return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
        }
        $arrayCallable = $this->arrayCallableMethodMatcher->match($node, $scope);
        if (!$arrayCallable instanceof ArrayCallable) {
            return null;
        }
        $callerExpr = $arrayCallable->getCallerExpr();
        if (!$callerExpr instanceof Variable && !$callerExpr instanceof PropertyFetch && !$callerExpr instanceof ClassConstFetch) {
            return null;
        }
        $args = [new VariadicPlaceholder()];
        if ($callerExpr instanceof ClassConstFetch) {
            $type = $this->getType($callerExpr->class);
            if ($type instanceof FullyQualifiedObjectType && $this->isNonStaticOtherObject($type, $arrayCallable, $scope)) {
                return null;
            }
            return new StaticCall($callerExpr->class, $arrayCallable->getMethod(), $args);
        }
        return new MethodCall($callerExpr, $arrayCallable->getMethod(), $args);
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersion::PHP_81;
    }
    private function isNonStaticOtherObject(FullyQualifiedObjectType $fullyQualifiedObjectType, ArrayCallable $arrayCallable, Scope $scope) : bool
    {
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        if ($classReflection->getName() === $fullyQualifiedObjectType->getClassName()) {
            return \false;
        }
        $arrayClassReflection = $this->reflectionProvider->getClass($arrayCallable->getClass());
        // we're unable to find it
        if (!$arrayClassReflection->hasMethod($arrayCallable->getMethod())) {
            return \false;
        }
        $extendedMethodReflection = $arrayClassReflection->getMethod($arrayCallable->getMethod(), $scope);
        if (!$extendedMethodReflection->isStatic()) {
            return \true;
        }
        return !$extendedMethodReflection->isPublic();
    }
}
