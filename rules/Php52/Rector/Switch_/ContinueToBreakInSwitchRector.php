<?php

declare (strict_types=1);
namespace Rector\Php52\Rector\Switch_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Switch_;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ConstantType;
use Rector\PhpParser\Node\Value\ValueResolver;
use Rector\Rector\AbstractRector;
use Rector\ValueObject\PhpVersionFeature;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\Php52\Rector\Switch_\ContinueToBreakInSwitchRector\ContinueToBreakInSwitchRectorTest
 */
final class ContinueToBreakInSwitchRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @readonly
     * @var \Rector\PhpParser\Node\Value\ValueResolver
     */
    private $valueResolver;
    public function __construct(ValueResolver $valueResolver)
    {
        $this->valueResolver = $valueResolver;
    }
    public function provideMinPhpVersion() : int
    {
        return PhpVersionFeature::CONTINUE_TO_BREAK;
    }
    public function getRuleDefinition() : RuleDefinition
    {
        return new RuleDefinition('Use break instead of continue in switch statements', [new CodeSample(<<<'CODE_SAMPLE'
function some_run($value)
{
    switch ($value) {
        case 1:
            echo 'Hi';
            continue;
        case 2:
            echo 'Hello';
            break;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
function some_run($value)
{
    switch ($value) {
        case 1:
            echo 'Hi';
            break;
        case 2:
            echo 'Hello';
            break;
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
        return [Switch_::class];
    }
    /**
     * @param Switch_ $node
     */
    public function refactor(Node $node) : ?Switch_
    {
        $hasChanged = \false;
        foreach ($node->cases as $case) {
            foreach ($case->stmts as $key => $caseStmt) {
                if (!$caseStmt instanceof Continue_) {
                    continue;
                }
                $newStmt = $this->processContinueStatement($caseStmt);
                if ($newStmt instanceof Continue_) {
                    continue;
                }
                $case->stmts[$key] = $newStmt;
                $hasChanged = \true;
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    /**
     * @return \PhpParser\Node\Stmt\Break_|\PhpParser\Node\Stmt\Continue_
     */
    private function processContinueStatement(Continue_ $continue)
    {
        if (!$continue->num instanceof Expr) {
            return new Break_();
        }
        if ($continue->num instanceof LNumber) {
            $continueNumber = $this->valueResolver->getValue($continue->num);
            if ($continueNumber <= 1) {
                return new Break_();
            }
        } elseif ($continue->num instanceof Variable) {
            return $this->processVariableNum($continue, $continue->num);
        }
        return $continue;
    }
    /**
     * @return \PhpParser\Node\Stmt\Continue_|\PhpParser\Node\Stmt\Break_
     */
    private function processVariableNum(Continue_ $continue, Variable $numVariable)
    {
        $staticType = $this->getType($numVariable);
        if (!$staticType instanceof ConstantType) {
            return $continue;
        }
        if (!$staticType instanceof ConstantIntegerType) {
            return $continue;
        }
        if ($staticType->getValue() > 1) {
            return $continue;
        }
        return new Break_();
    }
}
