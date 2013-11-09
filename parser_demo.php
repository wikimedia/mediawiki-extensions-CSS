<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/PHP-CSS-Parser/lib/');
require_once 'Sabberworm/CSS/Parser.php';
require_once 'Sabberworm/CSS/Settings.php';
require_once 'Sabberworm/CSS/Property/AtRule.php';
require_once 'Sabberworm/CSS/Value/Value.php';
require_once 'Sabberworm/CSS/Value/PrimitiveValue.php';
require_once 'Sabberworm/CSS/Value/Size.php';
require_once 'Sabberworm/CSS/Value/String.php';
require_once 'Sabberworm/CSS/CSSList/CSSList.php';
require_once 'Sabberworm/CSS/CSSList/CSSBlockList.php';
require_once 'Sabberworm/CSS/CSSList/Document.php';
require_once 'Sabberworm/CSS/CSSList/KeyFrame.php';
require_once 'Sabberworm/CSS/Value/Value.php';
require_once 'Sabberworm/CSS/Property/Charset.php';
require_once 'Sabberworm/CSS/RuleSet/RuleSet.php';
require_once 'Sabberworm/CSS/RuleSet/DeclarationBlock.php';
require_once 'Sabberworm/CSS/RuleSet/AtRuleSet.php';
require_once 'Sabberworm/CSS/Rule/Rule.php';
require_once 'Sabberworm/CSS/Value/URL.php';
require_once 'Sabberworm/CSS/Property/Selector.php';

$oParser = new Sabberworm\CSS\Parser(file_get_contents($argv[1]));
$oCss = $oParser->parse();
echo "Original CSS " . $oCss;

foreach($oCss->getAllRuleSets() as $oRuleSet) {
     echo $oRuleSet . "\n";
     $oRuleSet->removeRule('background-');
}
echo "Modified CSS " . $oCss;
?>
