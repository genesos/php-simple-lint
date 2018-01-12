<?php

namespace SimpleLint;

use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class Lint
{

    /*
    X.php
    --standard=C:/desktop/_dev/ridi/platform/docs/lint/php/ruleset.xml
    --encoding=utf-8
    --report=xml
     */
    /**
     * @param $rule_file
     * @param $command_args
     *
     * @return string
     * @throws \DomainException
     * @throws \InvalidArgumentException
     */
    public static function run($rule_file, $command_args): string
    {
        $remain_command_args = $command_args;
        $php_file = array_shift($remain_command_args);
        $standard = array_shift($remain_command_args);
        $encoding = array_shift($remain_command_args);
        $report_type = array_shift($remain_command_args);

        $is_error = 0;

        $is_error |= ($rule_file === null);
        $is_error |= (!is_file($php_file));
        $is_error |= ('' === $standard);
        $is_error |= ($encoding !== '--encoding=utf-8');
        $is_error |= ($report_type !== '--report=xml');
        $is_error |= (count($remain_command_args) > 0);
        if ($is_error) {
            if ($php_file === '--version') {
                return '';
            }
            if ($rule_file === null) {
                return '';
            }
            throw new \InvalidArgumentException('invalid argument (only support PHPSTORM)');
        }

        $php_string = file_get_contents($php_file);
        $rules = self::loadRulesFromFile($rule_file);

        $serialized_entities = self::convertPhpFromStringToSerializedEntrities($php_string);
        $serialized_entities_with_reason = self::filterByRules($rules, $serialized_entities);

        return self::exportXml($php_string, $serialized_entities_with_reason);
    }

    /*
    <?xml version="1.0" encoding="UTF-8"?>
    <phpcs version="x.x.x">
    <file name="/path/to/code/classA.php" errors="4" warnings="1" fixable="3">
    <error line="2" column="1" source="PEAR.Commenting.FileComment.Missing" severity="5" fixable="0">Missing file doc comment</error>
    <error line="4" column="12" source="Generic.PHP.LowerCaseConstant.Found" severity="5" fixable="1">TRUE, FALSE and NULL must be lowercase; expected &quot;false&quot; but found &quot;FALSE&quot;</error>
    <error line="6" column="2" source="PEAR.WhiteSpace.ScopeIndent.Incorrect" severity="5" fixable="1">Line indented incorrectly; expected at least 4 spaces, found 1</error>
    <error line="9" column="1" source="PEAR.Commenting.FunctionComment.Missing" severity="5" fixable="0">Missing function doc comment</error>
    <warning line="11" column="5" source="Generic.ControlStructures.InlineControlStructure.Discouraged" severity="5" fixable="1">Inline control structures are discouraged</warning>
    </file>
    </phpcs>
    */
    /**
     * @param $phpcs_xml
     * @param $simple_lint_xml
     *
     * @return mixed
     */
    public static function mergeResult($phpcs_xml, $simple_lint_xml)
    {
        if ($simple_lint_xml === null) {
            return $phpcs_xml;
        }

        return str_replace('</file>', $simple_lint_xml . PHP_EOL . '</file>', $phpcs_xml);
    }

    /**
     * @param                     $php_string
     * @param SeriailizedEntity[] $serialized_entities_with_reason
     *
     * @return array|string
     * @throws \DomainException
     */
    private static function exportXml($php_string, $serialized_entities_with_reason)
    {
        $php_array = explode("\n", $php_string);
        $parsed_php_length = 0;

        $return = [];
        foreach ($serialized_entities_with_reason as $serialized_entity) {
            $line_pos = $serialized_entity->startLine;
            $file_pos = $serialized_entity->file_pos;
            $clause = $serialized_entity->clause;
            $type = $serialized_entity->type;
            $reason = $serialized_entity->reason;

            while (true) {
                if (count($php_array) === 0) {
                    throw new \DomainException('find php code underflow');
                }
                $current_line = reset($php_array);
                $current_line_max = $parsed_php_length + \strlen($current_line);
                if ($file_pos >= $current_line_max) {
                    $parsed_php_length = $current_line_max + 1;
                    array_shift($php_array);
                    continue;
                }
                break;
            }
            $current_line_min = $parsed_php_length;
            $current_width_in_line = $file_pos - $current_line_min;
            $current_line = reset($php_array);

            $prefix = substr($current_line, 0, $current_width_in_line);
            $prefix = preg_replace('/\S+$/', '', $prefix);
            $token_count_in_line = \strlen($prefix);

            $html_reason = htmlspecialchars("[$type] {$reason} <{$clause}>");
            $return[] = "<error line='{$line_pos}' column='{$token_count_in_line}'  source='RIDI.LINT' severity='5' fixable='1'>$html_reason</error>";
        }

        $return = implode(PHP_EOL, $return);

        return $return;
    }

    /**
     * @param                     $rules
     * @param SeriailizedEntity[] $serialized_entities
     *
     * @return array
     */
    public static function filterByRules($rules, $serialized_entities): array
    {
        $rules = array_map(
            function ($rule) {
                return self::convertRuleFromArrayToCallable($rule);
            },
            $rules
        );
        $serialized_entities_with_reason = [];
        foreach ($serialized_entities as $k => $serialized_entity) {
            foreach ($rules as $rule) {
                $reason = $rule($serialized_entity);
                if ($reason === null) {
                    continue;
                }
                $serialized_entity->reason = $reason;
                $serialized_entities_with_reason[] = $serialized_entity;
                break;
            }
        }

        return $serialized_entities_with_reason;
    }

    private static function convertRuleFromArrayToCallable($rule): callable
    {
        return function (SeriailizedEntity $serialized_entity) use ($rule) {
            $clause = $serialized_entity->clause;
            $type = $serialized_entity->type;
            if ($type !== $rule['type']) {
                return null;
            }

            $ifs = $rule['if'];
            if (\is_string($ifs)) {
                $ifs = [$ifs];
            }
            if (\is_array($ifs)) {
                foreach ($ifs as $if) {
                    if (!preg_match('/(^|\s)' . $if . '($|\s)/', $clause)) {
                        return null;
                    }
                }
            }

            $if_nots = $rule['if not'];
            if (\is_string($if_nots)) {
                $if_nots = [$if_nots];
            }
            if (\is_array($if_nots)) {
                foreach ($if_nots as $if_not) {
                    if (preg_match('/(^|\s)' . $if_not . '($|\s)/', $clause)) {
                        return null;
                    }
                }
            }

            $must = $rule['must'];
            if (\is_string($must)) {
                if (!preg_match('/(^|\s)' . $must . '($|\s)/', $clause)) {
                    return $rule['reason'];
                }
            }

            $must_not = $rule['must not'];
            if (\is_string($must_not)) {
                if (preg_match('/' . $must_not . '($|\s)/', $clause)) {
                    return $rule['reason'];
                }
            }

            return null;
        };
    }

    private static function loadRulesFromFile($rule_file)
    {
        $rules = json_decode(file_get_contents($rule_file), true);

        return array_filter($rules);
    }

    /**
     * @param $php_string
     *
     * @return SeriailizedEntity[]
     */
    public static function convertPhpFromStringToSerializedEntrities($php_string): array
    {
        $lexer = new Lexer(
            [
                'usedAttributes' => [
                    'startFilePos',
                    'startLine',
                    'endLine',
                ],
            ]
        );
        $visitor = new LintVisitor();
        $traverser = new NodeTraverser;
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer);

        $stmts = $parser->parse($php_string);
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        return $visitor->getSerializedEntities();
    }
}
