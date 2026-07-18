<?php

namespace SonarFixer\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitor;

/**
 * Abstract base class for all SonarFixer rule visitors
 *
 * This class implements the Visitor Pattern for AST traversal and transformation.
 * Each rule-specific visitor should extend this class and implement rule-specific
 * logic in the leaveNode() or enterNode() methods.
 *
 * @package SonarFixer\Visitors
 */
abstract class BaseVisitor implements NodeVisitor
{
    /**
     * Collection of changes made during traversal
     *
     * @var array<int, array{rule: string, line: int, column: int, description: string}>
     */
    protected array $changes = [];

    /**
     * Stack of nodes being processed (for context tracking)
     *
     * @var array<int, Node>
     */
    protected array $nodeStack = [];

    /**
     * Current line number for change tracking
     *
     * @var int
     */
    protected int $currentLine = 0;

    /**
     * Map of node attributes to preserve during transformation
     *
     * @var array<string, mixed>
     */
    protected array $nodeAttributes = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->changes = [];
        $this->nodeStack = [];
        $this->currentLine = 0;
    }

    /**
     * Called when entering a node during tree traversal
     *
     * Return value:
     * - null: Continue with next node
     * - NodeTraverser::REMOVE_NODE: Remove this node
     * - NodeTraverser::SKIP_CHILDREN: Don't visit children of this node
     * - any Node: Replace this node with returned node
     *
     * @param Node $node The node being entered
     * @return null|int|Node
     */
    public function enterNode(Node $node): null|int|Node
    {
        // Track the node in the stack
        $this->nodeStack[] = $node;

        // Update current line if available
        if ($node->hasAttribute('startLine')) {
            $this->currentLine = $node->getAttribute('startLine');
        }

        return null;
    }

    /**
     * Called when leaving a node during tree traversal
     *
     * This is where most transformations happen. Override this method
     * in child classes to implement rule-specific logic.
     *
     * @param Node $node The node being left
     * @return null|int|Node
     */
    public function leaveNode(Node $node): null|int|Node
    {
        // Remove the node from the stack
        if (!empty($this->nodeStack)) {
            array_pop($this->nodeStack);
        }

        return null;
    }

    /**
     * Record a change made to the code
     *
     * This method should be called when a rule fix is applied to a node.
     * It records the change for reporting purposes.
     *
     * @param Node $node The node that was changed
     * @param string $rule The rule ID that triggered the change (e.g., 'S1125')
     * @param string $description Human-readable description of the change
     * @return void
     */
    protected function recordChange(Node $node, string $rule, string $description): void
    {
        $line = $this->getNodeLine($node);
        $column = $this->getNodeColumn($node);

        $this->changes[] = [
            'rule' => $rule,
            'line' => $line,
            'column' => $column,
            'description' => $description,
        ];
    }

    /**
     * Get all recorded changes
     *
     * @return array<int, array{rule: string, line: int, column: int, description: string}>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * Get the count of recorded changes
     *
     * @return int
     */
    public function getChangeCount(): int
    {
        return count($this->changes);
    }

    /**
     * Reset all recorded changes
     *
     * Useful when reusing the same visitor instance for multiple files.
     *
     * @return void
     */
    public function resetChanges(): void
    {
        $this->changes = [];
    }

    /**
     * Get the line number of a node
     *
     * @param Node $node
     * @return int
     */
    protected function getNodeLine(Node $node): int
    {
        return $node->getAttribute('startLine', $this->currentLine);
    }

    /**
     * Get the column number of a node
     *
     * @param Node $node
     * @return int
     */
    protected function getNodeColumn(Node $node): int
    {
        return $node->getAttribute('startFilePos', 0);
    }

    /**
     * Get the current parent node from the stack
     *
     * @param int $depth How many levels up to look (default: 0 = top of stack)
     * @return Node|null
     */
    protected function getParentNode(int $depth = 0): ?Node
    {
        $index = count($this->nodeStack) - 1 - $depth;

        if ($index >= 0 && $index < count($this->nodeStack)) {
            return $this->nodeStack[$index];
        }

        return null;
    }

    /**
     * Check if a node has a specific parent type at any level
     *
     * @param Node $node The node to check
     * @param string|array<string> $parentTypes Class name or array of class names to match
     * @return bool
     */
    protected function hasParentOfType(Node $node, string|array $parentTypes): bool
    {
        $types = is_array($parentTypes) ? $parentTypes : [$parentTypes];

        foreach ($this->nodeStack as $parent) {
            foreach ($types as $type) {
                if ($parent instanceof $type) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the first parent node of a specific type
     *
     * @param string|array<string> $parentTypes Class name or array of class names to match
     * @return Node|null
     */
    protected function getFirstParentOfType(string|array $parentTypes): ?Node
    {
        $types = is_array($parentTypes) ? $parentTypes : [$parentTypes];

        foreach ($this->nodeStack as $parent) {
            foreach ($types as $type) {
                if ($parent instanceof $type) {
                    return $parent;
                }
            }
        }

        return null;
    }

    /**
     * Create a new node while preserving important attributes from the original
     *
     * This helper method copies relevant attributes (like line numbers, file position)
     * from an original node to a new node to maintain source mapping.
     *
     * @param Node $newNode The new node to augment
     * @param Node $originalNode The original node to copy attributes from
     * @return Node The new node with copied attributes
     */
    protected function preserveAttributes(Node $newNode, Node $originalNode): Node
    {
        // Copy important attributes
        $attributes = [
            'startLine',
            'endLine',
            'startFilePos',
            'endFilePos',
            'startTokenPos',
            'endTokenPos',
            'comments',
        ];

        foreach ($attributes as $attr) {
            if ($originalNode->hasAttribute($attr)) {
                $newNode->setAttribute($attr, $originalNode->getAttribute($attr));
            }
        }

        return $newNode;
    }

    /**
     * Check if a node is the same as another node
     *
     * Useful for identifying nodes to skip or for deduplication.
     *
     * @param Node $node1 First node
     * @param Node $node2 Second node
     * @return bool True if nodes are identical
     */
    protected function areNodesIdentical(Node $node1, Node $node2): bool
    {
        return $node1 === $node2;
    }

    /**
     * Create a node copy with new values
     *
     * @param Node $node The node to copy
     * @return Node A shallow copy of the node
     */
    protected function cloneNode(Node $node): Node
    {
        $clone = clone $node;
        return $this->preserveAttributes($clone, $node);
    }

    /**
     * Check if the given node is a constant with a specific value
     *
     * @param Node $node The node to check
     * @param string $value The constant value to match (e.g., 'true', 'false', 'null')
     * @return bool
     */
    protected function isConstantFetch(Node $node, string $value): bool
    {
        if (!$node instanceof Node\Expr\ConstFetch) {
            return false;
        }

        return $node->name->toLowerString() === strtolower($value);
    }

    /**
     * Check if the given node represents a variable
     *
     * @param Node $node The node to check
     * @return bool
     */
    protected function isVariable(Node $node): bool
    {
        return $node instanceof Node\Expr\Variable;
    }

    /**
     * Check if the given node represents a function/method call
     *
     * @param Node $node The node to check
     * @return bool
     */
    protected function isFunctionCall(Node $node): bool
    {
        return $node instanceof Node\Expr\FuncCall ||
               $node instanceof Node\Expr\MethodCall ||
               $node instanceof Node\Expr\StaticCall;
    }

    /**
     * Check if the given node is a binary operation
     *
     * @param Node $node The node to check
     * @return bool
     */
    protected function isBinaryOp(Node $node): bool
    {
        return $node instanceof Node\Expr\BinaryOp;
    }

    /**
     * Get the variable name from a Variable node
     *
     * @param Node $node The node (should be a Variable node)
     * @return string|null The variable name or null if not a variable
     */
    protected function getVariableName(Node $node): ?string
    {
        if (!$node instanceof Node\Expr\Variable) {
            return null;
        }

        if (is_string($node->name)) {
            return $node->name;
        }

        return null;
    }

    /**
     * Check if a statement node represents a return statement
     *
     * @param Node $node The node to check
     * @return bool
     */
    protected function isReturnStatement(Node $node): bool
    {
        return $node instanceof Node\Stmt\Return_;
    }

    /**
     * Check if a statement is a break or continue
     *
     * @param Node $node The node to check
     * @return bool
     */
    protected function isBreakOrContinue(Node $node): bool
    {
        return $node instanceof Node\Stmt\Break_ ||
               $node instanceof Node\Stmt\Continue_;
    }

    /**
     * Check if a statement is a throw
     *
     * @param Node $node The node to check
     * @return bool
     */
    protected function isThrowStatement(Node $node): bool
    {
        return $node instanceof Node\Stmt\Throw_;
    }

    /**
     * Get the next sibling node in the parent's children array
     *
     * Note: This requires parent context and may not always work.
     *
     * @param Node $node The node to find the next sibling for
     * @param Node $parent The parent node containing the node
     * @return Node|null The next sibling or null if none
     */
    protected function getNextSibling(Node $node, Node $parent): ?Node
    {
        // This is a simplified implementation
        // In real use, you'd need to track parent-child relationships
        return null;
    }

    /**
     * Format a node for display in error messages
     *
     * @param Node $node The node to format
     * @return string A human-readable representation
     */
    protected function formatNode(Node $node): string
    {
        if ($node instanceof Node\Expr\Variable) {
            return '$' . $this->getVariableName($node);
        }

        if ($node instanceof Node\Expr\ConstFetch) {
            return $node->name->toString();
        }

        if ($node instanceof Node\Scalar) {
            return (string) $node->value;
        }

        return $node::class;
    }

    /**
     * Debug helper: print node structure
     *
     * @param Node $node The node to debug
     * @return void
     */
    protected function debugNode(Node $node): void
    {
        echo "Node Type: " . $node::class . "\n";
        echo "Line: " . $this->getNodeLine($node) . "\n";

        $properties = get_object_vars($node);
        foreach ($properties as $key => $value) {
            if (!in_array($key, ['attributes', 'endAttributes'], true)) {
                echo "  $key: " . print_r($value, true) . "\n";
            }
        }
    }

    /**
     * Get debug information about the current state
     *
     * @return array Debug information
     */
    public function getDebugInfo(): array
    {
        return [
            'changes_count' => $this->getChangeCount(),
            'changes' => $this->getChanges(),
            'stack_depth' => count($this->nodeStack),
            'current_line' => $this->currentLine,
        ];
    }
}
