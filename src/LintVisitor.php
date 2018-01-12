<?php

namespace SimpleLint;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeAbstract;
use PhpParser\NodeVisitorAbstract;

class LintVisitor extends NodeVisitorAbstract
{
    /**
     * @var SeriailizedEntity[]
     */
    private $serialized_entities = [];
    private $namespace = '';
    private $serial_stack = [];
    private $function_prefix = '';
    private $variables_scope = [];

    public function enterNode(Node $node)
    {
        if (is_a($node, Class_::class)) {
            $this->serializeClass($node);
        } elseif (is_a($node, Namespace_::class)) {
            $this->setNamespace($node);
        } elseif (is_a($node, Use_::class)) {
            $this->serializeUse($node);
        } elseif (is_a($node, Function_::class) || is_a($node, ClassMethod::class)) {
            $this->serializeFunction($node);
        } elseif (is_a($node, Variable::class)) {
            $this->serializeVariable($node);
        }
    }

    private function serializeUse(Use_ $node)
    {
        if ($this->namespace) {
            $namespace = $this->namespace;
        } else {
            $namespace = '';
        }
        $this->pushStack("namespace {$namespace}");
        foreach ($node->uses as $use) {
            $this->insert($node, 'use ' . $use->name, 'use');
        }
        $this->popStack();
    }

    /**
     * @param Class_ $node
     */
    private function serializeClass(Class_ $node)
    {
        $name = $node->name;
        $extends = $node->extends;
        $implements = $node->implements;

        if (\strlen($this->namespace) > 0) {
            $this->pushStack("namespace {$this->namespace}");
        }
        $this->pushStack("class {$name}");
        if (\is_a($extends, Name::class)) {
            $extends_name = $extends->getFirst();
            $this->pushStack("extends {$extends_name}");
        }
        if (\is_array($implements)) {
            foreach ($implements as $implement) {
                $this->pushStack("implements {$implement}");
            }
        }
        $this->insert($node, '', 'class');
        if (\is_array($node->stmts)) {
            $this->pushStack('{');
            foreach ($node->stmts as $stmt) {
                if (is_a($stmt, ClassConst::class)) {
                    $this->serializeClassConst($stmt);
                } elseif (is_a($stmt, Property::class)) {
                    $this->serializeClassProperty($stmt);
                } elseif (is_a($stmt, ClassMethod::class)) {
                    $this->serializeClassMethod($stmt);
                }
            }
            $this->popStack();
        }
        if (\is_array($extends)) {
            foreach ($extends as $extend) {
                $this->popStack();
            }
        }
        if (\is_array($implements)) {
            foreach ($implements as $implement) {
                $this->popStack();
            }
        }
        $this->popStack();
        $this->popStack();
    }

    private function setNamespace(Namespace_ $node)
    {
        $this->namespace = (string)$node->name;
    }

    private function pushStack($string)
    {
        $this->serial_stack[] = $string;
    }

    private function popStack()
    {
        array_pop($this->serial_stack);
    }

    private function insert(NodeAbstract $node, $string, $type)
    {
        $line = $node->getAttribute('startLine');
        $stacked_string = implode(' ', $this->serial_stack);
        $save_string = $stacked_string . ' ' . $string;
        $save_string = trim($save_string);

        $this->serialized_entities[] = new SeriailizedEntity($line, $node->getAttribute('startFilePos'), $save_string, $type);
    }

    private function serializeClassConst(ClassConst $stmt)
    {
        $clauses = [];
        if ($stmt->isPublic()) {
            $clauses[] = 'public';
        } elseif ($stmt->isPrivate()) {
            $clauses[] = 'private';
        } elseif ($stmt->isProtected()) {
            $clauses[] = 'protected';
        }
        if ($stmt->isStatic()) {
            $clauses[] = 'static';
        }
        $this->pushStack(implode(' ', $clauses));
        foreach ($stmt->consts as $const) {
            $this->insert($const, $const->name, 'const');
        }
        $this->popStack();
    }

    private function serializeClassProperty(Property $stmt)
    {
        $clauses = [];
        if ($stmt->isPublic()) {
            $clauses[] = 'public';
        } elseif ($stmt->isPrivate()) {
            $clauses[] = 'private';
        } elseif ($stmt->isProtected()) {
            $clauses[] = 'protected';
        }
        if ($stmt->isStatic()) {
            $clauses[] = 'static';
        }
        $this->pushStack(implode(' ', $clauses));
        foreach ($stmt->props as $prop) {
            $this->insert($prop, '$' . $prop->name, 'property');
        }
        $this->popStack();
    }

    private function serializeClassMethod(ClassMethod $stmt)
    {
        $clauses = [];
        if ($stmt->isPublic()) {
            $clauses[] = 'public';
        } elseif ($stmt->isPrivate()) {
            $clauses[] = 'private';
        } elseif ($stmt->isProtected()) {
            $clauses[] = 'protected';
        }
        if ($stmt->isAbstract()) {
            $clauses[] = 'abstract';
        }
        if ($stmt->isFinal()) {
            $clauses[] = 'final';
        }
        if ($stmt->isStatic()) {
            $clauses[] = 'static';
        }

        $clauses[] = 'function';
        $clauses[] = $stmt->name;
        $clauses[] = '(';

        $this->pushStack(implode(' ', $clauses));
        foreach ($stmt->params as $param) {
            if ($param->type) {
                $type = (string)$param->type . ' ';
            } else {
                $type = '';
            }
            $this->insert($stmt, $type . '$' . $param->name, 'param');
        }

        $clauses = [];
        $clauses[] = ')';
        if ($stmt->returnType) {
            $clauses[] = ':' . (string)$stmt->returnType;
        }

        $this->pushStack(implode(' ', $clauses));
        $this->insert($stmt, '', 'function');
        $this->popStack();
        $this->popStack();
    }

    /**
     * @param Function_ $stmt
     */
    private function serializeFunction($stmt)
    {
        $clauses = [];
        $clauses[] = 'function';
        $clauses[] = $stmt->name;
        $clauses[] = '(';
        $clauses[] = ')';
        if ($stmt->returnType) {
            $clauses[] = ':' . (string)$stmt->returnType;
        }
        $clauses[] = '{';

        $this->function_prefix = implode(' ', $clauses);
        $this->variables_scope = [];
    }

    private function serializeVariable(Variable $node)
    {
        if (isset($this->variables_scope[$node->name])) {
            return;
        }
        $this->variables_scope[$node->name] = true;
        $this->insert($node, $this->function_prefix . ' $' . $node->name, 'var');
    }

    public function getSerializedEntities()
    {
        return $this->serialized_entities;
    }
}
